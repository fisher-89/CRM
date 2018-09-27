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

    public function __construct(ClientsService $clientsService, AuthorityService $authorityService)
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
        return $this->client->listClient($request, $brand);
    }

    /**
     * 客户资料增加
     *
     * @param ClientRequest $clientRequest
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(ClientRequest $clientRequest)
    {
        $OA = $clientRequest->user()->authorities['oa'];
        if (!in_array('188', $OA)) {
            abort(401, '你没有权限操作');
        }
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
        $OA = $clientRequest->user()->authorities['oa'];
        if (!in_array('187', $OA)) {
            abort(401, '你没有权限操作');
        }
        $this->auth->actionAuth($clientRequest);
        $this->nameVerify($clientRequest->route('id'), $clientRequest->name);
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
        $OA = $request->user()->authorities['oa'];
        if (!in_array('178', $OA)) {
            abort(401, '你没有权限操作');
        }
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
        if ($request->user()->staff_sn == 999999) {
            return $this->client->delClient($request);
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
        $OA = $request->user()->authorities['oa'];
        if (!in_array('177', $OA)) {
            abort(401, '你没有权限操作');
        }
        $brand = $this->auth->readingAuth($request->user()->staff_sn);
        if ($request->user()->staff_sn == 999999) {
            $brand = $this->auth->userAuthentication();
        }
        return $this->client->firstClient($request, $brand);
    }

    public function nameVerify($id, $name)
    {
        $value = DB::table('clients')->where('id', $id)->first();
        $days = date('Y-m-d:H:i:s', strtotime('-7 days')) < $value->created_at;
        $dname = $name == $value->name;
        if ($days === false && $dname === false) {
            abort(500, '客户姓名不能修改');
        };
    }

    public function export(Request $request)
    {
        $brand = $this->auth->readingAuth($request->user()->staff_sn);
        if ($request->user()->staff_sn == 999999) {
            $brand = $this->auth->userAuthentication();
        }
        return $this->client->exportClient($request, $brand);
    }

    public function import(Request $request)
    {
        return $this->client->importClient($request);
    }

    /**
     * Excel模板
     *
     * @param Request $request
     */
    public function example()
    {
        $source = DB::table('source')->get();
        $sourceData = array_column($source == null ? [] : $source->toArray(), 'name');//来源
        $status = ['黑名单', '潜在客户', '合作中', '合作完毕'];
        $statusData = $status;
        $brand = ['集团公司', '杰尼威尼专卖', 'JV专卖', '利鲨', 'GO', 'GO快销', '杰尼威尼2部', '利鲨快销', 'GO加盟', 'GO批发', 'GO专卖', '杰尼威尼', 'JV', '女装'];
        $brandData = $brand;
        $nations = DB::table('nations')->get();
        $nationsData = array_column($nations == null ? [] : $nations->toArray(), 'name');//民族
        $tags = DB::table('tags')->get();
        $tagsData = array_column($tags == null ? [] : $tags->toArray(), 'name');//标签
        $provincial = DB::table('provincial')->get();
        $provincialData = array_column($provincial == null ? [] : $provincial->toArray(), 'name');//籍贯
        $max = count(max($sourceData, $statusData, $brandData, $nationsData, $tagsData, $provincialData));
        $data[] = ['客户来源', '客户状态', '合作品牌', '民族', '标签', '籍贯'];
        for ($i = 0; $i < $max; $i++) {
            $data[] = [
                isset($sourceData[$i]) ? $sourceData[$i] : '',
                isset($statusData[$i]) ? $statusData[$i] : '',
                isset($brandData[$i]) ? $brandData[$i] : '',
                isset($nationsData[$i]) ? $nationsData[$i] : '',
                isset($tagsData[$i]) ? $tagsData[$i] : '',
                isset($provincialData[$i]) ? $provincialData[$i] : '',
            ];
        }
        $cellData[] = ['姓名', '客户来源', '客户状态', '合作品牌', '性别', '电话', '微信', '民族', '身份证号码', '标签', '籍贯', '首次合作时间', '维护人员工编号', '备注'];
        $cellData[] = ['例：张三', '例：朋友介绍', '例：合作中', '例：杰尼威尼专卖', '例：女', '例：13333333333', '例：weixin', '例：汉族', '例：510111199905065215', 'VIP客户,市代客户（多个标签用英文逗号分开）', '例：四川省（选填）', '例：2010-01-01', '例：110105（选填）', '例：备注（选填）'];
        $fileName = '客户资料导入模板';
        $tot = count($cellData);
        $maxi = $max + 1;
        Excel::create($fileName, function ($excel) use ($cellData, $data, $tot, $maxi) {
            $excel->sheet('主表', function ($sheet) use ($cellData, $tot) {
                $sheet->rows($cellData);
                $sheet->cells('A1:N1', function ($cells) {
                    $cells->setAlignment('center');
                    $cells->setBackground('#D2E9FF');
                });
                $sheet->setColumnFormat(array(
                    'I' => '@',
                    'L' => 'yyyy-mm-dd',
                ));
                $sheet->cells('A2:N' . $tot, function ($cells) {
                    $cells->setAlignment('center');
                });
                $sheet->setAutoSize(true);
            });
            $excel->sheet('辅助附表', function ($sheet) use ($data, $maxi) {
                $sheet->rows($data);
                $sheet->cells('A1:F1', function ($cells) {
                    $cells->setAlignment('center');
                    $cells->setBackground('#D2E9FF');
                });
                $sheet->cells('A2:F' . $maxi, function ($cells) {
                    $cells->setAlignment('center');
                });
                $sheet->setAutoSize(true);
            });
        })->export('xlsx');
    }
}