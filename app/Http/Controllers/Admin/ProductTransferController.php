<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\ProductCatalogQuery;
use App\Services\ProductWorkbook;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ProductTransferController extends Controller
{
    private function staged(Request $request, string $token): array
    {
        $meta = $request->session()->get('product_imports.'.$token);
        if (! $meta || $meta['owner'] !== $request->user()->id || $meta['expires'] < time()) {
            throw ValidationException::withMessages(['file' => 'Phiên nhập đã hết hạn. Vui lòng chọn lại file.']);
        }

        return $meta;
    }

    public function scan(Request $request, ProductWorkbook $workbook)
    {
        $request->validate(['file' => ['required', 'file', 'mimes:xlsx', 'max:5120']]);
        foreach (Storage::disk('local')->files('imports/'.$request->user()->id) as $temporary) {
            if (Storage::disk('local')->lastModified($temporary) < time() - 3600) {
                Storage::disk('local')->delete($temporary);
            }
        }
        $sheets = $workbook->inspect($request->file('file')->getRealPath());
        $imports = $request->session()->get('product_imports', []);
        while (count($imports) >= 4) {
            $old = array_shift($imports);
            Storage::disk('local')->delete(array_filter([$old['path'], $old['preview'] ?? null]));
        }
        $token = (string) Str::uuid();
        $path = $request->file('file')->store('imports/'.$request->user()->id, 'local');
        if (! $path) {
            throw ValidationException::withMessages(['file' => 'Không lưu được file nhập.']);
        }
        $imports[$token] = ['owner' => $request->user()->id, 'path' => $path, 'expires' => time() + 3600, 'sheets' => $sheets];
        $request->session()->put('product_imports', $imports);

        return response()->json(['token' => $token, 'sheets' => $sheets]);
    }

    public function preview(Request $request, ProductWorkbook $workbook)
    {
        $data = $request->validate(['token' => ['required', 'uuid'], 'sheet' => ['required', 'string', 'max:100']]);
        $meta = $this->staged($request, $data['token']);
        if (! in_array($data['sheet'], $meta['sheets'], true)) {
            throw ValidationException::withMessages(['sheet' => 'Sheet không hợp lệ.']);
        }
        $rows = $workbook->readRows(Storage::disk('local')->path($meta['path']), $data['sheet']);
        $checked = $workbook->validateRows($rows);
        $preview = $meta['path'].'.json';
        if (! Storage::disk('local')->put($preview, json_encode(['rows' => $rows, 'fingerprints' => $checked['fingerprints']], JSON_THROW_ON_ERROR))) {
            throw ValidationException::withMessages(['file' => 'Không lưu được kết quả kiểm tra.']);
        }
        $meta['preview'] = $preview;
        $meta['valid'] = $checked['error_count'] === 0;
        $meta['sheet'] = $data['sheet'];
        unset($meta['completed']);
        $request->session()->put('product_imports.'.$data['token'], $meta);

        return response()->json(['total' => count($checked['rows']), 'errors' => $checked['error_count'],
            'created' => count(array_filter($checked['rows'], fn ($r) => $r['action'] === 'create')),
            'updated' => count(array_filter($checked['rows'], fn ($r) => $r['action'] === 'update')),
            'rows' => array_map(fn ($r) => array_intersect_key($r, array_flip(['line', 'code', 'name', 'action', 'errors'])) + ['price' => (int) $r['data']['price'], 'stock' => (int) $r['data']['stock']], array_slice($checked['rows'], 0, 100))]);
    }

    public function commit(Request $request, ProductWorkbook $workbook)
    {
        $data = $request->validate(['token' => ['required', 'uuid']]);
        $meta = $this->staged($request, $data['token']);
        if (isset($meta['completed'])) {
            return response()->json($meta['completed']);
        }
        if (empty($meta['valid']) || empty($meta['preview'])) {
            throw ValidationException::withMessages(['file' => 'Cần kiểm tra dữ liệu và sửa hết lỗi trước khi nhập.']);
        }
        $preview = json_decode(Storage::disk('local')->get($meta['preview']), true, 512, JSON_THROW_ON_ERROR);
        $result = $workbook->commit($preview['rows'], $preview['fingerprints']);
        $meta['completed'] = $result;
        $request->session()->put('product_imports.'.$data['token'], $meta);
        Storage::disk('local')->delete([$meta['path'], $meta['preview']]);
        $request->session()->flash('success', 'Đã nhập Excel: thêm '.($result['created']).' và cập nhật '.($result['updated']).' sản phẩm.');

        return response()->json($result + ['message' => 'Đã nhập sản phẩm thành công.']);
    }

    public function export(Request $request, ProductCatalogQuery $catalog, ProductWorkbook $workbook)
    {
        $data = $request->validate(['scope' => ['required', 'in:filtered,selected'], 'ids' => ['required_if:scope,selected', 'array', 'max:500'], 'ids.*' => ['integer', 'distinct', 'exists:products,id']]);
        $query = $data['scope'] === 'selected' ? Product::with('category')->whereIn('id', $data['ids'])->orderBy('id') : $catalog->query($catalog->filters($request));
        $products = $query->limit(5001)->get();
        if ($products->count() > 5000) {
            throw ValidationException::withMessages(['export' => 'Tối đa 5.000 sản phẩm mỗi lần xuất. Thu hẹp bộ lọc.']);
        }

        return $this->download($workbook->workbook($products), 'san-pham-'.now()->format('Ymd-His').'.xlsx');
    }

    public function template(ProductWorkbook $workbook)
    {
        return $this->download($workbook->workbook([], true), 'mau-nhap-san-pham.xlsx');
    }

    private function download($book, string $name)
    {
        return response()->streamDownload(function () use ($book) {
            try {
                (new Xlsx($book))->save('php://output');
            } finally {
                $book->disconnectWorksheets();
            }
        }, $name, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    public function bulk(Request $request)
    {
        $data = $request->validate(['action' => ['required', 'in:show,hide'], 'ids' => ['required', 'array', 'min:1', 'max:500'], 'ids.*' => ['required', 'integer', 'distinct', 'exists:products,id']]);
        Product::whereIn('id', $data['ids'])->update(['is_active' => $data['action'] === 'show']);

        return back()->with('success', 'Đã cập nhật trạng thái '.$count = count($data['ids']).' sản phẩm.');
    }
}
