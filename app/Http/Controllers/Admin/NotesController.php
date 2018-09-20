<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\NotesRequest;
use App\Models\AuthorityGroups;
use App\Models\ClientHasBrands;
use App\Models\Clients;
use App\Models\Nations;
use App\Models\AuthGroupHasStaff;
use App\Models\Notes;
use App\Services\Admin\AuthorityService;
use App\Services\Admin\NoteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class NotesController extends Controller
{
    protected $note;
    protected $auth;

    public function __construct(NoteService $noteService,AuthorityService $authorityService)
    {
        $this->note = $noteService;
        $this->auth = $authorityService;
    }

    /**
     * 事件分类列表
     *
     * @param Request $request
     * @return mixed
     */
    public function indexType(Request $request)
    {
        $OA = $request->user()->authorities['oa'];
        if (!in_array('189',$OA)) {
            abort(401, '你没有权限操作');
        }
        return $this->note->getListType($request);
    }

    /**
     * 添加事件分类
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeType(Request $request)
    {
        $OA = $request->user()->authorities['oa'];
        if (!in_array('189',$OA)) {
            abort(401, '你没有权限操作');
        }
        $this->storeVerify($request);
        return $this->note->addNoteType($request);
    }

    /**
     * 修改事件分类
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function editType(Request $request)
    {
        $OA = $request->user()->authorities['oa'];
        if (!in_array('189',$OA)) {
            abort(401, '你没有权限操作');
        }
        $this->verifyEmploy($request);
        $this->editVerify($request);
        return $this->note->editNoteType($request);
    }

    /**
     * 删除事件分类
     *
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function deleteType(Request $request)
    {
        $OA = $request->user()->authorities['oa'];
        if (!in_array('189',$OA)) {
            abort(401, '你没有权限操作');
        }
        $this->verifyEmploy($request);
        return $this->note->delNoteType($request);
    }

    /**
     * 事件列表
     *
     * @param Request $request
     * @return mixed
     */
    public function index(Request $request)
    {
        $OA = $request->user()->authorities['oa'];
        if (!in_array('181',$OA)) {
            abort(401, '你没有权限操作');
        }
        $obj = $this->auth->readingAuth($request->user()->staff_sn);
        return $this->note->getList($request, $obj);
    }

    /**
     * 添加事件
     *
     * @param NotesRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(NotesRequest $request)
    {
//        $this->auth->actionAuth($request);
        $OA = $request->user()->authorities['oa'];
        if (!in_array('182',$OA)) {
            abort(401, '你没有权限操作');
        }
        return $this->note->addNote($request);
    }

    /**
     * 修改事件
     *
     * @param NotesRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function edit(NotesRequest $request)
    {
        abort(500,'接口暂停使用');
        $this->noteEditAuth($request);
        return $this->note->editNote($request);
    }

    /**
     * 删除事件
     *
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function delete(Request $request)
    {
        $OA = $request->user()->authorities['oa'];
        if (!in_array('185',$OA)) {
            abort(401, '你没有权限操作');
        }
        $this->noteEditAuth($request);
        return $this->note->delNote($request);
    }

    /**
     * 单条详细事件
     *
     * @param Request $request
     * @return mixed
     */
    public function detailNote(Request $request)
    {
        $OA = $request->user()->authorities['oa'];
        if (!in_array('181',$OA)) {
            abort(401, '你没有权限操作');
        }
        $staff = $this->auth->readingAuth($request->user()->staff_sn);
        foreach ($staff as $item){
            foreach ($item['visibles'] as $k=>$v){
                $auth[]=$v['brand_id'];
            }
        }
        $data = isset($auth) ? $auth : [];
        $bool = array_filter($data);
        if ((bool)$bool === false) {
            abort(401, '暂无权限');
        }else{
            return $this->note->getDetail($request,$bool);
        }
    }

    /**
     * 事件添加验证
     *
     * @param $request
     * @return array
     */
    public function storeVerify($request)
    {
        return $this->validate($request,
            [
                'name' => 'required|max:20|unique:note_types,name',
                'sort' => 'required|numeric',
                'is_task' => 'numeric|between:0,1',
            ], [], [
                'name' => '名称',
                'sort' => '排序',
                'is_task' => '定时任务',
            ]
        );
    }

    /**
     * 事件修改验证
     *
     * @param $request
     * @return array
     */
    public function editVerify($request)
    {
        return $this->validate($request,
            [
                'name' => ['required', 'max:20',
                    Rule::unique('note_types', 'name')
                        ->whereNotIn('id', explode(' ', $request->route('id'))),
                ],
                'sort' => 'required|numeric',
                'is_task' => 'numeric|between:0,1',
            ], [], [
                'name' => '名称',
                'sort' => '排序',
                'is_task' => '定时任务',
            ]
        );
    }

    /**
     * 事件修改权限
     *
     * @param $request
     */
    protected function noteEditAuth($request)
    {
        $recorderSn = Notes::where('id', $request->route('id'))->value('recorder_sn');
        if ($recorderSn != Auth::user()->staff_sn) {
            abort(401, '暂无权限,只能操作本人添加的数据');
        }
    }

    /**
     * 被使用验证
     *
     * @param $request
     */
    private function verifyEmploy($request)
    {
        $note=Notes::where('note_type_id',$request->route('id'))->first();
        if((bool)$note === true){
            abort(400,'当前分类被暂用，不能修改');
        }
    }

    public function getUserBrands(Request $request)
    {
        $obj=ClientHasBrands::where('client_id',$request->route('id'))->get();
        return $obj;
    }
}