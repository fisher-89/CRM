<?php

namespace App\Http\Controllers\Admin;

use App\Models\AuthorityGroups;
use Illuminate\Http\Request;

class AuthBrandController extends Controller
{
    public function getBrand(Request $request)
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
        return array_unique(array_filter($data));
    }
}