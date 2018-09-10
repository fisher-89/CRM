<?php

namespace App\Services\Admin;

use App\Http\Resources\NoteLogCollection;
use App\Models\NoteLogs;
use App\Models\Notes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
            foreach ($item['visibles'] as $key => $value) {
                $brandId[] = $value['brand_id'];
            }
        }
        $arr =  isset($brandId) ? array_unique(array_filter($brandId)) : [];
        $list = $this->noteLogsModel->orderBy('id', 'desc')->with('notes')
            ->whereHas('notes.Brands',function ($query)use($arr){
            $query->whereIn('brand_id',$arr);
        })->filterByQueryString()->withPagination($request->get('pagesize', 10));
        if (isset($list['data'])) {
            $list['data'] = new NoteLogCollection(collect($list['data']));
            return $list;
        } else {
            return new NoteLogCollection($list);
        }
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
                    $src = '/uploads/' . $getFileName;
                    $dst = '/abandon/' . $getFileName;
                    if(Storage::exists($src)) {
                        Storage::disk('public')->move($src, $dst);
                    }
                }
            } else {
                $getFileName = basename($attachments);
                $src = '/uploads/' . $getFileName;
                $dst = '/abandon/' . $getFileName;
                if(Storage::exists($src)) {
                    Storage::disk('public')->move($src, $dst);
                }
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