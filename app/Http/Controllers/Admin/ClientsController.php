<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\ClientRequest;
use App\Models\AuthGroupHasEditableBrands;
use App\Models\AuthGroupHasVisibleBrands;
use App\Services\Admin\AuthorityService;
use App\Services\Admin\ClientsService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\AuthorityGroups;
use App\Models\ClientHasBrands;
use Illuminate\Http\Request;
use App\Models\Clients;
use App\Http\Requests;
use Validator;
use Excel;

class ClientsController extends Controller
{
    protected $client;
    protected $status;
    protected $error;
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

    /**
     * Excel 导出
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function export(Request $request)
    {
        $brand = $this->auth->readingAuth($request->user()->staff_sn);
        if ($request->user()->staff_sn == 999999) {
            $brand = $this->auth->userAuthentication();
        }
        return $this->client->exportClient($request, $brand);
    }

    /**
     * Excel导入
     *
     * @param Request $request
     * @return mixed
     * return $this->client->importClient($request);
     */
    public function import(Request $request)
    {
        if (!$request->hasFile('file')) {
            abort(400, '未选择文件');
        }
        $excelPath = $request->file('file');
        if (!$excelPath->isValid()) {
            abort(400, '文件上传出错');
        }
        $res = [];
        try {
            Excel::selectSheets('主表')->load($excelPath, function ($matter) use (&$res) {
                $matter = $matter->getSheet();
                $res = $matter->toArray();
            });
        } catch (\Exception $exception) {
            abort(404, '未找到主表');
        }
        if (!isset($res[1]) || implode($res[1]) == '') {
            abort(404, '未找到导入数据');
        }
        try {
            $brand = app('api')->getBrands([1, 2]);
        } catch (\Exception $exception) {
            abort(500, '调取数据错误');
        }
        $header = $res[0];
        for ($i = 1; $i < count($res); $i++) {
            $this->error = [];
//            if (trim($res[$i][14]) == true) {
//                $oaData = app('api')->withRealException()->getStaff(trim($res[$i][15]));
//            }
            $transNum = $this->strTransNum(trim($res[$i][2]));
            $this->status = $transNum;
            $source = trim($res[$i][1]) != '' ? $this->getSource(trim($res[$i][1])) : $transNum == '0' ? null : $this->error['来源'][] = '不能为空';
            $brandId = (bool)trim($res[$i][3]) == true ? $this->getBrandId($brand, trim($res[$i][3])) : $this->error['合作品牌'][] = '不能为空';
            $level = isset($res[$i][4]) ? trim($res[$i][4]) != '' ? $this->getLevelsId(trim($res[$i][4])) : '' : $this->error['客户等级'][] = '不能为空';
            $linkage = isset($res[$i][5]) ? trim($res[$i][5]) != '' ? $this->getLinkagesId(trim($res[$i][5])) : '' : $this->error['合作省份'][] = '不能为空';
            $tagId = isset($res[$i][11]) ? trim($res[$i][11]) != '' ? $this->getTagId(trim($res[$i][11])) : '' : $this->error['标签'][] = '不能为空';
            $arr = [
                'name' => trim($res[$i][0]),
                'source_id' => $source,
                'status' => $transNum,
                'brands' => $brandId,
                'levels' => $level,
                'linkages' => $linkage,
                'gender' => trim($res[$i][6]),
                'mobile' => trim($res[$i][7]),
                'wechat' => trim($res[$i][8]),
                'nation' => trim($res[$i][9]),
                'id_card_number' => trim($res[$i][10]),
                'tag_id' => $tagId,
                'native_place' => trim($res[$i][12]),
                'first_cooperation_at' => (bool)trim($res[$i][13]) == false ? null : $res[$i][13],
                'develop_sn' => $this->resole($res[$i][14], 0, '开发人编号'),
                'develop_name' => $this->resole($res[$i][14], 1, '开发人姓名'),
                'vindicator_sn' => $this->resole($res[$i][15], 0, '维护人编号'),
                'vindicator_name' => $this->resole($res[$i][15], 1, '维护人姓名'),
                'remark' => trim($res[$i][16]),
            ];
            $request = new Requests\Admin\ClientRequest($arr);
            $this->excelVerify($request);
            if ($this->error == []) {
                $arr['id_card_number'] = (bool)$arr['id_card_number'] == false ? uniqid('auto') : $arr['id_card_number'];
                $data = $this->client->excelSaveClient($arr);
                if((bool)$arr['brands'] === true){
                    $brandArray = [];
                    foreach ($arr['brands'] as $value) {
                        $brandArray[] = [
                            'client_id' => $data->id,
                            'brand_id' => $value,
                        ];
                    }
                    $this->client->excelSaveBrand($brandArray);
                }
                if ((bool)$arr['tag_id'] === true) {
                    $tagsArray = [];
                    foreach ($arr['tag_id'] as $item) {
                        $tagsArray[] = [
                            'client_id' => $data->id,
                            'tag_id' => $item,
                        ];
                    }
                    $this->client->excelSaveTags($tagsArray);
                }
                if ((bool)$arr['levels'] === true) {
                    $levelArray = [];
                    foreach ($arr['levels'] as $items) {
                        $levelArray[] = [
                            'client_id' => $data->id,
                            'level_id' => $items,
                        ];
                    }
                    $this->client->excelSaveLevels($levelArray);
                }
                if ((bool)$arr['linkages'] === true) {
                    $linkageArray = [];
                    foreach ($arr['linkages'] as $val) {
                        $linkageArray[] = [
                            'client_id' => $data->id,
                            'linkage_id' => $val,
                        ];
                    }
                    $this->client->excelSaveLinkages($linkageArray);
                }
                if ($data == true) {
                    $success[] = $data;
                }
            } else {
                $errors['row'] = $i + 1;
                $errors['rowData'] = $res[$i];
                $errors['message'] = $this->error;
                $mistake[] = $errors;
                continue;
            }
        }
        $info['data'] = isset($success) ? $success : [];
        $info['headers'] = isset($header) ? $header : [];
        $info['errors'] = isset($mistake) ? $mistake : [];
        return $info;
    }

