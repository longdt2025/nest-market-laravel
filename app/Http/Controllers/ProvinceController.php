<?php
namespace App\Http\Controllers;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;
class ProvinceController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            $provinces = Cache::remember('nest_provinces_v2', now()->addDay(), function (): array {
                $response = Http::acceptJson()->timeout(5)->get('https://provinces.open-api.vn/api/v2/');
                $response->throw();
                $data = $response->json();
                if (!is_array($data) || !array_is_list($data)) throw new \RuntimeException('Invalid province API response');
                return collect($data)->pluck('name')->filter(fn($name) => is_string($name))->values()->all();
            });
            return response()->json(['provinces'=>$provinces]);
        } catch (Throwable $e) {
            report($e);
            return response()->json(['message'=>'Không tải được gợi ý tỉnh/thành. Bạn vẫn có thể nhập địa chỉ thủ công.'],503);
        }
    }
}
