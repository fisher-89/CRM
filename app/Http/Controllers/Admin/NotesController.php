<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\NotesRequest;
use App\Models\AuthorityGroups;
use App\Models\ClientHasBrands;
use App\Models\Clients;
use App\Models\Nations;
use App\Models\NoteGroupStaff;
use App\Models\Notes;
use App\Services\Admin\NoteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class NotesController extends Controller
{
    protected $note;

    public function __construct(NoteService $noteService)
    {
        $this->note = $noteService;
    }

    /**
     * 事件分类列表
     *
     * @param Request $request
     * @return mixed
     */
    public function indexType(Request $request)
    {
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
        $obj = $this->noteReadingAuth($request);
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
        $this->noteActionAuth($request);
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
        $this->clientEditAuth($request);
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
        $this->clientEditAuth($request);
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
        $id = $request->route('id');
        $clientId = Notes::where('id', $id)->value('client_id');
        $brandId = ClientHasBrands::where('client_id', $clientId)->get();
        foreach ($brandId as $item){
            $auth[] = AuthorityGroups::where(['auth_type'=>1,'auth_brand'=>$item->brand_id])
                ->whereHas('noteStaff', function ($query) use ($request) {
                $query->where('staff_sn', $request->user()->staff_sn);
            })->first();
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
     * 事件列表查看权限
     *
     * @param $request
     */
    protected function noteReadingAuth($request)
    {
        $noteGroup = AuthorityGroups::where('auth_type', 1)->whereHas('noteStaff', function ($query) use ($request) {
            $query->where('staff_sn', $request->user()->staff_sn);
        })->get();
        if ((bool)$noteGroup->all() === false) {
            abort(401, '暂无查看权限');
        } else {
            return $noteGroup;
        }
    }

    /**
     * 事件操作权限
     *
     * @param $request
     */
    protected function noteActionAuth($request)
    {
        $client = $request->all('client_id');
        $has = ClientHasBrands::where('client_id', $client)->get();
        foreach ($has as $item) {
            $auth[] = AuthorityGroups::where(['auth_type' => 2, 'auth_brand' => $item->brand_id])
                ->whereHas('noteStaff', function ($query) use ($request) {
                    $query->where('staff_sn', $request->user()->staff_sn);
                })->first();
        }
        $data = isset($auth) ? $auth : [];
        $bool = array_filter($data);
        if ($bool === []) {
            abort(401, '暂无添加权限');
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
    protected function clientEditAuth($request)
    {
        $recorderSn = Notes::where('id', $request->route('id'))->value('recorder_sn');
        if ($recorderSn != Auth::user()->staff_sn) {
            abort(401, '暂无权限');
        }
    }
}