    /**
     * 数据验证
     * unique:tag_types,name
     * @param $request
     */
    protected function excelVerify($request)
    {
        try {
            $this->validate($request,
                [
                    'name' => 'required|max:10',
                    'gender' => ['required', 'max:1', function ($attribute, $value, $event) {
                        if ($value != '男' && $value != '女') {
                            return $event('不正确');
                        }
                    }],
                    'mobile' => ['required', 'digits:11', 'regex:/^1[3456789]\d{9}$/', 'unique:clients,mobile'],
                    'wechat' => 'max:20|nullable|regex:/^[a-zA-Z0-9_]+$/',
                    'nation' => 'max:5|exists:nations,name',
                    'id_card_number' => [$this->status == 0 ? 'nullable' : 'required', 'unique:clients,id_card_number', 'max:18',
                        'regex:/(^[1-9]\d{5}(18|19|([23]\d))\d{2}((0[1-9])|(10|11|12))(([0-2][1-9])|10|20|30|31)\d{3}[0-9Xx]$)|(^[1-9]\d{5}\d{2}((0[1-9])|(10|11|12))(([0-2][1-9])|10|20|30|31)\d{2}$)/'],
                    'native_place' => 'nullable|max:8|exists:provinces,name',
                    'present_address' => 'nullable|max:150',
                    'first_cooperation_at' => 'nullable|date',
                    'develop_sn' => 'numeric|nullable|digits:6',
                    'develop_name' => 'max:10',
                    'vindicator_sn' => 'numeric|nullable|digits:6',
                    'vindicator_name' => 'max:10',
                    'brands.*' =>  'required',
                    'levels.*' => $this->status == 0 ? 'nullable' : 'required',
                    'linkages.*' => $this->status == 0 ? 'nullable' : 'required',
                    'remark' => 'max:200',
                    'shops' => 'array|nullable',
                    'shops.*.shop_sn' => [
                        'required',
                    ]
                ]
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            foreach ($e->validator->errors()->getMessages() as $key => $value) {
                $this->error[$this->conversion($key)] = $this->conversionValue($value);
            }
        } catch (\Exception $e) {
            $this->error['message'] = '系统异常：' . $e->getMessage();
        }
    }

    protected function resole($string, $number, $key)
    {
        if ((bool)trim($string) === false) {
            return null;
        }
        $arr = explode(',', $string);
        if (count($arr) != 2) {
            $this->error[$key][] = '不正确';
        } else {
            return $arr[$number];
        }
    }

    protected function getLinkagesId($str)
    {
        $arr = explode(',', $str);
        if (count(array_unique($arr)) < count($arr)) {
            $this->error['合作省份'][] = '存在重复';
        }
        $e = [];
        $n = 0;
        foreach ($arr as $item) {
            $n++;
            $id = DB::table('linkage')->where(['name' => $item, 'level' => 1])->value('id');
            if (false == (bool)$id) {
                $e[] = $n;
            }
            $a[] = $id;
        }
        $null = isset($e) ? implode(',', $e) : '';
        if ($null == '') {
            return isset($a) ? $a : [];
        } else {
            $this->error['合作省份'][] = '第' . implode('、', $e) . '未找到';
        }
    }

    protected function getLevelsId($str)
    {
        $arr = explode(',', $str);
        if (count(array_unique($arr)) < count($arr)) {
            $this->error['客户等级'][] = '存在重复';
        }
        $e = [];
        $n = 0;
        foreach ($arr as $item) {
            $n++;
            $id = DB::table('levels')->where('name', $item)->value('id');
            if (false == (bool)$id) {
                $e[] = $n;
            }
            $a[] = $id;
        }
        $null = isset($e) ? implode(',', $e) : '';
        if ($null == '') {
            return isset($a) ? $a : [];
        } else {
            $this->error['客户等级'][] = '第' . implode('、', $e) . '未找到';
        }
    }

    protected function conversionValue($value)
    {
        $array = [];
        foreach ($value as $item) {
            $arr = explode(' ', $item);
            if (count($arr) > 2) {
                $clean = [];
                foreach ($arr as $items) {
                    if (preg_match('/^[A-Za-z]+$/', $items)) {
                        unset($items);
                    } else {
                        $clean[] = $items;
                    }
                }
                $array[] = implode($clean);
            } else {
                $array[] = isset($arr[1]) ? $arr[1] : $arr[0];
            }
        }
        return $array;
    }

    protected function conversion($str)
    {
        $arr = [
            'name' => '客户姓名',
            'source_id' => '客户来源',
            'status' => '客户状态',
            'gender' => '性别',
            'mobile' => '电话',
            'wechat' => '微信',
            'nation' => '民族',
            'id_card_number' => '身份证号码',
            'native_place' => '籍贯',
            'present_address' => '现住地址',
            'tag_id' => '标签',
            'first_cooperation_at' => '第一次合作时间',
            'develop_sn' => '开发员工编号',
            'develop_name' => '开发员工姓名',
            'vindicator_sn' => '维护人编号',
            'vindicator_name' => '维护人姓名',
            'remark' => '备注',
            'brand' => '品牌',
        ];
        return $arr[$str];
    }

    protected function getSource($str)
    {
        $source = DB::table('source')->where('name', $str)->value('id');
        if ($source == true) {
            return $source;
        } else {
            $this->error['来源'][] = '错误';
        }
    }

    protected function getBrandId($brand, $str)
    {
        $explode = explode(',', $str);
        if (count(array_unique($explode)) < count($explode)) {
            $this->error['合作品牌'][] = '存在重复';
        }
        $brandId = [];
        foreach ($brand as $item) {
            if (in_array($item['name'], $explode)) {
                $brandId[] = $item['id'];
            }
        }
        if (isset($brandId)) {
            return $brandId;
        } else {
            if (count($brandId) < count($explode)) {
                $this->error['合作品牌'][] = '名字个别错误';
            } else if ($brandId == []) {
                $this->error['合作品牌'][] = '名字全部错误';
            }
        }
    }

    protected function getTagId($str)
    {
        $arr = explode(',', $str);
        if (count(array_unique($arr)) < count($arr)) {
            $this->error['标签'][] = '存在重复';
        }
        $e = [];
        $n = 0;
        foreach ($arr as $item) {
            $n++;
            $id = DB::table('tags')->where('name', $item)->value('id');
            if (false == (bool)$id) {
                $e[] = $n;
            }
            $a[] = $id;
        }
        $null = isset($e) ? implode(',', $e) : '';
        if ($null == '') {
            return isset($a) ? $a : [];
        } else {
            $this->error['标签'][] = '第' . implode('、', $e) . '未找到';
        }
    }

    protected function strTransNum($str)
    {
        $arr = [
            '黑名单' => '-1',
            '潜在客户' => '0',
            '合作中' => '1',
            '合作完毕' => '2',
        ];
        if (!isset($arr[$str])) {
            $this->error['状态'][] = '不存在';
        } else {
            return $arr[$str];
        }
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

        $province = DB::table('provinces')->get();
        $provincialData = array_column($province == null ? [] : $province->toArray(), 'name');//籍贯

        $level = DB::table('levels')->get();
        $levelData = array_column($level == null ? [] : $level->toArray(), 'name');//标签

        $explain = [
            '其中合作省份、标签可填多个，请注意用英文逗号分',
            '隔（一定要英文逗号），开发员工和维护员格式为：',
            '员工编号,员工姓名；例如：100000,陈某（也必须是',
            '英文逗号分隔）',
        ];
        $null = [
            '',
        ];
        $str = [
            '必填',
            '',
            '',
            '',
            '潜在客户选填',
            '',
            '',
            '',
            '选填'
        ];

        $max = count(max($sourceData, $statusData, $brandData, $nationsData, $tagsData, $provincialData,$explain,$null,$str));
        $data[] = ['客户来源', '客户状态', '合作品牌', '民族', '标签', '客户等级', '籍贯/合作省份', '主表填写说明：表头背景为红色是必填栏，黄色为潜'];
        for ($i = 0; $i < $max; $i++) {
            $data[] = [
                isset($sourceData[$i]) ? $sourceData[$i] : '',
                isset($statusData[$i]) ? $statusData[$i] : '',
                isset($brandData[$i]) ? $brandData[$i] : '',
                isset($nationsData[$i]) ? $nationsData[$i] : '',
                isset($tagsData[$i]) ? $tagsData[$i] : '',
                isset($levelData[$i]) ? $levelData[$i] : '',
                isset($provincialData[$i]) ? $provincialData[$i] : '',
                isset($explain[$i]) ? $explain[$i] : '',
                isset($null[$i]) ? $null[$i] : '',
                isset($str[$i]) ? $str[$i] : '',
            ];
        }
        $cellData[] = ['姓名', '客户来源', '客户状态', '合作品牌', '客户等级', '合作省份', '性别', '电话', '微信', '民族', '身份证号码', '标签', '籍贯', '首次合作时间', '开发员工编号', '维护员工编号', '备注'];
        $cellData[] = ['例：张三', '例：朋友介绍', '例：合作中', '例：杰尼威尼专卖,利鲨(多个品牌用英文逗号分开)', '请以辅助表数据填写', '例：成都市', '例：女', '例：13333333333', '例：weixin', '例：汉族', '例：510111199905065215', 'VIP客户,市代客户（多个标签用英文逗号分开）', '例：四川省', '例：2010-01-01', '例：110000,王某某', '例：110105,刘某某', '例：备注'];
        $fileName = '客户资料导入模板';
        $tot = count($cellData);
        $maxi = $max + 1;
        Excel::create($fileName, function ($excel) use ($cellData, $data, $tot, $maxi) {
            $excel->sheet('辅助附表', function ($sheet) use ($data, $maxi) {
                $sheet->rows($data);
                $sheet->cells('A1:G1', function ($cells) {
                    $cells->setAlignment('center');
                    $cells->setBackground('#D2E9FF');
                });
                $sheet->cells('H1:H5', function ($cells) {
                    $cells->setBackground('#B0C4DE');
                });
                $sheet->cells('A2:G' . $maxi, function ($cells) {
                    $cells->setAlignment('center');
                });
                $sheet->cells('I1:I3',function($cells){
                    $cells->setBackground('#55ACFD');
                });
                $sheet->cells('I5:I7',function($cells){
                    $cells->setBackground('#FFC25F');
                });
                $sheet->cells('I9:I11',function($cells){
                    $cells->setBackground('#4FDADA');
                });
                $sheet->setAutoSize(true);
            });
            $excel->sheet('主表', function ($sheet) use ($cellData, $tot) {
                $sheet->rows($cellData);
                $sheet->cells('A1:Q1', function ($cells) {
                    $cells->setAlignment('center');
                });
                $sheet->cells('A1', function ($cells) {
                    $cells->setBackground('#55ACFD');
                });
                $sheet->cells('C1:D1', function ($cells) {
                    $cells->setBackground('#55ACFD');
                });
                $sheet->cells('G1:H1', function ($cells) {
                    $cells->setBackground('#55ACFD');
                });

                $sheet->cells('B1', function ($cells) {
                    $cells->setBackground('#FFC25F');
                });
                $sheet->cells('E1:F1', function ($cells) {
                    $cells->setBackground('#FFC25F');
                });
                $sheet->cells('K1', function ($cells) {
                    $cells->setBackground('#FFC25F');
                });

                $sheet->cells('I1:J1', function ($cells) {
                    $cells->setBackground('#4FDADA');
                });
                $sheet->cells('L1:Q1', function ($cells) {
                    $cells->setBackground('#4FDADA');
                });
                $sheet->setColumnFormat(array(
                    'A' => '@', 'B' => '@', 'C' => '@', 'D' => '@', 'E' => '@', 'F' => '@',
                    'G' => '@', 'H' => '@', 'I' => '@', 'J' => '@', 'K' => '@', 'L' => '@',
                    'M' => '@', 'N' => '@', 'O' => '@', 'P' => '@', 'q' => '@',
                ));
                $sheet->cells('A2:Q' . $tot, function ($cells) {
                    $cells->setAlignment('center');
                });
                $sheet->setAutoSize(true);
            });
        })->export('xlsx');
    }
}