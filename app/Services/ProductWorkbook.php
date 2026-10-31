<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class ProductWorkbook
{
    public const HEADERS = ['ma_hang' => 'Mã hàng', 'ten_hang' => 'Tên hàng', 'ma_danh_muc' => 'Mã danh mục', 'nhom_hang' => 'Nhóm hàng', 'barcode' => 'Barcode', 'dvt' => 'ĐVT', 'gia_von' => 'Giá vốn', 'gia_ban' => 'Giá bán', 'vat' => 'VAT (%)', 'ton_kho' => 'Tồn kho', 'hien_thi' => 'Hiển thị', 'phong' => 'Phòng'];

    public function inspect(string $path): array
    {
        $zip = new \ZipArchive;
        if ($zip->open($path) !== true) {
            throw ValidationException::withMessages(['file' => 'File XLSX không hợp lệ.']);
        }
        try {
            $size = 0;
            if ($zip->numFiles > 2048) {
                throw new \RuntimeException;
            }
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $entry = $zip->statIndex($i);
                $size += $entry['size'];
                if ($size > 64 * 1024 * 1024) {
                    throw new \RuntimeException;
                }
            }
        } catch (\Throwable $e) {
            throw ValidationException::withMessages(['file' => 'File vượt giới hạn xử lý an toàn (64 MB sau giải nén).']);
        } finally {
            $zip->close();
        }
        try {
            $info = (new Xlsx)->listWorksheetInfo($path);
            if (count($info) > 20) {
                throw new \RuntimeException;
            }
            foreach ($info as $sheet) {
                if ($sheet['totalRows'] > 2001 || $sheet['totalColumns'] > 30) {
                    throw new \RuntimeException;
                }
            }

            return array_column($info, 'worksheetName');
        } catch (\Throwable $e) {
            throw ValidationException::withMessages(['file' => 'Chỉ hỗ trợ tối đa 20 sheet, 2.000 dòng dữ liệu và 30 cột mỗi sheet.']);
        }
    }

    public function readRows(string $path, string $sheet): array
    {
        $sheets = $this->inspect($path);
        if (! in_array($sheet, $sheets, true)) {
            throw ValidationException::withMessages(['sheet' => 'Sheet không tồn tại trong file.']);
        }
        $reader = new Xlsx;
        $reader->setReadDataOnly(false)->setLoadSheetsOnly($sheet);
        try {
            $book = $reader->load($path);
        } catch (\Throwable $e) {
            throw ValidationException::withMessages(['file' => 'Không đọc được nội dung file XLSX. Hãy lưu lại file bằng Excel rồi thử lại.']);
        }
        try {
            $tab = $book->getSheetByName($sheet);
            $headers = [];
            $known = array_flip(array_map(fn ($v) => Str::slug($v, '_'), self::HEADERS));
            foreach ($tab->getRowIterator(1, 1) as $row) {
                foreach ($row->getCellIterator() as $cell) {
                    $label = trim((string) $cell->getValue());
                    if ($label === '') {
                        continue;
                    }
                    $key = isset(self::HEADERS[$label]) ? $label : ($known[Str::slug($label, '_')] ?? null);
                    if (! $key || in_array($key, $headers, true)) {
                        throw ValidationException::withMessages(['file' => 'Cột không được hỗ trợ hoặc bị trùng: '.$label.'. Vui lòng dùng file mẫu.']);
                    }
                    $headers[$cell->getColumn()] = $key;
                }
            }
            if (array_diff(['ten_hang', 'ma_danh_muc', 'gia_ban', 'ton_kho'], array_values($headers))) {
                throw ValidationException::withMessages(['file' => 'Thiếu cột bắt buộc. Tải file mẫu và giữ nguyên dòng tiêu đề.']);
            }
            $rows = [];
            for ($r = 2; $r <= $tab->getHighestDataRow(); $r++) {
                $data = [];
                $formula = false;
                foreach ($headers as $col => $key) {
                    $cell = $tab->getCell($col.$r);
                    if ($cell->getDataType() === DataType::TYPE_FORMULA) {
                        $formula = true;
                    }
                    $value = $cell->getValue();
                    $data[$key] = is_scalar($value) || $value === null ? trim((string) $value) : '';
                }
                if (! array_filter($data, fn ($v) => $v !== '')) {
                    continue;
                }
                $rows[] = ['line' => $r, 'values' => $data, 'formula' => $formula];
            }
            if (! $rows) {
                throw ValidationException::withMessages(['file' => 'Sheet không có dữ liệu sản phẩm.']);
            }

            return $rows;
        } finally {
            $book->disconnectWorksheets();
        }
    }

    public function fingerprint(Product $product): string
    {
        $data = $product->getRawOriginal();
        ksort($data);

        return hash('sha256', json_encode($data, JSON_THROW_ON_ERROR));
    }

    public function validateRows(array $rows): array
    {
        $results = [];
        $seenCodes = [];
        $seenBarcodes = [];
        $fingerprints = [];
        foreach ($rows as $raw) {
            $v = $raw['values'];
            $errors = [];
            $code = trim($v['ma_hang'] ?? '');
            $product = $code !== '' ? Product::where('sku', $code)->first() : null;
            if (! $product && preg_match('/^SP0*(\d+)$/i', $code, $m)) {
                $product = Product::whereKey((int) $m[1])->whereNull('sku')->first();
                if (! $product) {
                    $errors[] = 'Mã SP tự sinh không tồn tại; để trống mã để thêm mới.';
                }
            }
            $data = ['name' => $v['ten_hang'] ?? '', 'category_id' => $v['ma_danh_muc'] ?? '', 'price' => $v['gia_ban'] ?? '', 'stock' => $v['ton_kho'] ?? ''];
            if ($code !== '' && ! $product) {
                $data['sku'] = $code;
            }
            foreach (['barcode' => 'barcode', 'dvt' => 'unit', 'gia_von' => 'cost_price', 'vat' => 'vat_rate', 'phong' => 'room'] as $key => $field) {
                if (array_key_exists($key, $v)) {
                    $data[$field] = $v[$key] !== '' ? $v[$key] : match ($field) {
                        'unit' => 'Cái','cost_price','vat_rate' => 0,default => null
                    };
                }
            }
            if (isset($v['hien_thi'])) {
                $data['is_active'] = $v['hien_thi'];
            }
            if ($raw['formula']) {
                $errors[] = 'Không hỗ trợ ô công thức. Chuyển thành giá trị trước khi nhập.';
            }
            $validator = Validator::make($data, [
                'sku' => ['nullable', 'regex:/^(?!SP[0-9]+$)[a-zA-Z0-9_.-]{1,60}$/i', Rule::unique('products', 'sku')->ignore($product?->id)],
                'name' => ['required', 'string', 'min:3', 'max:180'],
                'category_id' => ['required', 'integer', Rule::exists('categories', 'id')->when((bool) ($data['is_active'] ?? $product?->is_active ?? true), fn ($rule) => $rule->where('is_active', true))],
                'price' => ['required', 'integer', 'min:1000', 'max:1000000000'],
                'stock' => ['required', 'integer', 'min:0', 'max:1000000'],
                'unit' => ['nullable', 'string', 'max:30'],
                'cost_price' => ['nullable', 'integer', 'min:0', 'max:1000000000'],
                'vat_rate' => ['nullable', 'numeric', 'min:0', 'max:100', 'decimal:0,2'],
                'barcode' => ['nullable', 'string', 'max:80', Rule::unique('products', 'barcode')->ignore($product?->id)],
                'room' => ['nullable', Rule::in(array_keys(Product::ROOMS))],
                'is_active' => ['nullable', 'boolean'],
            ], [], ['name' => 'Tên hàng', 'category_id' => 'Mã danh mục', 'price' => 'Giá bán', 'stock' => 'Tồn kho', 'vat_rate' => 'VAT', 'cost_price' => 'Giá vốn', 'sku' => 'Mã hàng']);
            $errors = [...$errors, ...$validator->errors()->all()];
            if ($code !== '' && isset($seenCodes[strtolower($code)])) {
                $errors[] = 'Mã hàng bị lặp trong sheet.';
            }
            if ($code !== '') {
                $seenCodes[strtolower($code)] = true;
            }
            if (isset($data['barcode']) && $data['barcode'] !== '') {
                if (isset($seenBarcodes[Str::lower($data['barcode'])])) {
                    $errors[] = 'Barcode bị lặp trong sheet.';
                }
                $seenBarcodes[Str::lower($data['barcode'])] = true;
            }
            if ($product) {
                $fingerprints[$product->id] = $this->fingerprint($product);
            }
            $results[] = ['line' => $raw['line'], 'code' => $code, 'name' => $data['name'], 'action' => $product ? 'update' : 'create', 'id' => $product?->id, 'data' => $data, 'errors' => $errors];
        }

        return ['rows' => $results, 'fingerprints' => $fingerprints, 'error_count' => count(array_filter($results, fn ($r) => count($r['errors']) > 0))];
    }

    public function commit(array $rawRows, array $fingerprints): array
    {
        return DB::transaction(function () use ($rawRows, $fingerprints) {
            foreach ($fingerprints as $id => $hash) {
                $product = Product::whereKey($id)->lockForUpdate()->first();
                if (! $product || ! hash_equals($hash, $this->fingerprint($product))) {
                    throw ValidationException::withMessages(['import' => 'Sản phẩm đã thay đổi sau khi kiểm tra. Kiểm tra file lại trước khi nhập để tránh ghi đè dữ liệu mới.']);
                }
            }
            $checked = $this->validateRows($rawRows);
            if (array_diff(array_keys($checked['fingerprints']), array_keys($fingerprints))) {
                throw ValidationException::withMessages(['import' => 'Mã hàng mới đã được sử dụng sau khi xem trước. Hãy kiểm tra lại file.']);
            }
            if ($checked['error_count']) {
                throw ValidationException::withMessages(['import' => 'Dữ liệu đã thay đổi hoặc có lỗi. Vui lòng kiểm tra lại.']);
            }
            $created = 0;
            $updated = 0;
            foreach ($checked['rows'] as $row) {
                $data = $row['data'];
                if ($row['id']) {
                    if (($old = Product::findOrFail($row['id']))->compare_at_price !== null && $old->compare_at_price < (int) $data['price']) {
                        $data['compare_at_price'] = null;
                    }
                    $old->update($data);
                    $updated++;
                } else {
                    Product::create($data + ['slug' => Str::slug($data['name']).'-'.Str::lower(Str::random(10)), 'is_active' => true, 'is_featured' => false, 'unit' => 'Cái', 'cost_price' => 0, 'vat_rate' => 0]);
                    $created++;
                }
            }

            return compact('created', 'updated');
        });
    }

    public function workbook(iterable $products, bool $template = false): Spreadsheet
    {
        $book = new Spreadsheet;
        $sheet = $book->getActiveSheet()->setTitle('San pham');
        $sheet->fromArray(array_values(self::HEADERS), null, 'A1');
        $i = 2;
        foreach ($products as $product) {
            $values = [$product->code, $product->name, $product->category_id, $product->category?->name ?? '', $product->barcode ?? '', $product->unit ?? 'Cái', $product->cost_price ?? 0, $product->price, $product->vat_rate ?? 0, $product->stock, $product->is_active ? 1 : 0, $product->room ?? ''];
            foreach ($values as $j => $value) {
                $sheet->setCellValueExplicit([$j + 1, $i], $value, in_array($j, [2, 6, 7, 8, 9, 10]) ? DataType::TYPE_NUMERIC : DataType::TYPE_STRING);
            }
            $i++;
        }
        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:L'.max(1, $i - 1));
        $sheet->getStyle('A1:L1')->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle('A1:L1')->getFill()->setFillType('solid')->getStartColor()->setARGB('FF24434B');
        $sheet->getRowDimension(1)->setRowHeight(28);
        foreach (range('A', 'L') as $col) {
            $sheet->getColumnDimension($col)->setWidth(in_array($col, ['B', 'D']) ? 32 : 19);
        }
        if ($i > 2) {
            $sheet->getStyle('G2:H'.($i - 1))->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('I2:I'.($i - 1))->getNumberFormat()->setFormatCode('0.##');
        }
        $help = $book->createSheet()->setTitle('Huong dan');
        $help->fromArray([
            ['HƯỚNG DẪN NHẬP SẢN PHẨM'],
            ['Giữ nguyên tiêu đề. Chọn sheet San pham khi nhập; tối đa 2.000 dòng.'],
            ['Bắt buộc: Tên hàng, Mã danh mục, Giá bán, Tồn kho.'],
            ['Mã hàng trống: thêm mới. Mã hàng có sẵn: cập nhật. Mã SP tự sinh không được tạo thủ công.'],
            ['Hiển thị: 1 = đang bán, 0 = ẩn. Giá bán là giá cuối hiển thị; VAT chỉ là thông tin quản lý.'],
            ['Phòng: living, bedroom, dining, office, decor; có thể bỏ trống.'],
            ['Không dùng công thức. Barcode và mã hàng nên định dạng Text để giữ số 0 đầu.'],
            ['Nhập cập nhật chỉ thay các cột có trong file; ảnh, mô tả và gallery được giữ.'],
        ]);
        $help->getColumnDimension('A')->setWidth(115);
        $categories = $book->createSheet()->setTitle('Danh muc');
        $categories->fromArray([['Mã danh mục', 'Tên danh mục']]);
        foreach (Category::orderBy('id')->get() as $i => $category) {
            $categories->setCellValueExplicit([1, $i + 2], $category->id, DataType::TYPE_NUMERIC);
            $categories->setCellValueExplicit([2, $i + 2], $category->name, DataType::TYPE_STRING);
        }
        $categories->getColumnDimension('A')->setWidth(20);
        $categories->getColumnDimension('B')->setWidth(35);
        $book->setActiveSheetIndex(0);

        return $book;
    }
}
