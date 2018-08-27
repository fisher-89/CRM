<?php

namespace App\Services\Admin;

use App\Models\Tags;
use App\Models\TagType;
use Illuminate\Http\Request;

class TagService
{
    use Traits\Filterable;

    protected $tagType;
    protected $tags;

    public function __construct(TagType $tagType, Tags $tags)
    {
        $this->tagType = $tagType;
        $this->tags = $tags;
    }

    public function listType(Request $request)
    {
        return $this->tagType->orderBy('sort','asc')->filterByQueryString()->withPagination($request->get('pagesize', 10));
    }

    public function addType(Request $request)
    {
        $createData = $this->tagType->create($request->all());
        return response()->json($createData, 201);
    }

    public function editType(Request $request)
    {
        $tag = $this->tagType->find($request->route('id'));
        if ((bool)$tag === false) {
            abort(404, '未找到数据');
        }
        $tag->update($request->all());
        return response()->json($tag, 201);
    }

    public function delType(Request $request)
    {
        $data = $this->tagType->find($request->route('id'));
        if ((bool)$data === false) {
            abort(404, '未找到数据');
        }
        if ((bool)$data->delete() === false) {
            abort(400, '操作失败');
        }
        return response('', 204);
    }

    public function tagList($request)
    {
        return $this->tags
//            ->with('tagType')
            ->orderBy('sort','asc')->filterByQueryString()->withPagination($request->get('pagesize', 10));
    }

    public function tagStore($request)
    {
        return response()->json( $this->tags->create($request->all()),201);
    }

    public function tagEdit($request)
    {
        $tagId=$this->tags->find($request->route('id'));
        if((bool)$tagId===false){
            abort(404,'未找到数据');
        }
        $tagId->update($request->all());
        return response()->json($tagId,201);
    }

    public function tagDel($request)
    {
        $tagsData=$this->tags->find($request->route('id'));
        if((bool)$tagsData===false){
            abort(404,'提供无效参数');
        };
        $tagsData->delete();
        return response('',204);
    }
}