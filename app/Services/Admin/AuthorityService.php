<?php

namespace App\Services\Admin;

use App\Models\ClientGroupDepartments;
use App\Models\AuthorityGroups;
use App\Models\ClientGroupStaff;
use App\Models\NoteGroupStaff;
use DB;

class AuthorityService
{
    protected $staff;
    protected $groups;
    protected $noteStaff;
    protected $departments;

    public function __construct(AuthorityGroups $authorityGroups, ClientGroupStaff $authorityGroupStaff, ClientGroupDepartments $authorityGroupDepartments, NoteGroupStaff $noteGroupStaff)
    {
        $this->groups = $authorityGroups;
        $this->staff = $authorityGroupStaff;
        $this->noteStaff = $noteGroupStaff;
        $this->departments = $authorityGroupDepartments;
    }

    public function getList($request)
    {
        return $this->groups->with('staffs')->with('departments')->with('noteStaff')->filterByQueryString()->withPagination($request->get('pagesize', 10));
    }

    public function addAuth($request)
    {
        $all = $request->all();
        try {
            DB::beginTransaction();
            $group = $this->groups->create($all);
            if ((bool)$all['staffs'] === true) {
                foreach ($all['staffs'] as $key => $value) {
                    $staffSql = [
                        'authority_group_id' => $group->id,
                        'staff_sn' => $value['staff_sn'],
                        'staff_name' => $value['staff_name'],
                    ];
                    $this->staff->create($staffSql);
                }
            }
            if ((bool)$all['departments'] === true) {
                foreach ($all['departments'] as $k => $v) {
                    $departmentSql = [
                        'authority_group_id' => $group->id,
                        'department_id' => $v['department_id'],
                        'department_name' => $v['department_name'],
                    ];
                    $this->departments->create($departmentSql);
                }
            }
            if ((bool)$all['notes_staff'] === true) {
                foreach ($all['notes_staff'] as $ky => $va) {
                    $oa = app('api')->withRealException()->getStaff($va['staff_sn']);
                    $clientAuth = AuthorityGroups::where(['auth_type' => 1, 'auth_brand' => $all['auth_brand']])
                        ->wherehas('staffs', function ($query) use ($request, $va) {
                            $query->where('staff_sn', $va['staff_sn']);
                        })->orWhereHas('departments', function ($query) use ($request, $oa) {
                            $query->where('department_id', $oa['department_id']);
                        })->first();
                    if ((bool)$clientAuth === false) {
                        DB::rollback();
                        return response('添加失败，请注意是否有客户信息查看权限',400);
                    }
                    $noteSql = [
                        'authority_group_id' => $group->id,
                        'staff_sn' => $va['staff_sn'],
                        'staff_name' => $va['staff_name'],
                    ];
                    $this->noteStaff->create($noteSql);
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            abort(400, '添加失败');
        }
        return response()->json($this->groups->with('staffs')->with('departments')->with('noteStaff')->where('id', $group->id)->first(), 201);
    }

    public function updateAuth($request)
    {
        $id = $request->route('id');
        $group = $this->groups->find($id);
        if ((bool)$group === false) {
            abort(404, '未找到数据');
        }
        $all = $request->all();
        try {
            DB::beginTransaction();
            if ((bool)$all['name'] === true) {
                $group->update($all);
            }
            if ((bool)$all['staffs'] === true) {
                $this->staff->where('authority_group_id', $id)->delete();
                foreach ($all['staffs'] as $key => $value) {
                    $staffSql = [
                        'authority_group_id' => $group->id,
                        'staff_sn' => $value['staff_sn'],
                        'staff_name' => $value['staff_name'],
                    ];
                    $this->staff->create($staffSql);
                }
            }
            if ((bool)$all['departments'] === true) {
                $this->departments->where('authority_group_id', $id)->delete();
                foreach ($all['departments'] as $k => $v) {
                    $departmentSql = [
                        'authority_group_id' => $group->id,
                        'department_id' => $v['department_id'],
                        'department_name' => $v['department_name'],
                    ];
                    $this->departments->create($departmentSql);
                }
            }
            if ((bool)$all['notes_staff'] === true) {
                $this->noteStaff->where('authority_group_id', $id)->delete();
                foreach ($all['notes_staff'] as $ky => $va) {
                    $oa = app('api')->withRealException()->getStaff($va['staff_sn']);
                    $clientAuth = AuthorityGroups::where(['auth_type' => 1, 'auth_brand' => $all['auth_brand']])
                        ->wherehas('staffs', function ($query) use ($request, $va) {
                            $query->where('staff_sn', $va['staff_sn']);
                        })->orWhereHas('departments', function ($query) use ($request, $oa) {
                            $query->where('department_id', $oa['department_id']);
                        })->first();
                    if ((bool)$clientAuth === false) {
                        DB::rollback();
                        return response('修改失败，请注意是否有客户信息查看权限',400);
                    }
                    $noteSql = [
                        'authority_group_id' => $group->id,
                        'staff_sn' => $va['staff_sn'],
                        'staff_name' => $va['staff_name'],
                    ];
                    $this->noteStaff->create($noteSql);
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            abort(400, '修改失败');
        }
        return response()->json($this->groups->with('staffs')->with('departments')->with('noteStaff')->where('id', $group->id)->first(), 201);
    }

    public function delAuth($request)
    {
        $id = $request->route('id');
        $group = $this->groups->find($id);
        if ((bool)$group === true) {
            $this->staff->where('authority_group_id', $id)->delete();
            $this->departments->where('authority_group_id', $id)->delete();
            $this->noteStaff->where('authority_group_id', $id)->delete();
            $group->delete();
            return response('', 204);
        } else {
            abort(404, '删除失败,未找到数据');
        }
    }
}