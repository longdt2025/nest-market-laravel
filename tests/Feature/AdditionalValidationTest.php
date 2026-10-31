<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\ProductWorkbook;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class AdditionalValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->actingAs(User::where('role', 'manager')->firstOrFail());
    }

    public function test_category_name_is_normalized_unique_and_edit_can_keep_own_name(): void
    {
        $category = Category::create(['name' => 'Test Group', 'slug' => 'test-group', 'is_active' => true]);
        $this->post(route('manager.categories.store'), ['name' => '  TEST   GROUP  '])->assertSessionHasErrors('name');
        $this->put(route('manager.categories.update', $category), ['name' => ' Test Group ', 'is_active' => 1])->assertSessionHasNoErrors();
        $this->assertSame('Test Group', $category->fresh()->name);
    }

    public function test_product_rejects_precision_hidden_category_and_large_dimensions(): void
    {
        $product = Product::firstOrFail();
        $category = $product->category;
        $category->update(['is_active' => false]);
        $data = ['name' => $product->name, 'category_id' => $category->id, 'price' => 100000, 'stock' => 10, 'is_active' => 1, 'vat_rate' => '8.123', 'image' => UploadedFile::fake()->image('wide.png', 4200, 1)];
        $this->put(route('manager.products.update', $product), $data)->assertSessionHasErrors(['category_id', 'vat_rate', 'image']);
        unset($data['image']);
        $data['is_active'] = 0;
        $data['vat_rate'] = '8.25';
        $this->put(route('manager.products.update', $product), $data)->assertSessionHasNoErrors();
        $this->assertSame('8.25', $product->fresh()->vat_rate);
    }

    public function test_unknown_bank_bin_is_rejected_before_upload(): void
    {
        $this->put(route('manager.payments.settings.update'), ['bank_bin' => '999999', 'bank_name' => 'Unknown', 'account_number' => '123456789', 'account_holder' => 'TEST', 'qr' => UploadedFile::fake()->image('qr.png')])->assertSessionHasErrors('bank_bin');
        $this->assertDatabaseCount('payment_settings', 0);
    }

    public function test_excel_obeys_same_vat_and_active_category_rules(): void
    {
        $category = Category::firstOrFail();
        $category->update(['is_active' => false]);
        $result = app(ProductWorkbook::class)->validateRows([['line' => 2, 'formula' => false, 'values' => ['ma_hang' => 'VALIDATION-TEST', 'ten_hang' => 'Test product', 'ma_danh_muc' => $category->id, 'gia_ban' => 100000, 'ton_kho' => 5, 'vat' => '8.123', 'hien_thi' => 1]]]);
        $this->assertSame(1, $result['error_count']);
        $this->assertGreaterThanOrEqual(2, count($result['rows'][0]['errors']));
    }
}
