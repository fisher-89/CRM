<?php

namespace App\Http\Controllers\Admin;

use App\Models\ClientGroupDepartments;
use App\Models\ClientGroupStaff;
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
        $this->editVerify($request);
        return $this->authority->updateAuth($request);
    }

    public function delete(Request $request)
    {
        return $this->authority->delAuth($request);
    }

    protected function storeVerify($request)
    {
        $this->validate($request,
            [
                'name' => ['required', 'max:20', Rule::unique('authority_groups', 'name')],
                'auth_type' => 'required|min:1|max:2|numeric',
                'auth_brand' => 'required|min:1|max:20|numeric',
                'departments.*.department_id' => 'numeric|digits:3',
                'departments.*.department_name' => 'max:10',
                'staffs.*.staff_sn' => ['numeric','digits:6',
                    Rule::unique('client_group_staff','staff_sn')
                    ->where(function ($query)use($request){
                        $query->where('authority_group_id',$request->route('id'));
                    })
                    ],
                'staffs.*.staff_name' => 'max:10',
                'notes_staff.*.staff_sn' => 'numeric|digits:6',
                'notes_staff.*.staff_name' => 'max:10',
            ], [], [
                'name' => '分组名称',
                'auth_type' => '权限类型',
                'auth_brand' => '权限品牌',
                'departments.*.department_id' => '客户信息部门id',
                'departments.*.department_name' => '客户信息部门名称',
                'staffs.*.staff_sn' => '客户信息员工编号',
                'staffs.*.staff_name' => '客户信息员工姓名',
                'notes_staff.*.staff_sn' => '客户事件员工编号',
                'notes_staff.*.staff_name' => '客户事件员工名称',
            ]
        );
    }

    protected function editVerify($request)
    {
        $this->validate($request,
            [
                'name' => ['max:20', Rule::unique('authority_groups', 'name')->whereNotIn('id', explode(' ', $request->route('id'))),],
                'auth_type' => 'required|min:1|max:2|numeric',
                'auth_brand' => 'required|min:1|max:20|numeric',
                'departments.*.department_id' => 'numeric|digits:3',
                'departments.*.department_name' => 'max:10',
                'staffs.*.staff_sn' => 'numeric|digits:6',
                'staffs.*.staff_name' => 'max:10',
                'notes_staff.*.staff_sn' => 'numeric|digits:6',
                'notes_staff.*.staff_name' => 'max:10',
            ], [], [
                'name' => '分组名称',
                'auth_type' => '权限类型',
                'auth_brand' => '权限品牌',
                'departments.*.department_id' => '客户信息部门id',
                'departments.*.department_name' => '客户信息部门名称',
                'staffs.*.staff_sn' => '客户信息员工编号',
                'staffs.*.staff_name' => '客户信息员工姓名',
                'notes_staff.*.staff_sn' => '客户事件员工编号',
                'notes_staff.*.staff_name' => '客户事件员工名称',
            ]
        );
    }
}