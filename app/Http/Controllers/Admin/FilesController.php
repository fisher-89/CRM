<?php

namespace App\Http\Controllers\Admin;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FilesController extends Controller
{
    public function index(Request $request)
    {
        $this->verifyFile($request);
        $files = $request->file('file');
        if (!$files->isValid()) {
            abort(400, '文件上传失败');
        }
        return $this->storageName($files, $request->user()->staff_sn);
    }

    /**
     * 保存文件  返回路径
     *
     * @param $dir
     * @return string
     */
    private function storageName($file, $staff_sn)
    {
        $exe = $file->getClientOriginalExtension();
        $fileName = rand(99, 999) . time() . $staff_sn;
        $path = Storage::url($file->storeAs('temporary', $fileName . '.' . $exe, 'public'));
        if ($path == false) {
            abort(400, '文件上传失败');
        }
        return url()->asset($path);
    }

    private function verifyFile($request)
    {
        $this->validate($request,
            [
                'file' => 'required|file|max:4096|mimes:png,gif,jpeg,txt,pdf,doc,docx'
            ], [], [
                'file' => '文件'
            ]
        );
    }
}