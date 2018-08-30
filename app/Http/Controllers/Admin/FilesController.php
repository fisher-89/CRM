<?php

namespace App\Http\Controllers\Admin;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FilesController extends Controller
{
    public function index(Request $request)
    {
        if ($request->isMethod('post')) {
            if (empty($_FILES['file']))
                abort(400, '文件接收失败');
            $this->verifyFile($request);
            $files = $request->file('file');
            $this->checkFile($files);
            return $this->storageName($files, $request->user()->staff_sn);
        }
    }

    /**
     * 是否接收到和文件类型判断
     * @param $file
     */
    private function checkFile($file)
    {
        $n = 0;
        foreach ($file as $key => $value) {
            $n++;
            if(!$value->isValid()){
                abort(400,'文件上传失败');
            }
            $typeList = ['jpg', 'JPG', 'png', 'PNG', 'gif', 'GIF', 'jpeg', 'JPEG'];
            if (!in_array($value->getClientOriginalExtension(), $typeList)) {
                abort(400, '第' . $n . '个文件类型错误');
            }
            if ($value->getClientSize() > 4194304) {
                abort(400, '第' . $n . '个文件超4MB');
            }
//            $size[]=$value->getClientSize();
        }
    }

    /**
     * 保存文件  返回路径
     *
     * @param $dir
     * @return string
     */
    private function storageName($file, $staff_sn)
    {
        foreach ($file as $k => $v) {
            $exe = $v->getClientOriginalExtension();
            $fileName = md5(microtime() . $v->getClientOriginalName() . $staff_sn);
            $bool = Storage::disk('public')->put('temporary/' . $fileName . '.' . $exe,
                file_get_contents($v->getRealPath()));
            if ((bool)$bool == false) {
                abort(400, '文件上传失败');
            }
            $path[] = 'http://'.$_SERVER['HTTP_HOST'].'/storage/temporary/'. $fileName . '.' . $exe;
        }
        return isset($path) ? $path : [];
    }

    private function verifyFile($request)
    {
        $this->validate($request,
            [
                'file.*'=>'file|image|max:4194304'
            ],[],[
                'file'=>'文件'
            ]
        );
    }
}