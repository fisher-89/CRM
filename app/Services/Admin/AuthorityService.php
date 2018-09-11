<?php

namespace App\Services\Admin;

use App\Http\Resources\AuthorityCollection;
use App\Models\AuthorityGroups;
use App\Models\AuthGroupHasStaff;
use App\Models\AuthGroupHasVisibleBrands;
use App\Models\AuthGroupHasEditableBrands;
use DB;

class AuthorityService
{
    protected $groups;
    protected $staff;
    protected $editable;
    protected $visible;

    public function __construct(AuthorityGroups $authorityGroups, AuthGroupHasStaff $authGroupHasStaff,
                                AuthGroupHasVisibleBrands $authGroupHasVisibleBrands, AuthGroupHasEditableBrands $authGroupHasEditableBrands)
    {
        $this->groups = $authorityGroups;
        $this->staff = $authGroupHasStaff;
        $this->editable = $authGroupHasEditableBrands;
        $this->visible = $authGroupHasVisibleBrands;
    }

    public function getList($request)
    {
        $list = $this->groups->with(['staffs','editables','visibles'])->filterByQueryString()->SortByQueryString()->withPagination($request->get('pagesize', 10));
        if (isset($list['data'])) {
            $list['data'] = new AuthorityCollection(collect($list['data']));
            return $list;
        } else {
            return new AuthorityCollection($list);
        }
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
                        'staff_name'=>$value['staff_name']
                    ];
                    $this->staff->create($staffSql);
                }
            }
            if ((bool)$all['editables'] === true) {
                foreach ($all['editables'] as $k => $v) {
                    $departmentSql = [
                        'authority_group_id' => $group->id,
                        'brand_id' => $v,
                    ];
                    $this->editable->create($departmentSql);
                }
            }
            if ((bool)$all['visibles'] === true) {
                foreach ($all['visibles'] as $ky => $va) {
                    $noteSql = [
                        'authority_group_id' => $group->id,
                        'brand_id' => $va,
                    ];
                    $this->visible->create($noteSql);
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            abort(400, '添加失败');
        }
        $data=$this->groups->with('staffs')->where('id', $group->id)->first();
        $data['editables']=$all['editables'];
        $data['visibles']=$all['visibles'];
        return response()->json($data, 201);
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
            if ((bool)$all['editables'] === true) {
                $this->editable->where('authority_group_id', $id)->delete();
                foreach ($all['editables'] as $k => $v) {
                    $departmentSql = [
                        'authority_group_id' => $group->id,
                        'brand_id' => $v,
                    ];
                    $this->editable->create($departmentSql);
                }
            }
            if ((bool)$all['visibles'] === true) {
                $this->visible->where('authority_group_id', $id)->delete();
                foreach ($all['visibles'] as $ky => $va) {
                    $noteSql = [
                        'authority_group_id' => $group->id,
                        'brand_id' => $va,
                    ];
                    $this->visible->create($noteSql);
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            abort(400, '修改失败');
        }
        $editData=$this->groups->with('staffs')->where('id', $group->id)->first();
        $editData['editables']=$all['editables'];
        $editData['visibles']=$all['visibles'];
        return response()->json($editData, 201);
    }

    public function delAuth($request)
    {
        $id = $request->route('id');
        $group = $this->groups->find($id);
        if ((bool)$group === true) {
//            $this->staff->where('authority_group_id', $id)->delete();
//            $this->editable->where('authority_group_id', $id)->delete();
//            $this->visible->where('authority_group_id', $id)->delete();
            $group->delete();
            return response('', 204);
        } else {
            abort(404, '删除失败,未找到数据');
        }
    }

    /**
     * 查看权限
     *
     * @param $request
     */
    public function readingAuth($staff)
    {
        $staff = AuthorityGroups::whereHas('staffs', function ($query) use ($staff) {
            $query->where('staff_sn', $staff);
        })->with('visibles')->get();
        if ((bool)$staff === false) {
            abort(401, '暂无权限');
        }else{
            return $staff;
        }
    }

    /**
     * 操作权限
     *
     * @param $request
     */
    public function actionAuth($request)
    {
        if(empty($request->brands)){
            abort(404,'未找到的品牌');
        }
        foreach ($request->brands as $item) {
            $auth[] = AuthorityGroups::whereHas('staffs', function ($query) use ($request) {
                    $query->where('staff_sn', $request->user()->staff_sn);
                })->whereHas('editables',function($query)use($item){
                    $query->where('brand_id',$item);
            })->first();
        }
        $data = isset($auth) ? $auth : [];
        $bool = array_filter($data);
        if ($bool === []) {
            abort(401, '暂无权限');
        }
    }
}