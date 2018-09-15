<?php

namespace App\Services\Admin;

use App\Models\AuthorityGroups;
use App\Http\Resources\ClientsCollection;
use App\Models\AuthGroupHasEditableBrands;
use App\Models\AuthGroupHasVisibleBrands;
use App\Models\ClientHasBrands;
use App\Models\ClientHasShops;
use App\Models\ClientHasTags;
use App\Models\ClientLogs;
use App\Models\Nations;
use App\Models\Clients;
use App\Models\Source;
use App\Models\Tags;
use Excel;
use DB;
use Illuminate\Support\Facades\Auth;

class ClientsService
{
    use Traits\GetInfo;
    protected $tags;
    protected $client;
    protected $source;
    protected $nations;
    protected $clientLogs;
    protected $clientHasTags;
    protected $clientHasShops;
    protected $clientHasBrands;

    public function __construct(Clients $clients, ClientHasTags $clientHasTags, Source $source, Nations $nations,
                                Tags $tags, ClientHasShops $clientHasShops, ClientHasBrands $clientHasBrands, ClientLogs $clientLogs)
    {
        $this->tags = $tags;
        $this->source = $source;
        $this->client = $clients;
        $this->nations = $nations;
        $this->clientLogs = $clientLogs;
        $this->clientHasTags = $clientHasTags;
        $this->clientHasShops = $clientHasShops;
        $this->clientHasBrands = $clientHasBrands;
    }

    public function listClient($request, $brand)
    {
//        foreach ($brand as $key => $value) {
//            foreach ($value['visibles'] as $k => $v) {
//                $arrData[] = $v['brand_id'];
//            }
//        }
//        $arr = isset($arrData) ? $arrData : [];
        $list = $this->client->with('tags')->with('shops')->with('brands')
//            ->whereHas('brands', function ($query) use ($arr) {
//                $query->whereIn('brand_id', $arr);})
            ->filterByQueryString()->SortByQueryString()->withPagination($request->get('pagesize', 10));
        if (isset($list['data'])) {
            $list['data'] = new ClientsCollection(collect($list['data']));
            return $list;
        } else {
            return new ClientsCollection($list);
        }
    }

