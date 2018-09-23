<?php

namespace App\Http\Controllers\Admin;

use App\Models\AuthorityGroups;
use Illuminate\Http\Request;

class AuthBrandController extends Controller
{
    public function getBrand(Request $request)
    {
        if($request->user()->staff_sn == 999999){
            dd( [
                "editable"=>[1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14],
                "visible"=> [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14]
            ]);
        }
        return ['editable'=>$this->getBrandEditable($request),'visible'=>$this->getBrandVisible($request)];
    }

    public function getBrandEditable($request)
    {
        $staffSn = $request->user()->staff_sn;
        $obj = AuthorityGroups::whereHas('staffs', function ($query) use ($staffSn) {
            $query->where('staff_sn', $staffSn);
        })->with('editables')->get();
        foreach ($obj as $k => $v) {
            foreach ($v['editables'] as $key => $value) {
                $authData[] = $value['brand_id'];
            }
        }
        $data = isset($authData) ? $authData : [];
        if ((bool)$data == false) {
            return [];
        }
        return array_values(array_unique(array_filter($data)));
    }

    public function getBrandVisible($request)
    {
        $staffSn = $request->user()->staff_sn;
        $obj = AuthorityGroups::whereHas('staffs', function ($query) use ($staffSn) {
            $query->where('staff_sn', $staffSn);
        })->with('visibles')->get();
        foreach ($obj as $k => $v) {
            foreach ($v['visibles'] as $key => $value) {
                $authData[] = $value['brand_id'];
            }
        }
        $data = isset($authData) ? $authData : [];
        if ((bool)$data == false) {
            return [];
        }
        return array_values(array_unique(array_filter($data)));
    }
}