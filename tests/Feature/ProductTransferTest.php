<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\ProductWorkbook;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as Reader;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class ProductTransferTest extends TestCase
{
    use RefreshDatabase;

    private array $tempFiles = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        Storage::fake('local');
        $this->actingAs(User::where('role', 'manager')->firstOrFail());
    }

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }
        parent::tearDown();
    }

    private function file(array $rows): UploadedFile
    {
        $book = new Spreadsheet;
        $sheet = $book->getActiveSheet()->setTitle('San pham');
        $sheet->fromArray(array_values(ProductWorkbook::HEADERS), null, 'A1');
        foreach ($rows as $i => $values) {
            foreach ($values as $j => $value) {
                $sheet->setCellValueExplicit([$j + 1, $i + 2], $value, DataType::TYPE_STRING);
            }
        }
        $book->createSheet()->setTitle('Other');
        $path = tempnam(sys_get_temp_dir(), 'nest-xlsx-');
        $this->tempFiles[] = $path;
        (new Xlsx($book))->save($path);
        $book->disconnectWorksheets();

        return new UploadedFile($path, 'products.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }

    private function row(array $extra = []): array
    {
        return array_replace(['NEW-EXCEL', 'Bàn Excel kiểm thử', Category::firstOrFail()->id, 'Tên nhóm', '001234567890', 'Cái', 50000, 99000, 8, 7, 1, 'office'], $extra);
    }

    private function stage(array $rows): array
    {
        $scan = $this->post(route('manager.products.import.scan'), ['file' => $this->file($rows)], ['Accept' => 'application/json'])->assertOk()->json();
        $preview = $this->postJson(route('manager.products.import.preview'), ['token' => $scan['token'], 'sheet' => 'San pham'])->assertOk()->json();

        return [$scan['token'], $preview];
    }

    public function test_filtered_product_list_combines_status_inventory_room_price_and_search(): void
    {
        $p = Product::where('room', 'living')->firstOrFail();
        $p->update(['sku' => 'FIND-ME', 'barcode' => '001111', 'stock' => 2, 'price' => 150000, 'is_active' => true]);
        $response = $this->get(route('manager.products.index', ['q' => '001111', 'room' => 'living', 'inventory' => 'low_stock', 'status' => 'active', 'min_price' => 100000, 'max_price' => 200000]));
        $response->assertOk()->assertViewHas('products', fn ($items) => $items->total() === 1 && $items->first()->id === $p->id)->assertSee('Nhập file Excel')->assertSee('Tùy chọn xuất dữ liệu');
        $this->get(route('manager.products.index', ['min_price' => 20, 'max_price' => 10]))->assertSessionHasErrors('max_price');
    }

    public function test_sheet_scan_preview_and_commit_create_product_and_replay_is_idempotent(): void
    {
        [$token,$preview] = $this->stage([$this->row()]);
        $this->assertSame(0, $preview['errors']);
        $this->assertSame(1, $preview['created']);
        $this->assertDatabaseMissing('products', ['sku' => 'NEW-EXCEL']);
        $this->postJson(route('manager.products.import.commit'), ['token' => $token])->assertOk()->assertJson(['created' => 1, 'updated' => 0]);
        $this->assertDatabaseHas('products', ['sku' => 'NEW-EXCEL', 'barcode' => '001234567890', 'price' => 99000, 'stock' => 7, 'cost_price' => 50000, 'vat_rate' => 8]);
        $this->postJson(route('manager.products.import.commit'), ['token' => $token])->assertOk();
        $this->assertSame(1, Product::where('sku', 'NEW-EXCEL')->count());
    }

    public function test_invalid_row_blocks_entire_import_and_reports_line_number(): void
    {
        [$token,$preview] = $this->stage([$this->row(), $this->row([0 => 'BAD', 1 => 'Giá âm', 4 => '00999', 7 => -1])]);
        $this->assertSame(1, $preview['errors']);
        $this->assertSame(3, $preview['rows'][1]['line']);
        $this->postJson(route('manager.products.import.commit'), ['token' => $token])->assertUnprocessable();
        $this->assertDatabaseCount('products', 16);
    }

    public function test_update_preserves_images_and_detects_stock_change_after_preview(): void
    {
        $p = Product::firstOrFail();
        [$token,$preview] = $this->stage([$this->row([0 => $p->code])]);
        $this->assertSame(1, $preview['updated']);
        $p->update(['stock' => 3]);
        $this->postJson(route('manager.products.import.commit'), ['token' => $token])->assertUnprocessable();
        $this->assertSame(3, $p->fresh()->stock);
        [$token] = $this->stage([$this->row([0 => $p->code])]);
        $image = $p->image_path;
        $this->postJson(route('manager.products.import.commit'), ['token' => $token])->assertOk();
        $this->assertSame($image, $p->fresh()->image_path);
        $this->assertSame(7, $p->fresh()->stock);
    }

    public function test_duplicate_codes_and_barcodes_and_formulas_are_rejected(): void
    {
        [$token,$preview] = $this->stage([$this->row(), $this->row([1 => 'Sản phẩm trùng'])]);
        $this->assertGreaterThan(0, $preview['errors']);
        $this->postJson(route('manager.products.import.commit'), ['token' => $token])->assertUnprocessable();
        $file = $this->file([$this->row()]);
        $book = (new Reader)->load($file->getRealPath());
        $book->getActiveSheet()->setCellValue('H2', '=1+1');
        (new Xlsx($book))->save($file->getRealPath());
        $book->disconnectWorksheets();
        $scan = $this->post(route('manager.products.import.scan'), ['file' => $file], ['Accept' => 'application/json'])->assertOk()->json();
        $this->postJson(route('manager.products.import.preview'), ['token' => $scan['token'], 'sheet' => 'San pham'])->assertOk()->assertJsonPath('errors', 1);
    }

    private function readDownload($response): Spreadsheet
    {
        $path = tempnam(sys_get_temp_dir(), 'nest-export-');
        $this->tempFiles[] = $path;
        file_put_contents($path, $response->streamedContent());

        return (new Reader)->load($path);
    }

    public function test_export_selected_keeps_barcode_text_and_prevents_formula_injection(): void
    {
        $p = Product::firstOrFail();
        $p->update(['name' => '=HYPERLINK("bad","name")', 'barcode' => '001234']);
        $response = $this->post(route('manager.products.export'), ['scope' => 'selected', 'ids' => [$p->id]])->assertOk()->assertDownload();
        $book = $this->readDownload($response);
        $sheet = $book->getSheet(0);
        $this->assertSame(2, $sheet->getHighestDataRow());
        $this->assertSame($p->name, $sheet->getCell('B2')->getValue());
        $this->assertSame(DataType::TYPE_STRING, $sheet->getCell('B2')->getDataType());
        $this->assertSame('001234', $sheet->getCell('E2')->getValue());
        $book->disconnectWorksheets();
    }

    public function test_export_uses_same_filters_across_all_pages_and_template_has_category_reference(): void
    {
        $p = Product::firstOrFail();
        $p->update(['sku' => 'ONLY-THIS']);
        $book = $this->readDownload($this->post(route('manager.products.export'), ['scope' => 'filtered', 'q' => 'ONLY-THIS'])->assertOk());
        $this->assertSame('ONLY-THIS', $book->getSheet(0)->getCell('A2')->getValue());
        $this->assertSame(2, $book->getSheet(0)->getHighestDataRow());
        $book->disconnectWorksheets();
        $template = $this->readDownload($this->get(route('manager.products.template'))->assertOk());
        $this->assertNotNull($template->getSheetByName('Danh muc'));
        $this->assertSame('Mã hàng', $template->getSheet(0)->getCell('A1')->getValue());
        $template->disconnectWorksheets();
    }

    public function test_import_tokens_are_session_bound_and_roles_cannot_transfer_or_bulk(): void
    {
        [$token] = $this->stage([$this->row()]);
        $this->post(route('logout'));
        $this->actingAs(User::where('role', 'manager')->firstOrFail())->postJson(route('manager.products.import.preview'), ['token' => $token, 'sheet' => 'San pham'])->assertUnprocessable();
        foreach (['admin', 'customer'] as $role) {
            $this->actingAs(User::where('role', $role)->firstOrFail());
            $this->postJson(route('manager.products.import.commit'), ['token' => $token])->assertForbidden();
            $this->post(route('manager.products.export'), ['scope' => 'filtered'])->assertForbidden();
            $this->post(route('manager.products.bulk'), ['action' => 'hide', 'ids' => [Product::firstOrFail()->id]])->assertForbidden();
            $this->get(route('manager.products.template'))->assertForbidden();
        }
    }

    public function test_new_code_claimed_after_preview_is_not_overwritten(): void
    {
        [$token] = $this->stage([$this->row()]);
        $p = Product::firstOrFail();
        $p->update(['sku' => 'NEW-EXCEL', 'stock' => 3]);
        $this->postJson(route('manager.products.import.commit'), ['token' => $token])->assertUnprocessable();
        $this->assertSame(3, $p->fresh()->stock);
    }

    public function test_bulk_updates_only_selected_products(): void
    {
        $p = Product::firstOrFail();
        $this->post(route('manager.products.bulk'), ['action' => 'hide', 'ids' => [$p->id]])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertFalse($p->fresh()->is_active);
        $this->assertSame(15, Product::where('is_active', true)->count());
    }

    public function test_header_sort_uses_displayed_codes_and_numeric_values_while_preserving_filters(): void
    {
        $items = Product::orderBy('id')->limit(3)->get();
        foreach ($items as $i => $item) {
            $item->update(['name' => 'Sort sample '.$i, 'category_id' => $items[0]->category_id, 'sku' => ['ZZ-LAST', 'AA-FIRST', null][$i], 'price' => [3000, 1000, 2000][$i], 'stock' => [1, 3, 2][$i]]);
        }
        $filters = ['q' => 'Sort sample', 'category' => $items[0]->category_id, 'per_page' => 10, 'sort' => 'code', 'direction' => 'asc', 'page' => 1];
        $this->get(route('manager.products.index', $filters))->assertOk()
            ->assertViewHas('products', fn ($products) => $products->pluck('id')->all() === [$items[1]->id, $items[2]->id, $items[0]->id])
            ->assertSee(route('manager.products.index', ['q' => 'Sort sample', 'category' => $items[0]->category_id, 'per_page' => 10, 'sort' => 'code', 'direction' => 'desc']));
        $filters['direction'] = 'desc';
        $this->get(route('manager.products.index', $filters))->assertOk()
            ->assertViewHas('products', fn ($products) => $products->pluck('id')->all() === [$items[0]->id, $items[2]->id, $items[1]->id]);
        $filters['sort'] = 'stock';
        $this->get(route('manager.products.index', $filters))->assertOk()
            ->assertViewHas('products', fn ($products) => $products->pluck('id')->all() === [$items[1]->id, $items[2]->id, $items[0]->id]);
    }
}
