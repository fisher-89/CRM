<?php

namespace App\Services\Admin;

use App\Models\NoteLogs;
use App\Models\Notes;
use Illuminate\Http\Request;

class NoteLogsService
{
    use Traits\Filterable, Traits\GetInfo;

    protected $notes;
    protected $noteLogsModel;

    public function __construct(NoteLogs $noteLogs, Notes $notes)
    {
        $this->notes = $notes;
        $this->noteLogsModel = $noteLogs;
    }

    public function getList($request,$obj)
    {
        foreach ($obj as $item){
            $data[]=$item->auth_brand;
        }
        $bool = isset($data) ? $data : [] ;
        $arr = array_filter($bool);
        return $this->noteLogsModel->orderBy('id', 'desc')->with('notes')->whereHas('notes.clients.Brands',function ($query)use($arr){
            $query->whereIn('brand_id',$arr);
        })->filterByQueryString()->withPagination($request->get('pagesize', 10));
    }

    /**
     * 从来不写注释，只有我和天知道写的是什么，一个礼拜后只有天知道写的是什么
     *
     * @param $request
     */
    public function restoreNote($request)
    {
        $id = $request->route('id');
        $noteLogs = $this->noteLogsModel->where('id', $id)->first();
        if (false === (bool)$noteLogs) {
            abort(404, '未找到数据');
        }
        $attachments = $noteLogs->changes['attachments'];
        if (true === (bool)$attachments) {
            $this->restoreFiles($attachments);
        }
        $this->restoreDelNote($noteLogs->note_id);
        $noteData = $this->notes->find($noteLogs->note_id);
        if ((bool)$noteData === false) {
            abort(500, '未知错误');
        }
        $arr = $noteData->update($noteLogs->changes);
        $this->saveNote($request, $noteLogs);
        return response()->json($arr, 201);
    }

//还原写入记录表
    protected function saveNote($request, $noteLog)
    {
        $logSql = [
            'note_id' => $noteLog->note_id,
            'type' => '还原到' . $noteLog->id,
            'staff_sn' => $request->user()->staff_sn,
            'staff_name' => $request->user()->realname,
            'operation_address' => [
                '电话号码' => $this->getOperation(),
                '设备类型' => $this->getPhoneType(),
                'IP地址' => $request->getClientIp()
            ],
            'changes' => $noteLog->changes,
        ];
        $this->noteLogsModel->create($logSql);
    }

    protected function restoreFiles($attachments)
    {
        try {
            if (is_array($attachments)) {
                foreach ($attachments as $key => $value) {
                    $getFileName = basename($value);
                    $src = storage_path() . '/app/public/abandon/' . $getFileName;
                    $dst = storage_path() . '/app/public/uploads/' . $getFileName;
                    copy($src, $dst);
                    unlink($src);
                }
            } else {
                $getFileName = basename($attachments);
                $src = storage_path() . '/app/public/abandon/' . $getFileName;
                $dst = storage_path() . '/app/public/uploads/' . $getFileName;
                copy($src, $dst);
                unlink($src);
            }
        } catch (\Exception $e) {
            abort(500, '附件未找到');
        }
    }

    protected function restoreDelNote($note_id)
    {
        $data = $this->notes->find($note_id);
        if ((bool)$data === false) {
            $restore = $this->notes->where('id', $note_id)->withTrashed()->first();
            true === (bool)$restore ? $this->notes->where('id', $note_id)->restore() :
                abort(404, '还原失败,找不到还原对象');
        }
    }
}