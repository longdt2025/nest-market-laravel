<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\BackupService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class WorkspaceSecurityTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->admin = User::where('role', 'admin')->firstOrFail();
        $this->manager = User::where('role', 'manager')->firstOrFail();
        Storage::fake('minio');
        Storage::fake('public');
        config(['backups.disk' => 'minio']);
    }

    private function accountData(User $user, array $extra = []): array
    {
        return array_replace(['name' => $user->name, 'email' => $user->email, 'username' => $user->username, 'role' => $user->role, 'is_active' => 1], $extra);
    }

    public function test_admin_and_manager_have_separate_read_and_write_permissions(): void
    {
        $this->actingAs($this->admin)->get(route('system.accounts.index'))->assertOk()->assertDontSee('Quản lý sản phẩm');
        $this->get(route('manager.dashboard'))->assertForbidden();
        $this->post(route('manager.products.store'), [])->assertForbidden();
        $this->actingAs($this->manager)->get(route('manager.products.index'))->assertOk();
        $this->get(route('system.accounts.index'))->assertForbidden();
        $this->get(route('system.backups.index'))->assertForbidden();
        $this->post(route('system.backups.store'), ['label' => 'Denied'])->assertForbidden();
        $this->put(route('system.accounts.update', $this->admin), $this->accountData($this->admin))->assertForbidden();
    }

    public function test_manager_login_redirect_and_account_menu(): void
    {
        $this->post(route('login.submit'), ['email' => 'manager', 'password' => 'Password@123'])->assertRedirect(route('manager.dashboard'));
        $this->get(route('manager.dashboard'))->assertOk()->assertSee('Đổi mật khẩu')->assertSee('Cập nhật thông tin')->assertSee('Đăng xuất')->assertDontSee('Sao lưu / Phục hồi');
    }

    public function test_admin_can_create_manager_and_lock_account_but_cannot_lock_self(): void
    {
        $this->actingAs($this->admin)->post(route('system.accounts.store'), [
            'name' => 'Quản lý mới', 'email' => 'newmanager@nest.test', 'username' => 'newmanager', 'role' => 'manager', 'is_active' => 1, 'password' => 'Manager123', 'password_confirmation' => 'Manager123',
        ])->assertRedirect(route('system.accounts.index'));
        $this->assertDatabaseHas('users', ['username' => 'newmanager', 'role' => 'manager']);
        $this->put(route('system.accounts.update', $this->manager), $this->accountData($this->manager, ['is_active' => 0]))->assertSessionHasNoErrors();
        $this->assertFalse($this->manager->fresh()->is_active);
        $this->put(route('system.accounts.update', $this->admin), $this->accountData($this->admin, ['role' => 'customer', 'is_active' => 0]))->assertSessionHasErrors('role');
        $this->assertTrue($this->admin->fresh()->is_active);
        $this->assertTrue($this->admin->fresh()->isAdmin());
    }

    public function test_locked_account_cannot_login_and_existing_session_is_revoked(): void
    {
        $this->post(route('login.submit'), ['email' => 'manager', 'password' => 'Password@123'])->assertRedirect();
        $this->manager->forceFill(['is_active' => false])->save();
        $this->get(route('manager.dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
        $this->post(route('login.submit'), ['email' => 'manager', 'password' => 'Password@123'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_account_profile_and_password_cannot_change_own_role(): void
    {
        $this->actingAs($this->manager)->patch(route('staff.account.update'), ['name' => 'Quản lý đã sửa', 'phone' => '0912345678', 'role' => 'admin'])->assertSessionHasNoErrors();
        $this->assertSame('manager', $this->manager->fresh()->role);
        $this->post(route('staff.password'), ['current_password' => 'wrong', 'password' => 'NewPassword123', 'password_confirmation' => 'NewPassword123'])->assertSessionHasErrors('current_password');
        $this->post(route('staff.password'), ['current_password' => 'Password@123', 'password' => 'NewPassword123', 'password_confirmation' => 'NewPassword123'])->assertRedirect(route('login'));
        $this->assertGuest();
        $this->assertTrue(Hash::check('NewPassword123', $this->manager->fresh()->password));
    }

    public function test_backup_roundtrip_restores_data_uploads_and_keeps_recovery_copy(): void
    {
        $product = Product::firstOrFail();
        Storage::disk('public')->put('products/original.jpg', 'original-picture');
        $product->update(['image_path' => 'products/original.jpg', 'gallery' => ['products/original.jpg'], 'stock' => 9]);
        $service = app(BackupService::class);
        $backup = $service->create($this->admin, 'Snapshot');
        $archive = Storage::disk('minio')->get($backup->path);
        $this->assertStringNotContainsString('admin@nest.test', $archive);
        $this->assertStringNotContainsString('original-picture', $archive);
        $product->update(['stock' => 1, 'name' => 'Changed']);
        $service->restore($backup, $this->admin);
        $restored = $product->fresh();
        $this->assertSame(9, $restored->stock);
        $this->assertNotSame('Changed', $restored->name);
        $this->assertSame('original-picture', Storage::disk('public')->get($restored->image_path));
        $this->assertSame($restored->image_path, $restored->gallery[0]);
        $this->assertDatabaseCount('backups', 2);
        $this->assertNotNull($backup->fresh()->restored_at);
    }

    public function test_tampered_backup_is_rejected_without_changing_database(): void
    {
        $service = app(BackupService::class);
        $backup = $service->create($this->admin, 'Snapshot');
        Storage::disk('minio')->put($backup->path, 'corrupted');
        $stock = Product::firstOrFail()->stock;
        try {
            $service->restore($backup, $this->admin);
            $this->fail('Must reject damaged file');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('backup', $e->errors());
        }
        $this->assertSame($stock, Product::firstOrFail()->stock);
        $this->assertDatabaseCount('backups', 1);
    }

    public function test_backup_failure_leaves_no_success_record(): void
    {
        Storage::shouldReceive('disk')->with('minio')->andThrow(new \RuntimeException('MinIO unavailable'));
        $this->actingAs($this->admin)->post(route('system.backups.store'), ['label' => 'Failure'])->assertSessionHasErrors('backup');
        $this->assertDatabaseCount('backups', 0);
        $this->assertDatabaseCount('products', 22);
    }

    public function test_restore_requires_current_password_and_explicit_confirmation(): void
    {
        $backup = app(BackupService::class)->create($this->admin, 'Snapshot');
        $this->actingAs($this->admin)->post(route('system.backups.restore', $backup), ['current_password' => 'wrong', 'confirmation' => 'PHUC HOI '.$backup->id])->assertSessionHasErrors('current_password');
        $this->post(route('system.backups.restore', $backup), ['current_password' => 'admin123', 'confirmation' => 'wrong'])->assertSessionHasErrors('confirmation');
        $this->assertDatabaseCount('backups', 1);
        $this->assertNull($backup->fresh()->restored_at);
        $this->actingAs($this->manager)->post(route('system.backups.restore', $backup), [])->assertForbidden();
    }

    public function test_restore_database_failure_rolls_back_and_removes_only_new_images(): void
    {
        Storage::disk('public')->put('products/original.jpg', 'picture');
        Product::firstOrFail()->update(['image_path' => 'products/original.jpg']);
        $service = app(BackupService::class);
        $backup = $service->create($this->admin, 'Snapshot');
        Product::firstOrFail()->update(['stock' => 3]);
        DB::statement("CREATE TRIGGER reject_restore BEFORE INSERT ON products BEGIN SELECT RAISE(ABORT,'test failure'); END");
        try {
            $service->restore($backup, $this->admin);
            $this->fail('Must fail');
        } catch (QueryException $e) {
            $this->assertStringContainsString('test failure', $e->getMessage());
        }
        $this->assertSame(3, Product::firstOrFail()->stock);
        $this->assertDatabaseCount('users', 3);
        $this->assertDatabaseCount('backups', 2);
        $this->assertSame(['products/original.jpg'], Storage::disk('public')->allFiles('products'));
        $this->assertNull($backup->fresh()->restored_at);
    }

    public function test_manager_report_filters_revenue_and_rejects_invalid_dates(): void
    {
        $customer = User::where('role', 'customer')->firstOrFail();
        foreach ([['done', 'delivered', 500000], ['pending', 'pending', 900000]] as [$code,$status,$total]) {
            Order::create(['user_id' => $customer->id, 'code' => $code, 'recipient_name' => 'Test', 'phone' => '0912345678', 'shipping_address' => 'Test address for report', 'subtotal' => $total, 'shipping_fee' => 0, 'total' => $total, 'payment_method' => 'cod', 'status' => $status]);
        }
        $this->actingAs($this->manager)->get(route('manager.dashboard', ['from' => now()->toDateString(), 'to' => now()->toDateString()]))
            ->assertOk()->assertViewHas('sales', 500000)
            ->assertDontSee('Thêm sản phẩm')->assertDontSee('Đơn hàng gần đây')->assertDontSee('Sắp hết hàng');
        $this->get(route('manager.dashboard', ['period' => 'year', 'metric' => 'orders']))->assertOk()
            ->assertViewHas('chartPoints', fn ($points) => count($points) === 12 && array_sum(array_column($points, 'value')) === 2)
            ->assertViewHas('monthly', true);
        $this->get(route('manager.dashboard', ['from' => '2026-10-30', 'to' => '2026-10-01']))->assertSessionHasErrors('to');
    }

    public function test_manager_and_admin_cannot_use_customer_checkout(): void
    {
        foreach ([$this->admin, $this->manager] as $staff) {
            $this->actingAs($staff)->get(route('checkout.create'))->assertForbidden();
            $this->get(route('profile.edit'))->assertForbidden();
        }
    }
}
