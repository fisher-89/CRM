<?php

namespace App\Services\Admin;

use App\Models\AuthorityGroups;
use App\Models\NoteGroupStaff;
use App\Models\NoteLogs;
use App\Models\Notes;
use App\Models\NoteTypes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class NoteService
{
    use Traits\Filterable, Traits\GetInfo;

    protected $noteModel;
    protected $noteLogsModel;
    protected $noteTypesModel;

    public function __construct(Notes $notes, NoteTypes $noteTypes, NoteLogs $noteLogs)
    {
        $this->noteModel = $notes;
        $this->noteLogsModel = $noteLogs;
        $this->noteTypesModel = $noteTypes;
    }

    public function getListType($request)
    {
        return $this->noteTypesModel->orderBy('sort', 'asc')->filterByQueryString()->withPagination($request->get('pagesize', 10));
    }

    public function addNoteType($request)
    {
        $all = $request->all();
        $this->noteTypesModel->name = $all['name'];
        $this->noteTypesModel->sort = $all['sort'];
        $this->noteTypesModel->is_task = $all['is_task'];
        $this->noteTypesModel->save();
        return response()->json($this->noteTypesModel, 201);
    }

    public function editNoteType($request)
    {
        $note = $this->noteTypesModel->find($request->route('id'));
        if (false === (bool)$note) {
            abort(404, '未找到数据');
        }
        $note->update($request->all());
        return response()->json($note, 201);
    }

    public function delNoteType($request)
    {
        $noteType = $this->noteTypesModel->find($request->route('id'));
        $noteType->delete();
        return response('', 204);
    }

    public function getList($request, $obj)
    {
        foreach ($obj as $item) {
            $brand_id[] = $item->auth_brand;
        }
        $arr = isset($brand_id) ? $brand_id : [];
        return $this->noteModel->whereHas('clients.Brands', function ($query) use ($arr) {
            $query->whereIn('brand_id', $arr);
        })->orderBy('id', 'asc')->filterByQueryString()->withPagination($request->get('pagesize', 10));
    }

    public function addNote($request)
    {
        $note = $this->noteModel;
        $note->note_type_id = $request->note_type_id;
        $note->client_id = $request->client_id;
        $note->took_place_at = $request->took_place_at;
        $note->recorder_sn = $request->user()->staff_sn;
        $note->recorder_name = $request->user()->realname;
        $note->title = $request->title;
        $note->content = $request->content;
        $note->attachments = $this->fileDispose($request);
        $note->task_deadline = $request->task_deadline;
        $note->finished_at = $request->finished_at;
        $note->task_result = $request->task_result;
        $note->save();
        $this->saveLogs($request, '后台添加', $note->id, $note);
        return response()->json($note, 201);
    }

    public function editNote($request)
    {
        $id = $request->id;
        $note = $this->noteModel->find($id);
        if (false == (bool)$note) {
            abort(404, '未找到数据');
        }
        if (true === (bool)$note->attachments) {
            try {
                if (is_array($note->attachments)) {
                    foreach ($note->attachments as $key => $value) {
                        $fileName = basename($value);
                        copy(storage_path() . '/app/public/uploads/' . $fileName, storage_path() . '/app/public/abandon/' . $fileName);
                        unlink(storage_path() . '/app/public/uploads/' . $fileName);
                    }
                } else {
                    $fileName = basename($note->attachments);
                    copy(storage_path() . '/app/public/uploads/' . $fileName, storage_path() . '/app/public/abandon/' . $fileName);
                    unlink(storage_path() . '/app/public/uploads/' . $fileName);
                }
            } catch (\Exception $e) {
                abort(500, '修改失败');
            }
        }
        $noteSql = [
            'note_type_id' => $request->note_type_id,
            'client_id' => $request->client_id,
            'took_place_at' => $request->took_place_at,
            'recorder_sn' => $request->user()->staff_sn,
            'recorder_name' => $request->user()->realname,
            'title' => $request->title,
            'content' => $request->content,
            'attachments' => $this->fileDispose($request),
            'task_deadline' => $request->task_deadline,
            'finished_at' => $request->finished_at,
            'task_result' => $request->task_result,
        ];
        $note->update($noteSql);
        $this->saveLogs($request, '后台修改', $id, $note);
        return response()->json($note, 201);
    }

    public function delNote($request)
    {
        $id = $request->route('id');
        $note = $this->noteModel->find($id);
        if ((bool)$note == false) {
            abort(404, '未找到数据');
        }
        if (true === (bool)$note->attachments) {
            try {
                if (is_array($note->attachments)) {
                    foreach ($note->attachments as $key => $value) {
                        $fileName = basename($value);
                        copy(storage_path() . '/app/public/uploads/' . $fileName, storage_path() . '/app/public/abandon/' . $fileName);
                        unlink(storage_path() . '/app/public/uploads/' . $fileName);
                    }
                } else {
                    $fileName = basename($note->attachments);
                    copy(storage_path() . '/app/public/uploads/' . $fileName, storage_path() . '/app/public/abandon/' . $fileName);
                    unlink(storage_path() . '/app/public/uploads/' . $fileName);
                }
            } catch (\Exception $e) {
                abort(500, '删除失败');
            }
        }
        $note->delete();
        $this->saveLogs($request, '后台删除', $id, $note);
        return response('', 204);
    }

    public function getDetail($request,$obj)
    {
        foreach ($obj as $item) {
            $brand_id[] = $item->auth_brand;
        }
        $arr = isset($brand_id) ? $brand_id : [];
        return $this->noteModel->where('id', $request->route('id'))->whereHas('clients.Brands', function ($query) use ($arr) {
            $query->whereIn('brand_id', $arr);
        })->with('clients')->with('noteType')->first();
    }

    /**
     * w文件处理，返回路径
     *
     * @param $request
     * @return array|string
     */
    public function fileDispose($request)
    {
        $file = $request->attachments;
        if ($file == true) {
            try {
                if (is_array($file)) {
                    foreach ($file as $key => $value) {
                        $getFileName = basename($value);
                        $src = storage_path() . '/app/public/temporary/' . $getFileName;
                        $dst = storage_path() . '/app/public/uploads/' . $getFileName;
                        copy($src, $dst);
                        unlink($src);
                        $arr[] = '/storage/uploads/' . $getFileName;
                    }
                    return $arr;
                } else {
                    $getFileName = basename($file);
                    $src = storage_path() . '/app/public/temporary/' . $getFileName;
                    $dst = storage_path() . '/app/public/uploads/' . $getFileName;
                    copy($src, $dst);
                    unlink($src);
                    return '/storage/uploads/' . $getFileName;
                }
            } catch (\Exception $e) {
                abort(500, '操作失败');
            }
        }
        return '';
    }

    protected function saveLogs($request, $type, $id, $arr)
    {
        $logSql = [
            'note_id' => $id,
            'type' => $type,
            'staff_sn' => $request->user()->staff_sn,
            'staff_name' => $request->user()->realname,
            'operation_address' => [
                '电话号码' => $this->getOperation(),
                '设备类型' => $this->getPhoneType(),
                'IP地址' => $request->getClientIp()
            ],
            'changes' => $arr,
        ];
        $this->noteLogsModel->create($logSql);
    }
}