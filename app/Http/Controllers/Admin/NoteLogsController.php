<?php

namespace App\Http\Controllers\Admin;

use App\Models\AuthorityGroups;
use App\Models\Nations;
use App\Models\NoteLogs;
use App\Models\Notes;
use App\Services\Admin\NoteLogsService;
use Illuminate\Http\Request;

class NoteLogsController extends Controller
{
    protected $noteLogsService;
    protected $noteLogsModel;
    protected $noteModel;

    public function __construct(NoteLogsService $noteLogsService,NoteLogs $noteLogs,Notes $notes)
    {
        $this->noteModel = $notes;
        $this->noteLogsModel = $noteLogs;
        $this->noteLogsService = $noteLogsService;
    }
    public function index(Request $request)
    {
        $obj=$this->noteReadingAuth($request);
        return $this->noteLogsService->getList($request,$obj);
    }

    public function restore(Request $request)
    {
        $logs=$this->noteLogsModel->find($request->route('id'));
        $note=$this->noteLogsModel->orderBy('id','desc')->where('note_id',$logs->note_id)->first();
        if($request->user()->staff_sn !== $logs->staff_sn){
            abort(401,'你没有权限修改');
        }
        if(strstr($logs->type,'还原')){
            abort(400,'错误操作');
        }
        if($note->id != $request->id){
            abort(401,'无法操作之前数据');
        }
        return $this->noteLogsService->restoreNote($request);
    }

    protected function noteReadingAuth($request)
    {
        $staff = AuthorityGroups ::where('auth_type', 1)->whereHas('noteStaff', function ($query) use ($request) {
            $query->where('staff_sn', $request->user()->staff_sn);
        })->get();
        if ((bool)$staff === false) {
            abort(401, '暂无权限');
        }
        return $staff;
    }
}