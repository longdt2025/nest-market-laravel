<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Backup;
use App\Services\BackupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class BackupController extends Controller
{
    public function index()
    {
        return view('system.backups.index', ['backups' => Backup::latest()->paginate(12)]);
    }

    public function store(Request $request, BackupService $service)
    {
        $data = $request->validate(['label' => ['required', 'string', 'max:120']]);
        try {
            $service->create($request->user(), $data['label']);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);

            return back()->withErrors(['backup' => 'Không lưu được bản sao lưu. Kiểm tra MinIO và thử lại. Dữ liệu hiện tại không thay đổi.']);
        }

        return back()->with('success', 'Đã sao lưu dữ liệu và ảnh upload lên MinIO.');
    }

    public function show(Backup $backup, BackupService $service)
    {
        try {
            $data = $service->read($backup);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('system.backups.index')->withErrors(['backup' => 'Không đọc được bản sao lưu từ MinIO.']);
        }

        return view('system.backups.show', ['backup' => $backup, 'counts' => array_map('count', $data['tables']), 'fileCount' => count($data['files'])]);
    }

    public function download(Backup $backup)
    {
        try {
            return Storage::disk($backup->disk)->download($backup->path, 'nest-backup-'.$backup->id.'.nestbackup', ['Content-Type' => 'application/octet-stream']);
        } catch (\Throwable $e) {
            report($e);

            return back()->withErrors(['backup' => 'Không tải được bản sao lưu. Kiểm tra MinIO.']);
        }
    }

    public function restore(Request $request, Backup $backup, BackupService $service)
    {
        $request->validate(['current_password' => ['required', 'current_password'], 'confirmation' => ['required', 'in:PHUC HOI '.$backup->id]]);
        try {
            $service->restore($backup, $request->user());
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);

            return back()->withErrors(['backup' => 'Phục hồi không thành công. Dữ liệu database đã được rollback; xem log để kiểm tra.']);
        }
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Đã phục hồi. Đăng nhập bằng tài khoản và mật khẩu tại thời điểm sao lưu.');
    }
}
