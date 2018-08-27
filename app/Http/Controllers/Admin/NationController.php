<?php

namespace App\Http\Controllers\Admin;

use App\Models\Nations;
use Illuminate\Http\Request;

class NationController extends Controller
{
    protected $nation;

    public function __construct(Nations $nations)
    {
        $this->nation = $nations;
    }

    public function index(Request $request)
    {
        return $this->nation->get();
    }
//临时用
    public function store(Request $request)
    {
        foreach($request->all() as $k=>$v){
            $sql=[
                'name'=>$v['name'],
                'sort'=>$v['sort'],
            ];
            $this->nation->create($sql);
        }
    }
}