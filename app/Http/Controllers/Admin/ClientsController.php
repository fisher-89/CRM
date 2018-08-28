<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\ClientRequest;
use App\Models\AuthorityGroups;
use App\Models\ClientGroupDepartments;
use App\Models\ClientGroupStaff;
use App\Models\ClientHasBrands;
use App\Models\Clients;
use App\Services\Admin\ClientsService;
use Illuminate\Http\Request;
use Excel;
use Illuminate\Support\Facades\Auth;

class ClientsController extends Controller
{
    protected $client;

    public function __construct(ClientsService $clientsService)
    {
        $this->client = $clientsService;
    }

    /**
     * 客户资料list
     * @param Request $request
     * @return mixed
     */
    public function index(Request $request)
    {
        $this->clientReadingAuth($request);
        return $this->client->listClient($request);
    }

    /**
     * 客户资料增加
     *
     * @param ClientRequest $clientRequest
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(ClientRequest $clientRequest)
    {
        $this->clientActionAuth($clientRequest);
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
        $this->clientActionAuth($clientRequest);
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
            $auth = AuthorityGroups::where(['auth_type' => '2', 'auth_brand' => $item['brand_id']])
                ->whereHas('staffs', function ($query) use ($request) {
                    $query->where('staff_sn', $request->user()->staff_sn);
                })->orWhereHas('departments', function ($query) use ($request) {
                    $query->where('department_id', $request->user()->department['id']);
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
        $this->clientReadingAuth($request);
        return $this->client->firstClient($request);
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
     * 查看权限
     *
     * @param $request
     */
    protected function clientReadingAuth($request)
    {
        $staff = AuthorityGroups::where('auth_type', 1)->whereHas('staffs', function ($query) use ($request) {
            $query->where('staff_sn', $request->user()->staff_sn);
        })->orWhereHas('departments', function ($query) use ($request) {
            $query->where('department_id', $request->user()->department['id']);
        })->first();
        if ((bool)$staff === false) {
            abort(401, '暂无权限');
        }
    }

    /**
     * 操作权限
     *
     * @param $request
     */
    protected function clientActionAuth($request)
    {
        if(empty($request->brand_id)){
            abort(404,'未找到的品牌');
        }
        foreach ($request->brand_id as $item) {
        $auth[] = AuthorityGroups::where(['auth_type' => 2, 'auth_brand' => $item['id']])
            ->whereHas('staffs', function ($query) use ($request) {
                $query->where('staff_sn', $request->user()->staff_sn);
            })->orWhereHas('departments', function ($query) use ($request) {
                $query->where('department_id', $request->user()->department['id']);
            })->first();
    }
        $data = isset($auth) ? $auth : [];
        $bool = array_filter($data);
        if ($bool === []) {
            abort(401, '暂无添加权限');
        }
    }

    /**
     * Excel模板
     *
     * @param Request $request
     */
    public function example()
    {
        $cellData[] = ['姓名', '客户来源', '客户状态', '性别', '电话', '微信', '民族', '身份证号码', '标签', '籍贯', '现住地址', '首次合作时间', '维护人编号', '备注'];
        $cellData[] = ['例：张三', '例：朋友介绍', '待合作：0，已合作：1，合作完毕：-1', '例：女', '例：13333333333', '例：weixin（选填）', '例：汉族', '例：510111199905065215', 'VIP客户,市代客户（多个标签用英文逗号分开）', '例：四川', '例：四川省成都市金牛区万达广场（选填）', '例：2010-10-10', '例：110105（选填）', '例：备注（选填）'];
        $fileName = '客户资料导入模板';
        Excel::create($fileName, function ($excel) use ($cellData) {
            $excel->sheet('score', function ($sheet) use ($cellData) {
                $sheet->rows($cellData);
            });
        })->export('xlsx');
    }
}