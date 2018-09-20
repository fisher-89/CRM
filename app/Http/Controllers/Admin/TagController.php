<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tags;
use App\Services\Admin\TagService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TagController extends Controller
{
    protected $tagService;

    public function __construct(TagService $tagService)
    {
        $this->tagService = $tagService;
    }

    /**
     * 标签分类list
     *
     * @param Request $request
     * @return mixed
     */
    public function indexType(Request $request)
    {
        $OA = $request->user()->authorities['oa'];
        if (!in_array('180',$OA)) {
            abort(401, '你没有权限操作');
        }
        return $this->tagService->listType($request);
    }

    /**
     * 标签分类添加
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeType(Request $request)
    {
        $OA = $request->user()->authorities['oa'];
        if (!in_array('180',$OA)) {
            abort(401, '你没有权限操作');
        }
        $this->storeVerify($request);
        return $this->tagService->addType($request);
    }

    /**
     * 标签分类修改
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateType(Request $request)
    {
        $OA = $request->user()->authorities['oa'];
        if (!in_array('180',$OA)) {
            abort(401, '你没有权限操作');
        }
        $this->updateVerify($request);
        return $this->tagService->editType($request);
    }

    /**
     * 标签分类删除
     *
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function deleteType(Request $request)
    {
        $OA = $request->user()->authorities['oa'];
        if (!in_array('180',$OA)) {
            abort(401, '你没有权限操作');
        }
        $tags = Tags::where('type_id',$request->route('id'))->first();
        if($tags == true){
            abort(400,'删除失败，分类被使用');
        }
        return $this->tagService->delType($request);
    }

    /**
     * 添加表单验证
     *
     * @param Request $request
     */
    public function storeVerify(Request $request)
    {
        $this->validate($request,
            [
                'name' => 'required|unique:tag_types,name',
                'color' => 'max:7|min:7',
                'sort' => 'numeric'
            ], [], [
                'name' => '类型名字',
                'color' => '颜色',
                'sort' => '排序'
            ]);
    }

    /**
     * 编辑表单验证
     *
     * @param Request $request
     */
    protected function updateVerify(Request $request)
    {
        $this->validate($request,
            [
                'name' => ['required',
                    Rule::unique('tag_types', 'name')
                        ->whereNotIn('id', explode(' ', $request->route('id'))),
                ],
                'color' => 'max:7|min:7',
                'sort' => 'numeric'
            ], [], [
                'name' => '类型名字',
                'color' => '颜色',
                'sort' => '排序'
            ]);
    }

    /**
     * 标签list页面
     *
     * @param Request $request
     * @return mixed
     */
    public function index(Request $request)
    {
        $OA = $request->user()->authorities['oa'];
        if (!in_array('179',$OA)) {
            abort(401, '你没有权限操作');
        }
        return $this->tagService->tagList($request);
    }

    /**
     * 标签增加
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $OA = $request->user()->authorities['oa'];
        if (!in_array('179',$OA)) {
            abort(401, '你没有权限操作');
        }
        $this->tagStoreVerify($request);
        return $this->tagService->tagStore($request);
    }

    /**
     * 标签修改
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request)
    {
        $OA = $request->user()->authorities['oa'];
        if (!in_array('179',$OA)) {
            abort(401, '你没有权限操作');
        }
        $this->tagUpdateVerify($request);
        return $this->tagService->tagEdit($request);
    }

    /**
     * 标签删除
     *
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function delete(Request $request)
    {
        $OA = $request->user()->authorities['oa'];
        if (!in_array('179',$OA)) {
            abort(401, '你没有权限操作');
        }
        return $this->tagService->tagDel($request);
    }

    /**
     * 标签添加验证
     * 191
     * @param $request
     */
    protected function tagStoreVerify($request)
    {
        $this->validate($request,
            [
                'type_id'=>'required|numeric|exists:tag_types,id',
                'name'=>['required','max:255',
                    Rule::unique('tags','name')
//                        ->where('type_id',$request->all('type_id'))
                    ],
                'describe'=>'max:50',
                'sort'=>'numeric',
            ],[],[
                'type_id'=>'分类',
                'name'=>'标签名称',
                'describe'=>'描述',
                'sort'=>'排序',
            ]);
    }

    /**
     * 标签修改验证
     *
     * @param $request
     */
    protected function tagUpdateVerify($request)
    {
        $this->validate($request,
            [
                'type_id'=>'required|numeric|exists:tag_types,id',
                'name'=>['required','max:255',
                    Rule::unique('tags','name')
//                    ->where('type_id',$request->all('type_id'))
                    ->whereNotIn('id', explode(' ', $request->route('id')))
                    ],
                'describe'=>'max:50',
                'sort'=>'numeric',
            ],[],[
                'type_id'=>'分类',
                'name'=>'标签名称',
                'describe'=>'描述',
                'sort'=>'排序',
            ]);
    }
}
