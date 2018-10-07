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
                    $tagSql[] = [
                        'client_id' => $bool->id,
                        'tag_id' => $v['tag_id'],
                    ];
                }
                $this->clientHasTags->insert($tagSql);
            }
            if (isset($request->brands) && $request->brands != []) {
                foreach ($request->brands as $item) {
                    $brandSql[] = [
                        'client_id' => $bool->id,
                        'brand_id' => $item['brand_id'],
                    ];
                }
                $this->clientHasBrands->insert($brandSql);
            }
            if (isset($request->shops) && $request->shops != []) {
                foreach ($request->shops as $items) {
                    $shopSql[] = [
                        'client_id' => $bool->id,
                        'shop_sn' => $items['shop_sn'],
                    ];
                }
                $this->clientHasShops->insert($shopSql);
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
        try {
            DB::beginTransaction();
            $clientData->update($all);
            if ((bool)$clientData === false) {
                DB::rollback();
                abort(400, '客户修改失败2');
            }
            $this->clientHasTags->where('client_id', $clientData->id)->delete();
            $this->clientHasShops->where('client_id', $clientData->id)->delete();
            $this->clientHasBrands->where('client_id', $clientData->id)->delete();
            if (isset($request->tags)) {
                if ($request->tags != []) {
                    foreach ($request->tags as $k => $v) {
                        $sql[] = [
                            'client_id' => $clientData->id,
                            'tag_id' => $v['tag_id'],
                        ];
                    }
                    $this->clientHasTags->insert($sql);
                }
            }
            if (isset($request->brands)) {
                if ($request->brands != []) {
                    foreach ($request->brands as $item) {
                        $brandSql[] = [
                            'client_id' => $clientData->id,
                            'brand_id' => $item['brand_id'],
                        ];
                    }
                    $this->clientHasBrands->insert($brandSql);
                }
            }
            if (isset($request->shops)) {
                if ($request->shops != []) {
                    foreach ($request->shops as $items) {
                        $shopSql[] = [
                            'client_id' => $clientData->id,
                            'shop_sn' => $items['shop_sn'],
                        ];
                    }
                    $this->clientHasShops->insert($shopSql);
                }
            }
            $this->saveClientLog($specialHandling, $all, $request);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            abort(400, '客户修改失败');
        }
        return response($this->client->with('tags')->with('shops')->with('brands')->where('id', $clientData->id)->first(), 201);
    }

    private function saveClientLog($model, $commit, $request)
    {
        $model = $model->toArray();
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
        if (isset($model['present_address'])) {
            $model['present_address'] = json_encode($model['present_address']);
        }
        if (isset($commit['present_address'])) {
            $commit['present_address'] = json_encode($commit['present_address']);
        }
        $model['source_id'] = (string)$model['source_id'];
        $model['status'] = (string)$model['status'];
        $array = array_diff_assoc($commit, $model);
        $changes = [];
        foreach ($array as $key => $value) {
            if ($model[$key] != $commit[$key]) {
                $changes[$key] = [$model[$key], $commit[$key]];
            }
        }
        if (isset($changes['present_address'])) {
            $changes['present_address'][0] = json_decode($changes['present_address'][0]);
            $changes['present_address'][1] = json_decode($changes['present_address'][1]);
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
            'changes' => $changes,
            'status' => $this->identifying($model['id']),
            'restore_sn' => null,
            'restore_name' => null,
            'restore_at' => null,
        ];
        if ($changes != []) {
            $this->clientLogs->create($clientLogSql);
        }
    }

    protected function identifying($id)
    {
        $log = $this->clientLogs->where('client_id', $id)->where('status', 1)->orderBy('id', 'desc')->first();
        if ($log == true) {
            $logSql = [
                'status' => 0,
                'restore_sn' => null,
                'restore_name' => null,
                'restore_at' => null
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
            $this->identifying($id);
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
                'status' => '-1',
                'restore_sn' => null,
                'restore_name' => null,
                'restore_at' => null,
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
        if ($request->user()->staff_sn == 999999) {
            $arr = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15];
        }
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
    public function exportClient($request, $brand)
    {
        $all = $request->all();
        if (array_key_exists('page', $all) || array_key_exists('pagesize', $all)) {
            abort(400, '传递无效参数');
        }
        foreach ($brand as $key => $value) {
            foreach ($value['visibles'] as $k => $v) {
                $arrData[] = $v['brand_id'];
            }
        }
        $arr = isset($arrData) ? $arrData : [];
        $client = $this->client->with('source')->with('tags')->with('brands')->with('shops')
            ->whereHas('brands', function ($query) use ($arr) {
                $query->whereIn('brand_id', $arr);
            })->filterByQueryString()->withPagination();
        if (false == (bool)$client) {
            return response()->json(['message' => '没有找到符号条件的数据'], 404);
        }
        $brand = app('api')->getBrands($arr);
        $eventTop[] = ['姓名', '客户来源', '客户状态', '客户品牌', '性别', '电话', '微信', '民族', '身份证号码', '标签',
            '籍贯', '首次合作时间', '维护人编号', '备注'];
        foreach ($client as $k => $v) {
            $eventTop[] = [$v['name'], $v['source']['name'], $this->transform($v['status']), $this->transBrand($v['brands'], $brand),
                $v['gender'], $v['mobile'], $v['wechat'], $v['nation'], $v['id_card_number'],
                $v['tags'] ? $this->transTags($v['tags']) : '', $v['native_place'], $v['first_cooperation_at'],
                $v['vindicator_sn'] . ',' . $v['vindicator_name'], $v['remark']
            ];
        }
        Excel::create('客户信息资料', function ($excel) use ($eventTop) {
            $excel->sheet('score', function ($query) use ($eventTop) {
                $query->rows($eventTop);
            });
        })->export('xlsx');
    }

    protected function transBrand($obj, $brand)
    {
        $data = [];
        $brands = [];
        foreach ($obj as $items) {
            $data[] = $items['brand_id'];
        }
        foreach ($brand as $key => $value) {
            if (in_array($value['id'], $data)) {
                $brands[] = $value['name'];
            }
        }
        return implode(',', $brands);
    }

    public function excelSaveClient($array)
    {
        return $this->client->create($array);
    }

    public function excelSaveBrand($array)
    {
        return $this->clientHasBrands->insert($array);
    }

    public function excelSaveTags($array)
    {
        return $this->clientHasTags->insert($array);
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
            return false;
        }
        return $arr[$str];
    }
}