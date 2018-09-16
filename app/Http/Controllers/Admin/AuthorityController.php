<?php

namespace App\Http\Controllers\Admin;

use App\Models\AuthGroupHasEditableBrands;
use App\Models\AuthGroupHasVisibleBrands;
use App\Models\AuthorityGroups;
use App\Services\Admin\AuthorityService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AuthorityController extends Controller
{
    protected $authority;

    public function __construct(AuthorityService $authorityService)
    {
        $this->authority = $authorityService;
    }

    public function index(Request $request)
    {
        return $this->authority->getList($request);
    }

    public function store(Request $request)
    {
        $this->storeVerify($request);
        return $this->authority->addAuth($request);
    }

    public function edit(Request $request)
    {
        $this->editVerify($request);dd('ping');
        return $this->authority->updateAuth($request);
    }

    public function delete(Request $request)
    {
        return $this->authority->delAuth($request);
    }

    protected function storeVerify($request)
    {
        $editables = $request->editables;$visibles = $request->visibles;
        $this->validate($request,
            [
                'name' => ['required', 'max:20', Rule::unique('authority_groups', 'name')],
                'description' => 'max:30',
                'visibles.*' => 'numeric',
                'visibles' => ['array', function ($attribute, $value, $event) use ($editables) {
                    if ($value == [] &&  $editables == []) {
                        return $event('查看权限必选');
                    }
                }],
                'editables' => ['array',
                    function($attribute, $value, $event)use($visibles){
                        $diff = array_diff($value,$visibles);
                        if($diff != []){
                            $event('品牌ID:'.implode('、',$diff).'没有查看权限');
                        };
                    }
                    ],
                'editables.*' => 'numeric',
                'staffs' => 'array|required',
                'staffs.*.staff_sn' => 'numeric|digits:6|required',
                'staffs.*.staff_name' => 'max:10|required',
            ], [], [
                'name' => '分组名称',
                'description' => '描述',
                'visibles.*' => '查看',
                'editables.*' => '操作',
                'staffs.*.staff_sn' => '员工编号',
                'staffs.*.staff_name' => '员工姓名',
            ]
        );
    }

    protected function editVerify($request)
    {
        $id = $request->route('id');
        $visibles = $request->visibles;
        $this->validate($request,
            [
                'name' => ['required', 'max:20', Rule::unique('authority_groups', 'name')
                    ->whereNotIn('id', explode(' ', $id)),],
                'description' => 'max:30',
                'visibles.*' => 'numeric',
                'visibles' => ['array', function ($attribute, $value, $event){
                    if ($value == []) {
                        return $event('查看权限必选');
                    }
                }],
                'editables' => ['array',
                    function($attribute, $value, $event)use($visibles){
                        $diff = array_diff($value,$visibles);
                        if($diff != []){
                            $event('品牌ID:'.implode('、',$diff).'没有查看权限');
                        };
                    }
                ],
                'editables.*' => 'numeric',
                'staffs' => 'array|required',
                'staffs.*.staff_sn' => 'numeric|digits:6|required',
                'staffs.*.staff_name' => 'max:10|required',
            ], [], [
                'name' => '分组名称',
                'description' => '描述',
                'visibles.*' => '查看',
                'editables.*' => '操作',
                'staffs.*.staff_sn' => '员工编号',
                'staffs.*.staff_name' => '员工姓名',
            ]
        );
    }
}