    public function addClient($request)
    {
        $all = $request->all();
        try {
            DB::beginTransaction();
            $bool = $this->client->create($all);
            if ((bool)$bool === false) {
                DB::rollback();
                abort(400, '客户添加失败');
            }
            if (isset($request->tags) && $request->tags != []) {
                foreach ($request->tags as $k => $v) {
                    $tagSql = [
                        'client_id' => $bool->id,
                        'tag_id' => $v['tag_id'],
                    ];
                    $this->clientHasTags->create($tagSql);
                }
            }
            if (isset($request->brands) && $request->brands != []) {
                foreach ($request->brands as $item) {
                    $brandSql = [
                        'client_id' => $bool->id,
                        'brand_id' => $item['brand_id'],
                    ];
                    $this->clientHasBrands->create($brandSql);
                }
            }
            if (isset($request->shops) && $request->shops != []) {
                foreach ($request->shops as $items) {
                    $shopSql = [
                        'client_id' => $bool->id,
                        'shop_sn' => $items['shop_sn'],
                    ];
                    $this->clientHasShops->create($shopSql);
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            abort(400, '客户添加失败');
        }
        return response()->json($this->client->with('tags')->with('shops')->with('brands')->where('id', $bool->id)->first(), 201);
    }

    public function editClient($request)
    {
        $all = $request->all();
        $clientData = $this->client->with('tags')->with('shops')->with('brands')->find($request->route('id'));
        if ((bool)$clientData === false) {
            abort(404, '未找到数据');
        }
        $specialHandling = clone $clientData;
//        try {
//            DB::beginTransaction();
        $clientData->update($all);
        if ((bool)$clientData === false) {
            DB::rollback();
            abort(400, '客户修改失败');
        }
        $this->clientHasTags->where('client_id', $clientData->id)->delete();
        $this->clientHasShops->where('client_id', $clientData->id)->delete();
        $this->clientHasBrands->where('client_id', $clientData->id)->delete();
        if (isset($request->tags)) {
            if ($request->tags != []) {
                foreach ($request->tags as $k => $v) {
                    $sql = [
                        'client_id' => $clientData->id,
                        'tag_id' => $v['tag_id'],
                    ];
                    $this->clientHasTags->create($sql);
                }
            }
        }
        if (isset($request->brands)) {
            if ($request->brands != []) {
                foreach ($request->brands as $item) {
                    $brandSql = [
                        'client_id' => $clientData->id,
                        'brand_id' => $item['brand_id'],
                    ];
                    $this->clientHasBrands->create($brandSql);
                }
            }
        }
        if (isset($request->shops)) {
            if ($request->shops != []) {
                foreach ($request->shops as $items) {
                    $shopSql = [
                        'client_id' => $clientData->id,
                        'shop_sn' => $items['shop_sn'],
                    ];
                    $this->clientHasShops->create($shopSql);
                }
            }
        }
        $this->saveClientLog($specialHandling, $all, $request);
//            DB::commit();
//        } catch (\Exception $e) {
//            DB::rollback();
//            abort(400, '客户修改失败');
//        }
        return response($this->client->with('tags')->with('shops')->with('brands')->where('id', $clientData->id)->first(), 201);
    }

    private function saveClientLog($model, $commit, $request)
    {
        $model = $model->toArray();
//        $model->fill($commit);
//        $changes = $this->getDirtyWithOriginal($model);dd($changes);
        foreach ($model['tags'] as $i) {
            $tags[] = $i['tag_id'];
        }
        $tag = isset($tags) ? $tags : [];
        $tag = $this->sort($tag);
        $model['tags'] = implode(',', $tag);

        foreach ($model['brands'] as $item) {
            $brands[] = $item['brand_id'];
        }
        $brand = isset($brands) ? $brands : [];
        $brand = $this->sort($brand);
        $model['brands'] = implode(',', $brand);

        foreach ($model['shops'] as $items) {
            $shops[] = $items['shop_sn'];
        }
        $shop = isset($shops) ? $shops : [];
        $shop = $this->sort($shop);
        $model['shops'] = implode(',', $shop);

        foreach ($commit['tags'] as $v) {
            $commitTag[] = $v['tag_id'];
        }
        $commitTags = isset($commitTag) ? $commitTag : [];
        $commitTags = $this->sort($commitTags);
        $commit['tags'] = implode(',', $commitTags);

        foreach ($commit['brands'] as $v) {
            $commitBrand[] = $v['brand_id'];
        }
        $commitBrands = isset($commitBrand) ? $commitBrand : [];
        $commitBrands = $this->sort($commitBrands);
        $commit['brands'] = implode(',', $commitBrands);

        foreach ($commit['shops'] as $v) {
            $commitShop[] = $v['shop_sn'];
        }
        $commitShops = isset($commitShop) ? $commitShop : [];
        $commitShops = $this->sort($commitShops);
        $commit['shops'] = implode(',', $commitShops);
        $array = array_diff($commit, $model);
        foreach ($array as $key => $value) {
            if ($model[$key] != $commit[$key]) {
                $changes[$key] = [$model[$key], $commit[$key]];
            }
        }
        $clientLogSql = [
            'client_id' => $model['id'],
            'type' => '后台修改',
            'staff_sn' => $request->user()->staff_sn,
            'staff_name' => $request->user()->realname,
            'operation_address' =>
                [
                    '电话号码' => $this->getOperation(),
                    '设备类型' => $this->getPhoneType(),
                    'IP地址' => $request->getClientIp()
                ],
            'changes' => isset($changes) ? $changes : [],
            'restore_sn' => $this->identifying($model['id']),
            'restore_time' => null,
        ];
        $this->clientLogs->create($clientLogSql);
    }

    protected function identifying($id)
    {
        $log = $this->clientLogs->where('client_id', $id)->where('restore_sn', 1)->orderBy('id', 'desc')->first();
        if ($log == true) {
            $logSql = [
                'restore_sn' => 0,
                'restore_time' => null
            ];
            $log->update($logSql);
        }
        return 1;
    }

    protected function getDirtyWithOriginal($model)
    {
        $dirty = [];
        foreach ($model->getDirty() as $key => $value) {
            $dirty[$key] = [
                'original' => $model->getOriginal($key, ''),
                'dirty' => $value,
            ];
        }
        return $dirty;
    }

    private function sort($arr)
    {
        $length = count($arr);
        for ($n = 0; $n < $length - 1; $n++) {
            for ($i = 0; $i < $length - $n - 1; $i++) {
                if ($arr[$i] > $arr[$i + 1]) {
                    $temp = $arr[$i + 1];
                    $arr[$i + 1] = $arr[$i];
                    $arr[$i] = $temp;
                }
            }
        }
        return $arr;
    }

    public function delClient($request)
    {
        $id = $request->route('id');
        $client = $this->client->find($id);
        if ((bool)$client === false) {
            abort(404, '未找到数据');
        }
        try {
            DB::beginTransaction();
//            $this->clientHasTags->where('client_id', $id)->delete();
//            $this->clientHasShops->where('client_id', $id)->delete();
//            $this->clientHasBrands->where('client_id', $id)->delete();
            $client->delete();
            $clientLogSql = [
                'client_id' => $id,
                'type' => '后台删除',
                'staff_sn' => $request->user()->staff_sn,
                'staff_name' => $request->user()->realname,
                'operation_address' =>
                    [
                        '电话号码' => $this->getOperation(),
                        '设备类型' => $this->getPhoneType(),
                        'IP地址' => $request->getClientIp()
                    ],
                'changes' => [],
                'identifying' => 0,
                'log_id' => '',
            ];
            $this->clientLogs->create($clientLogSql);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            abort(400, '客户删除失败');
        }
        return response('', 204);
    }

    /**
     * 详细页面
     *
     * @param $id
     * @return mixed
     */
    public function firstClient($request, $brand)
    {
        foreach ($brand as $key => $value) {
            foreach ($value['visibles'] as $k => $v) {
                $arrData[] = $v['brand_id'];
            }
        }
        $arr = isset($arrData) ? $arrData : [];
        return $this->client->with('tags')->with('brands')->with('shops')
            ->where('id', $request->route('id'))->whereHas('brands', function ($query) use ($arr) {
                $query->whereIn('brand_id', $arr);
            })->first();
    }

    protected function transform($data)
    {
        $arr = [
            '-1' => '合作完毕',
            '0' => '待合作',
            '1' => '合作中',
        ];
        return $arr[$data];
    }

    protected function transTags($arr)
    {
        foreach ($arr as $key => $val) {
            $data[] = $val['tag']['name'];
        }
        return implode(',', $data);
    }

//导出
    public function exportClient($request)
    {
        $all = $request->all();
        if (array_key_exists('page', $all) || array_key_exists('pagesize', $all)) {
            abort(400, '传递无效参数');
        }
        $arr = $this->authDetection($request);
        $client = $this->client->with('source')->with('tags')->with('brands')->with('shops')
            ->whereHas('brands', function ($query) use ($arr) {
                $query->whereIn('brand_id', $arr);
            })->filterByQueryString()->withPagination();
        if (false == (bool)$client) {
            return response()->json(['message' => '没有找到符号条件的数据'], 404);
        }
        $eventTop[] = ['姓名', '客户来源', '客户状态', '性别', '电话', '微信', '民族', '身份证号码', '标签',
            '籍贯', '现住地址', '首次合作时间', '维护人编号',
//            '合作品牌','合作地区','地址店铺',
            '备注'];
        foreach ($client as $k => $v) {
            $eventTop[] = [$v['name'], $v['source']['name'], $this->transform($v['status']), $v['gender'], $v['mobile'],
                $v['wechat'], $v['nation'], $v['id_card_number'], $v['tags'] ? $this->transTags($v['tags']) : '', $v['native_place'],
                $v['present_address'], $v['first_cooperation_at'], $v['vindicator_sn'] . ',' . $v['vindicator_name'], $v['remark']
            ];
        }
        Excel::create('客户信息资料', function ($excel) use ($eventTop) {
            $excel->sheet('score', function ($query) use ($eventTop) {
                $query->rows($eventTop);
            });
        })->export('xlsx');
    }

//导入  todo  导入人权限品牌验证，合作店铺，合作品牌，合作区域   没弄
    public function importClient()
    {
        if (isset($_FILES['file']['tmp_name']) === false) {
            abort(400, '未选择文件');
        };
        $excelPath = $_FILES['file']['tmp_name'];
        $res = [];
        Excel::load($excelPath, function ($matter) use (&$res) {
            $matter = $matter->getSheet();
            $res = $matter->toArray();
        });
        for ($i = 1; $i < count($res); $i++) {
            $err = [];
            $l = $i + 1;
            if (count($res[$i]) != 14) {
                $err['序号:' . $l][] = '文件布局错误';
            }
            if (strlen($res[$i][0]) > 10) {
                $err[$res[$i][0]][] = '名字过长';
            }
            if (strlen($res[$i][0]) < 2) {
                $err[$res[$i][0]][] = '名字过短';
            }
            if (empty($res[$i][1])) {
                $err['序号:' . $l][] = '客户来源不能为空';
            } else {
                $bool = $this->source->where('name', $res[$i][1])->value('id');
                if (false === (bool)$bool) {
                    $err[$res[$i][0]][] = '未找到的客户来源';
                }
            }
            if (strlen($res[$i][2]) < 3) {
                $err['序号:' . $l][] = '未知的客户状态';
            } else if ($res[$i][2] == '待合作' || $res[$i][2] == '已合作' || $res[$i][2] == '合作完毕') {
                if ($this->strTransNum($res[$i][2]) === false) {
                    $err[$res[$i][2]][] = '未知的客户状态';
                }
            } else {
                $err[$res[$i][2]][] = '未知的客户状态';
            }
            if ($res[$i][3] != '男' && $res[$i][3] != '女') {
                $err[$res[$i][3]][] = '未知的性别';
            }
            if (empty($res[$i][4])) {
                $err['序号:' . $res[$l]] = '电话必须填写';
            } else {
                if (!is_numeric($res[$i][4])) {
                    $err[$res[$i][4]][] = '电话必须是数字';
                }
                if (strlen($res[$i][4]) < 11 || strlen($res[$i][4]) > 14) {
                    $err[$res[$i][4]][] = '电话号码位数不正确';
                }
                $mobile = $this->client->where('mobile', $res[$i][4])->first();
                if (true === (bool)$mobile) {
                    $err[$res[$i][4]][] = '电话号码已存在';
                }
            }
            if (isset($res[$i][5]) || strlen($res[$i][5]) > 20) {
                $res[$i][5][] = '微信号过长';
            }
            if (strlen($res[$i][6]) > 15) {
                $err[$res[$i][6]][] = '民族过长';
            } else {
                $nations = $this->nations->where('name', $res[$i][6])->first();
                if ($nations == false) {
                    $err[$res[$i][6]][] = '未知的民族';
                }
            }
            if (!preg_match('/^[1-9][0-9]{5}(19|20)[0-9]{2}((01|03|05|07|08|10|12)(0[1-9]|[1-2][0-9]|31)|(04|06|09|11)(0[1-9]|[1-2][0-9]|30)|02(0[1-9]|[1-2][0-9]))[0-9]{3}([0-9]|x|X)$/', $res[$i][7])) {
                $err[$res[$i][7]][] = '错误的身份证号码';
            } else {
                $card = $this->client->where('id_card_number', $res[$i][7])->first();
                if ((bool)$card === true) {
                    $err[$res[$i][7]][] = '身份证号码已存在';
                }
            }
            if (empty($res[$i][8])) {
                $err['序号:' . $res[$l]][] = '标签不能为空';
            } else {
                $arr = explode(',', $res[$i][8]);
                $e = [];
                $n = 0;
                foreach ($arr as $item) {
                    $n++;
                    $id = $this->tags->where('name', $item)->value('id');
                    if (false == (bool)$id) {
                        $e[] = $n;
                    }
                    $a[] = $id;
                }
                $tags = isset($e) ? implode(',', $e) : '';
                if (true === (bool)$tags) {
                    $err[$res[$i][8]] = '第' . $tags . '标签未找到';
                }
            }
            if (empty($res[$i][9])) {
                $err['序号:' . $res[$l]][] = '籍贯不能为空';
            } else if (strlen($res[$i][9]) > 8) {
                $err[$res[$i][9]][] = '籍贯过长,只需省份';
            }
            if (strlen($res[$i][10]) > 50) {
                $err[$res[$i][10]][] = '现住地址过长';
            }
            if (strtotime($res[$i][11]) == false) {
                $err['序号：' . $l][] = '必须是时间格式';
            }
            if (!is_numeric($res[$i][11]) == false) {
                $err[$res[$i][11]][] = '维护人编号必须数字';
            } else {
                try {
                    $oaData = app('api')->withRealException()->getStaff($res[$i][12]);
                    if ($oaData == false) {
                        $err[$l][] = '维护人错误';
                    }
                } catch (\Exception $e) {
                    $err[$l][] = '维护人错误';
                }
            }
            if (strlen($res[$i][13]) > 600) {
                $err[$l][] = '备注过长';
            }
            if ($err != []) {
                $errors['data'] = (object)$res[$i];
                $errors['message'] = $err;
                $error[] = $errors;
                continue;
            }
            $this->client->name = $res[$i][0];
            $this->client->source_id = $bool;
            $this->client->status = $this->strTransNum($res[$i][2]);
            $this->client->gender = $res[$i][3];
            $this->client->mobile = $res[$i][4];
            $this->client->wechat = $res[$i][5];
            $this->client->nation = $res[$i][6];
            $this->client->id_card_number = $res[$i][7];
            $this->client->native_place = $res[$i][9];//todo  验证省份
            $this->client->present_address = $res[$i][10];
            $this->client->first_cooperation_at = $res[$i][11];
            $this->client->vindicator_sn = $res[$i][12];
            $this->client->vindicator_name = $oaData['realname'];
            $this->client->remark = $res[$i][13];
            $this->client->save();
            foreach ($a as $val) {
                $this->clientHasTags->client_id = $this->client->id;
                $this->clientHasTags->tag_id = $val;
                $this->clientHasTags->save();
            }
            if ($this->client == true) {
                $success[] = $this->client;
            }
        }
        $data['data'] = isset($success) ? $success : [];
        $data['errors'] = isset($error) ? $error : [];
        return $data;
    }

    protected function strTransNum($str)
    {
        $arr = [
            '待合作' => '0',
            '已合作' => '1',
            '合作完毕' => '-1',
        ];
        if ($arr[$str] === false) {
            return false;
        }
        return $arr[$str];
    }
}