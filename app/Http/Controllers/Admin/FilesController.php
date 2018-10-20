<?php

namespace App\Http\Controllers\Admin;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManagerStatic;

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
     *http://112.74.177.132:8003/storage/temporary/5501537407988119462.jpg
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
        return config('app.url') . '/storage/temporary/' . $fileName . '.' . $exe;
    }

    private function verifyFile($request)
    {
        $this->validate($request,
            [
                'file' => 'required|file|max:4096|mimes:png,gif,jpeg,txt,pdf,doc,docx,xls,xlsx'
            ], [], [
                'file' => '附件'
            ]
        );
    }

    public function iconImage(Request $request)
    {
        $this->imageVerify($request);
        $files = $request->file('iconImage');
        if($files == null){
            abort(400, '未找到文件');
        }
        if (!$files->isValid()) {
            abort(400, '文件上传失败');
        }
        $exe = $files->extension();
        $fileName = rand(99, 999) . time() . $request->user()->staff_sn;
        $path = Storage::url($files->storeAs('temporary', $fileName . '.' . $exe, 'public'));
        $age = ImageManagerStatic::make($files->getRealPath())->resize(96,96);
        $age ->save(public_path('storage/temporary/'.$fileName . '_thumb.'. $exe));
        if ($path == false) {
            abort(400, '文件上传失败');
        }
        return config('app.url') . '/storage/temporary/' . $fileName . '.' . $exe;
    }

    protected function imageVerify($request)
    {
        $this->validate($request,[
                'iconImage' => 'file|max:4096|image'
//                    |dimensions:width=180,height=180
            ],[],[
                'iconImage' => '头像'
            ]
        );
    }

    public function cardImage(Request $request)
    {
        $this->cardVerify($request);
        $files = $request->file('cardImage');
        if($files == null){
            abort(400, '未找到文件');
        }
        if (!$files->isValid()) {
            abort(400, '文件上传失败');
        }
        $exe = $files->getClientOriginalExtension();
        $fileName = rand(99, 999) . time() . $request->user()->staff_sn;
        $path = Storage::url($files->storeAs('temporary', $fileName . '.' . $exe, 'public'));
        if ($path == false) {
            abort(400, '文件上传失败');
        }
        return config('app.url') . '/storage/temporary/' . $fileName . '.' . $exe;
    }

    protected function cardVerify($request)
    {
        $this->validate($request,[
            'cardImage' => 'required|file|max:4096|mimes:png,gif,jpeg'
        ],[],[
                'cardImage' => '身份证照片'
            ]
        );
    }
}