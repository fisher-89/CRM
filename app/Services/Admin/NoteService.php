<?php

namespace App\Services\Admin;

use App\Http\Resources\NoteCollection;
use App\Models\AuthorityGroups;
use App\Models\AuthGroupHasStaff;
use App\Models\NoteHasBrand;
use App\Models\NoteLogs;
use App\Models\Notes;
use DB;
use App\Models\NoteTypes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class NoteService
{
    use Traits\Filterable, Traits\GetInfo;

    protected $noteModel;
    protected $noteHasBrand;
    protected $noteLogsModel;
    protected $noteTypesModel;

    public function __construct(Notes $notes, NoteTypes $noteTypes, NoteLogs $noteLogs, NoteHasBrand $noteHasBrand)
    {
        $this->noteModel = $notes;
        $this->noteLogsModel = $noteLogs;
        $this->noteTypesModel = $noteTypes;
        $this->noteHasBrand = $noteHasBrand;
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
            foreach ($item['visibles'] as $key => $value) {
                $brand_id[] = $value['brand_id'];
            }
        }
        $arr = isset($brand_id) ? array_unique(array_filter($brand_id)) : [];
        $list = $this->noteModel->whereHas('Brands', function ($query) use ($arr) {
            $query->whereIn('brand_id', $arr);
        })->with('Brands')->filterByQueryString()->SortByQueryString()
            ->withPagination($request->get('pagesize', 10));
        if (isset($list['data'])) {
            $list['data'] = new NoteCollection(collect($list['data']));
            return $list;
        } else {
            return new NoteCollection($list);
        }
    }

    public function addNote($request)
    {
//        try {
//            DB::beginTransaction();
        $note = $this->noteModel;
        $note->note_type_id = $request->note_type_id;
        $note->client_id = $request->client_id;
        $note->client_name = $request->client_name;
        $note->took_place_at = $request->took_place_at;
        $note->recorder_sn = $request->user()->staff_sn;
        $note->recorder_name = $request->user()->realname;
        $note->title = $request->title;
        $note->content = $request->content;
        $note->attachments = $this->fileDispose($request->attachments);
        $note->task_deadline = $request->task_deadline;
        $note->finished_at = $request->finished_at;
        $note->task_result = $request->task_result;
        $note->save();
        foreach ($request->brands as $items) {
            $brandSql = [
                'note_id' => $note->id,
                'brand_id' => $items,
            ];
            $this->noteHasBrand->create($brandSql);
        }
//            DB::commit();
//        } catch (\Exception $e) {
//            DB::rollback();
//            abort(400, '事件添加失败');
//        }
        $data = $note->where('id', $note->id)->first();
        $data['brands'] = $request->brands;
        return response()->json($data, 201);
    }

    public function editNote($request)
    {
        $id = $request->id;
        $note = $this->noteModel->with('Brands')->find($id);
        if (false == (bool)$note) {
            abort(404, '未找到数据');
        }
//        try {
//            DB::beginTransaction();
        if (true === (bool)$note->attachments) {
            $this->fileDiscard($note->attachments);
        }
        $noteSql = [
            'note_type_id' => $request->note_type_id,
            'client_id' => $request->client_id,
            'client_name' => $request->client_name,
            'took_place_at' => $request->took_place_at,
            'recorder_sn' => $request->user()->staff_sn,
            'recorder_name' => $request->user()->realname,
            'title' => $request->title,
            'content' => $request->content,
            'attachments' => $this->fileDispose($request->attachments),
            'task_deadline' => $request->task_deadline,
            'finished_at' => $request->finished_at,
            'task_result' => $request->task_result,
        ];
        $notes = clone $note;
        $note->update($noteSql);
        $this->noteHasBrand->where('note_id', $id)->delete();
        foreach ($request->brands as $items) {
            $noteHasBrandSql = [
                'note_id' => $id,
                'brand_id' => $items
            ];
            $this->noteHasBrand->create($noteHasBrandSql);
        }
        $this->saveLogs($request, $notes, '后台修改');
//            DB::commit();
//        } catch (\Exception $e) {
//            DB::rollback();
//            abort(400, '事件修改失败');
//        }
        $data = $note->where('id', $note->id)->first();
        $data['brands'] = $request->brands;
        return response()->json($data, 201);
    }

    public function delNote($request)
    {
        $id = $request->route('id');
        $note = $this->noteModel->find($id);
        if ((bool)$note == false) {
            abort(404, '未找到数据');
        }
        if (true === (bool)$note->attachments) {
            $this->fileDiscard($note->attachments);
        }
        $note->delete();
        $this->saveLogs($request, $note, '后台删除');
        return response('', 204);
    }

    public function getDetail($request, $arr)
    {
        $data = $this->noteModel->where('id', $request->route('id'))
            ->whereHas('Brands', function ($query) use ($arr) {
                $query->whereIn('brand_id', $arr);
            })->with(['noteType', 'Brands'])->first();
        if (!empty($data)) {
            $data = $data->toArray();
            $brand = [];
            foreach ($data['brands'] as $items) {
                $brand[] = $items['brand_id'];
            }
            $data['brands'] = $brand;
            return $data;
        }
    }

    /**
     * 文件处理，返回路径
     *
     * @param $request
     * @return array|string
     */
    public function fileDispose($file)
    {
        if ($file == true) {
            if (is_array($file)) {
                $url = [];
                foreach ($file as $key => $value) {
                    $getFileName = basename($value);
                    $src = '/temporary/' . $getFileName;
                    $dst = '/uploads/' . $getFileName;
                    if(Storage::disk('public')->exists($src)) {
                        Storage::disk('public')->move($src, $dst);
                    }else{
                        abort(500,'文件未找到');
                    }
                    $url[] = env('APP_URL') . '/storage' . $dst;
                }
                return $url;
            } else {
                $getFileName = basename($file);
                $src = '/temporary/' . $getFileName;
                $dst = '/uploads/' . $getFileName;
                if(Storage::disk('public')->exists($src)) {
                    Storage::disk('public')->move($src, $dst);
                }else{
                    abort(500,'文件未找到');
                }
                $url = env('APP_URL') . '/storage' . $dst;
                return $url;
            }
        }
        return '';
    }

    /**
     * 文件移废弃文件夹
     *
     * @param $attachments
     */
    private function fileDiscard($attachments)
    {
//        try {
        if (is_array($attachments)) {
            foreach ($attachments as $key => $value) {
                $getFileName = basename($value);
                $src = '/uploads/' . $getFileName;
                $dst = '/abandon/' . $getFileName;
                if(Storage::exists($src)){
                    Storage::disk('public')->move($src, $dst);
                }
            }
        } else {
            $getFileName = basename($attachments);
            $src = '/uploads/' . $getFileName;
            $dst = '/abandon/' . $getFileName;
            if(Storage::exists($src)){
                Storage::disk('public')->move($src, $dst);
            }
        }
//        } catch (\Exception $e) {
//            abort(500, '修改失败');
//        }
    }

    protected function saveLogs($request, $notes, $type)
    {
        $all = $request->all();
        $logSql = [
            'note_id' => $request->route('id'),
            'type' => $type,
            'staff_sn' => $request->user()->staff_sn,
            'staff_name' => $request->user()->realname,
            'operation_address' => [
                '电话号码' => $this->getOperation(),
                '设备类型' => $this->getPhoneType(),
                'IP地址' => $request->getClientIp()
            ],
            'changes' => $this->dataTransform($all, $notes),
        ];
        $this->noteLogsModel->create($logSql);
    }

    private function dataTransform($all, $notes)
    {
        $notes = $notes->toArray();
        $note = [];
        foreach ($notes['brands'] as $key => $value) {
            $note[] = $value['brand_id'];
        }
        $note = $this->sort($note);
        $notes['brands'] = implode(',', $note);
        if(is_array($notes['attachments'])){
            $notes['attachments'] = implode(',', $notes['attachments']);
        }
        if(is_array($all['attachments'])){
            $all['attachments'] = implode(',', $all['attachments']);
        }
        $al = $this->sort($all['brands']);
        $all['brands'] = implode(',', $al);
        $array = array_diff($all, $notes);
        foreach ($array as $key => $value) {
            if ($notes[$key] != $all[$key]) {
                $changes[$key] = [$notes[$key], $all[$key]];
            }
        }
        return isset($changes) ? $changes : [];
    }

    private function sort($arr)
    {
        $length = count($arr);
        for ($n = 0; $n < $length - 1; $n++) {
            for ($i = 0; $i < $length - $n - 1; $i++) {
                if ($arr[$i] > $arr[$i + 1]) {
                    $temp = $arr[$i + 1];
                    $arr[$i + 1] = $arr[$i];
                    $arr[$i] = $temp;
                }
            }
        }
        return $arr;
    }
}