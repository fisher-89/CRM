<?php

namespace App\Services\Admin;

use App\Models\Source;
use Illuminate\Http\Request;

class SourceService
{
    protected $source;

    public function __construct(Source $sourceModels)
    {
        $this->source = $sourceModels;
    }

    public function indexList(Request $request)
    {
        return $this->source->SortByQueryString()->filterByQueryString()->withPagination($request->get('pagesize', 10));
    }

    public function addSource(Request $request)
    {
        $source = $this->source->create($request->all());
        return response()->json($source, 201);
    }

    public function editSource(Request $request)
    {
        $source = $this->source->find($request->route('id'));
        if ((bool)$source === false) {
            abort(404, '未找到数据');
        }
        $source->update($request->all());
        return response()->json($source, 201);
    }

    public function delSource(Request $request)
    {
        $source = $this->source->find($request->route('id'));
        if ((bool)$source === false) {
            abort(404, '未找到数据');
        }
        $source->delete();
        return response('', 204);
    }
}