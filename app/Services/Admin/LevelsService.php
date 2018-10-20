<?php

namespace App\Services\Admin;

use App\Models\Levels;
use Illuminate\Http\Request;

class LevelsService
{
    use Traits\Filterable;

    protected $levelModel;

    public function __construct(Levels $level)
    {
        $this->levelModel = $level;
    }

    public function getList($request)
    {
        return $this->levelModel->SortByQueryString()->filterByQueryString()->withPagination($request->get('pagesize', 10));
    }

    public function storeLevel($request)
    {
        $createData = $this->levelModel->create($request->all());
        return response()->json($createData, 201);
    }

    public function editLevel($request)
    {
        $tag = $this->levelModel->find($request->route('id'));
        if ((bool)$tag === false) {
            abort(404, '未找到数据');
        }
        $tag->update($request->all());
        return response()->json($tag, 201);
    }

    public function delLevel(Request $request)
    {
        $data = $this->levelModel->find($request->route('id'));
        if ((bool)$data === false) {
            abort(404, '未找到数据');
        }
        if ((bool)$data->delete() === false) {
            abort(400, '操作失败');
        }
        return response('', 204);
    }
}