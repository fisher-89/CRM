<?php

namespace App\Http\Controllers\Admin;

use App\Services\Admin\LevelsService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LevelsController extends Controller
{
    protected $levelService;

    public function __construct(LevelsService $level)
    {
        $this->levelService = $level;
    }

    public function index(Request $request)
    {
        //todo 权限
        return $this->levelService->getList($request);
    }

    public function store(Request $request)
    {
        //todo 权限
        $this->verifyLevel($request);
        return $this->levelService->storeLevel($request);
    }

    public function edit(Request $request)
    {
        //todo 权限
        $this->editVerify($request);
        return $this->levelService->editLevel($request);
    }

    public function delete(Request $request)
    {
        //todo 权限
        return $this->levelService->delLevel($request);
    }

    protected function verifyLevel($request)
    {
        $this->validate($request,
            [
                'name' => 'required|max:10|unique:levels,name',
                'explain' => 'max:50'
            ], [], [
                'name' => '等级名称',
                'explain' => '等级说明'
            ]
        );
    }

    protected function editVerify($request)
    {
        $this->validate($request,
            [
                'name' => ['required','max:10',
                    Rule::unique('levels','name')
                        ->whereNotIn('id', explode(' ', $request->route('id'))),
                ],
                'explain' => 'max:50'
            ], [], [
                'name' => '等级名称',
                'explain' => '等级说明'
            ]
        );
    }
}