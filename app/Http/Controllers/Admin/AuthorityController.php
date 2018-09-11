<?php

namespace App\Http\Controllers\Admin;

use App\Models\AuthGroupHasEditableBrands;
use App\Models\AuthGroupHasVisibleBrands;
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
    {abort(500,'暂停使用');
        $this->editVerify($request);
        return $this->authority->updateAuth($request);
    }

    public function delete(Request $request)
    {abort(500,'暂停使用');
        return $this->authority->delAuth($request);
    }

    protected function storeVerify($request)
    {
        $this->validate($request,
            [
                'name' => ['required', 'max:20', Rule::unique('authority_groups', 'name')],
                'description' => 'max:30',
                'visibles.*' => 'numeric',
                'editables.*' => 'numeric',
                'staffs.*.staff_sn' => 'numeric|digits:6',
                'staffs.*.staff_name' => 'max:10',
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
        $this->validate($request,
            [
                'name' => ['required', 'max:20', Rule::unique('authority_groups', 'name')->whereNotIn('id', explode(' ', $request->route('id'))),],
                'description' => 'max:30',
                'visibles.*' => 'numeric',
                'editables.*' => 'numeric',
                'staffs.*.staff_sn' => 'numeric|digits:6',
                'staffs.*.staff_name' => 'max:10',
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