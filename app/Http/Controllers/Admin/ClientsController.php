<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\ClientRequest;
use App\Models\AuthorityGroups;
use App\Models\AuthGroupHasEditableBrands;
use App\Models\AuthGroupHasVisibleBrands;
use App\Models\ClientHasBrands;
use App\Models\Clients;
use App\Services\Admin\AuthorityService;
use App\Services\Admin\ClientsService;
use Illuminate\Http\Request;
use Excel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ClientsController extends Controller
{
    protected $client;
    protected $auth;
    public function __construct(ClientsService $clientsService,AuthorityService $authorityService)
    {
        $this->client = $clientsService;
        $this->auth = $authorityService;
    }

    /**
     * 客户资料list
     * @param Request $request
     * @return mixed
     */
    public function index(Request $request)
    {
        $brand = $this->auth->readingAuth($request->user()->staff_sn);
        return $this->client->listClient($request,$brand);
    }

    /**
     * 客户资料增加
     *
     * @param ClientRequest $clientRequest
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(ClientRequest $clientRequest)
    {
        $this->auth->actionAuth($clientRequest);
        return $this->client->addClient($clientRequest);
    }

    /**
     * 客户资料修改
     *
     * @param ClientRequest $clientRequest
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(ClientRequest $clientRequest)
    {
        $this->auth->actionAuth($clientRequest);
        $this->nameVerify($clientRequest->route('id'),$clientRequest->name);
        return $this->client->editClient($clientRequest);
    }

    /**
     * 客户资料删除、
     *
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function delete(Request $request)
    {
        $id = $request->route('id');
        $data = ClientHasBrands::where('client_id', $id)->get();
        foreach ($data as $item) {
            $auth = AuthorityGroups::whereHas('staffs', function ($query) use ($request) {
                    $query->where('staff_sn', $request->user()->staff_sn);
                })->WhereHas('editables', function ($query) use ($item) {
                    $query->where('brand_id', $item['brand_id']);
                })->first();
            if ((bool)$auth === true) {
                return $this->client->delClient($request);
                break;
            }
        }
        abort(401, '暂无权限');
    }

    /**
     * 获取单条资料
     *
     * @param Request $request
     * @return mixed
     */
    public function details(Request $request)
    {
        $brand = $this->auth->readingAuth($request->user()->staff_sn);
        return $this->client->firstClient($request,$brand);
    }

    public function nameVerify($id,$name)
    {
        $value = DB::table('clients')->where('id', $id)->first();
        $days = date('Y-m-d:H:i:s', strtotime('-7 days')) < $value->created_at;
        $dname = $name == $value->name;
        if($days === false && $dname === false){
            abort(500,'客户姓名不能修改');
        };
    }
    public function export(Request $request)
    {
        return $this->client->exportClient($request);
    }

    public function import(Request $request)
    {
        return $this->client->importClient();
    }

    /**
     * Excel模板
     *
     * @param Request $request
     */
    public function example()
    {
        $cellData[] = ['姓名', '客户来源', '客户状态', '性别', '电话', '微信', '民族', '身份证号码', '标签', '籍贯', '现住地址', '首次合作时间', '维护人编号', '备注'];
        $cellData[] = ['例：张三', '例：朋友介绍', '-1:黑名单，0:潜在客户，1:合作中，2:合作完毕', '例：女', '例：13333333333', '例：weixin（选填）', '例：汉族', '例：510111199905065215', 'VIP客户,市代客户（多个标签用英文逗号分开）', '例：四川', '例：四川省成都市金牛区万达广场（选填）', '例：2010-10-10', '例：110105（选填）', '例：备注（选填）'];
        $fileName = '客户资料导入模板';
        Excel::create($fileName, function ($excel) use ($cellData) {
            $excel->sheet('score', function ($sheet) use ($cellData) {
                $sheet->rows($cellData);
            });
        })->export('xlsx');
    }
}