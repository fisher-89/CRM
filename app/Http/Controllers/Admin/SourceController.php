<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Clients;
use App\Services\Admin\SourceService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SourceController extends Controller
{
    protected $source;

    public function __construct(SourceService $sourceService)
    {
        $this->source = $sourceService;
    }

    public function index(Request $request)
    {
        return $this->source->indexList($request);
    }

    public function store(Request $request)
    {
        $this->storeVerify($request);
        return $this->source->addSource($request);
    }

    public function update(Request $request)
    {
        $this->editVerify($request);
        return $this->source->editSource($request);
    }

    public function delete(Request $request)
    {
        $client = Clients::where('source_id', $request->route('id'))->first();
        if ($client == true) {
            abort(400, '删除失败：该条记录被使用');
        }
        return $this->source->delSource($request);
    }

    protected function storeVerify($request)
    {
        //nullable
        $this->validate($request,
            [
                'name' => 'required|unique:source,name|max:10',
                'describe' => '',
                'sort' => 'numeric'
            ], [], [
                'name' => '来源名称',
                'describe' => '描述',
                'sort' => '排序'
            ]);
    }

    protected function editVerify($request)
    {
        $this->validate($request,
            [
                'name' => ['required',
                    Rule::unique('source', 'name')
                        ->whereNotIn('id', explode(' ', $request->route('id'))),
                    'max:10'
                ],
                'describe' => '',
                'sort' => 'numeric'
            ], [], [
                'name' => '来源名称',
                'describe' => '描述',
                'sort' => '排序'
            ]);
    }
}