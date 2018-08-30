<?php

namespace App\Http\Controllers\Admin;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FilesController extends Controller
{
    public function index(Request $request)
    {
        if (empty($_FILES['file']))
            abort(400, '文件接收失败');
        $this->verifyFile($request);
        $files = $request->file('file');
        if (!$files->isValid()) {
            abort(400, '文件上传失败');
        }
        if ($files->getClientSize() > 4194304) {
            abort(400, '文件超4MB');
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
        $fileName = md5(microtime() . $file->getClientOriginalName() . $staff_sn);
        $bool = Storage::disk('public')->put('temporary/' . $fileName . '.' . $exe,
            file_get_contents($file->getRealPath()));
        if ((bool)$bool == false) {
            abort(400, '文件上传失败');
        }
        $path[] = 'http://' . $_SERVER['HTTP_HOST'] . '/storage/temporary/' . $fileName . '.' . $exe;
        return $path;
    }

    private function verifyFile($request)
    {
        $this->validate($request,
            [
                'file' => 'file|max:4194304|mimes:png,gif,jpeg,txt,pdf,doc,docx'
            ], [], [
                'file' => '文件'
            ]
        );
    }
}