<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderCatalogQuery;
use App\Services\OrderWorkflow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class OrderController extends Controller
{
    public function index(Request $request, OrderCatalogQuery $catalog): View
    {
        $filters = $catalog->filters($request);
        $orders = $catalog->sorted($filters)->paginate($filters['per_page'] ?? 25)->withQueryString();
        $statusCounts = $catalog->query($filters, false)->reorder()->selectRaw('status, count(*) as aggregate')->groupBy('status')->pluck('aggregate', 'status');
        $summary = $catalog->query($filters)->reorder()->selectRaw("count(*) as orders_count, coalesce(sum(total),0) as total_value, coalesce(sum(case when status = 'delivered' then total else 0 end),0) as delivered_value, coalesce(sum(case when status in ('pending','confirmed','shipping') then total else 0 end),0) as processing_value")->first();

        return view('admin.orders.index', compact('orders', 'filters', 'statusCounts', 'summary'));
    }

    public function show(Request $request, Order $order): View
    {
        $order->load(['items.product', 'user', 'events.actor']);

        return view($request->boolean('panel') ? 'admin.orders.detail' : 'admin.orders.show', compact('order'));
    }

    public function print(Order $order): View
    {
        $order->load(['items', 'user']);

        return view('admin.orders.print', compact('order'));
    }

    public function export(Request $request, OrderCatalogQuery $catalog)
    {
        $data = $request->validate(['scope' => ['required', 'in:filtered,selected'], 'ids' => ['required_if:scope,selected', 'array', 'max:500'], 'ids.*' => ['integer', 'distinct', 'exists:orders,id']]);
        $filters = $catalog->filters($request);
        $query = $data['scope'] === 'selected'
            ? Order::with('user')->withCount('items')->whereIn('id', $data['ids'])->orderByDesc('id')
            : $catalog->sorted($filters);
        abort_if((clone $query)->count() > 5000, 422, 'Mỗi lần xuất tối đa 5.000 đơn hàng.');
        $orders = $query->get();

        return response()->streamDownload(function () use ($orders) {
            $book = new Spreadsheet;
            $sheet = $book->getActiveSheet()->setTitle('Don hang');
            $sheet->fromArray(['Mã đơn', 'Ngày tạo', 'Khách hàng', 'Điện thoại', 'Email', 'Địa chỉ', 'Trạng thái', 'Số mặt hàng', 'Tạm tính', 'Phí giao hàng', 'Tổng tiền', 'Thanh toán', 'Ghi chú']);
            foreach ($orders as $i => $order) {
                $values = [$order->code, $order->created_at->format('d/m/Y H:i'), $order->recipient_name, $order->phone, $order->user?->email, $order->shipping_address, $order->status_label, $order->items_count, $order->subtotal, $order->shipping_fee, $order->total, $order->payment_method_label.' / '.$order->payment_label, $order->note];
                foreach ($values as $j => $value) {
                    $sheet->setCellValueExplicit([$j + 1, $i + 2], $value ?? '', in_array($j, [7, 8, 9, 10]) ? DataType::TYPE_NUMERIC : DataType::TYPE_STRING);
                }
            }
            $sheet->freezePane('A2');
            $sheet->setAutoFilter('A1:M'.($orders->count() + 1));
            $sheet->getStyle('A1:M1')->getFont()->setBold(true);
            foreach (range('A', 'M') as $column) {
                $sheet->getColumnDimension($column)->setWidth(in_array($column, ['F', 'M']) ? 40 : 23);
            }
            $sheet->getStyle('I2:K'.max(2, $orders->count() + 1))->getNumberFormat()->setFormatCode('#,##0');
            (new Xlsx($book))->save('php://output');
            $book->disconnectWorksheets();
        }, 'don-hang-'.now()->format('Ymd-His').'.xlsx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'Cache-Control' => 'private, no-store']);
    }

    public function update(Request $request, Order $order, OrderWorkflow $workflow): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', 'in:confirmed,shipping,delivered,cancelled'], 'cancel_reason' => ['nullable', 'string', 'max:500']]);
        $workflow->changeStatus($order, $data['status'], $request->user(), $data['cancel_reason'] ?? null);

        return back()->with('success', 'Đã cập nhật trạng thái đơn hàng.');
    }
}
