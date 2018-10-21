<?php

namespace App\Services\Admin;

use App\Models\AuthorityGroups;
use App\Http\Resources\ClientsCollection;
use App\Models\AuthGroupHasEditableBrands;
use App\Models\AuthGroupHasVisibleBrands;
use App\Models\ClientHasBrands;
use App\Models\ClientHasLevel;
use App\Models\ClientHasLinkage;
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
use Illuminate\Support\Facades\Storage;

class ClientsService
{
    use Traits\GetInfo;
    protected $tags;
    protected $client;
    protected $source;
    protected $nations;
    protected $clientLogs;
    protected $clientHasTags;
    protected $clientHasLevel;
    protected $clientHasShops;
    protected $clientHasBrands;
    protected $clientHasLinkages;

    public function __construct(Clients $clients, ClientHasTags $clientHasTags, Source $source, Nations $nations, Tags $tags,
                                ClientHasShops $clientHasShops, ClientHasBrands $clientHasBrands, ClientLogs $clientLogs,
                                ClientHasLevel $clientHasLevel, ClientHasLinkage $clientHasLinkages)
    {
        $this->tags = $tags;
        $this->source = $source;
        $this->client = $clients;
        $this->nations = $nations;
        $this->clientLogs = $clientLogs;
        $this->clientHasTags = $clientHasTags;
        $this->clientHasLevel = $clientHasLevel;
        $this->clientHasShops = $clientHasShops;
        $this->clientHasBrands = $clientHasBrands;
        $this->clientHasLinkages = $clientHasLinkages;
    }

    /**
     * 获取列表
     *
     * @param $request
     * @param $brand
     * @return ClientsCollection
     */
    public function listClient($request, $brand)
    {
        $list = $this->client->with(['tags', 'shops', 'brands', 'levels', 'linkages'])
            ->filterByQueryString()->SortByQueryString()->withPagination($request->get('pagesize', 10));
        if (isset($list['data'])) {
            $list['data'] = new ClientsCollection(collect($list['data']));
            return $list;
        } else {
            return new ClientsCollection($list);
        }
    }

    /**
     * 添加执行
     *
     * @param $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function addClient($request)
    {
        $all = $request->all();
//        try {
//            DB::beginTransaction();
        if ((bool)$request->icon === true) {
            $icon = $this->imageDispose($request->icon, 'icon');
            $all['icon'] = $icon;
        }
        if ((bool)$request->id_card_image_f === true) {
            $card = $this->imageDispose($request->id_card_image_f, 'card');
            $all['id_card_image_f'] = $card;
        }
        if ((bool)$request->id_card_image_b === true) {
            $card = $this->imageDispose($request->id_card_image_b, 'card');
            $all['id_card_image_b'] = $card;
        }
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
        }// 合作省份
        if (isset($request->linkages) && $request->linkages != []) {
            foreach ($request->linkages as $val) {
                $linkageSql[] = [
                    'client_id' => $bool->id,
                    'linkage_id' => $val['linkage_id'],
                ];
            }
            $this->clientHasLinkages->insert($linkageSql);
        }// 客户等级
        if (isset($request->levels) && $request->levels != []) {
            foreach ($request->levels as $value) {
                $levelSql[] = [
                    'client_id' => $bool->id,
                    'level_id' => $value['level_id'],
                ];
            }
            $this->clientHasLevel->insert($levelSql);
        }
//            DB::commit();
//        } catch (\Exception $e) {
//            DB::rollback();
//            abort(400, '客户添加失败');
//        }
        return response()->json($this->client->where('id', $bool->id)->with(['tags', 'shops', 'brands', 'levels', 'linkages'])->first(), 201);
    }

    /**
     * 修改执行
     *
     * @param $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function editClient($request)
    {
        $all = $request->all();
        $clientData = $this->client->with(['tags', 'shops', 'brands', 'levels', 'linkages'])->find($request->route('id'));
        if ((bool)$clientData === false) {
            abort(404, '未找到数据');
        }
        $specialHandling = clone $clientData;
//        try {
//            DB::beginTransaction();
        if ((bool)$request->icon === true && $request->icon != isset($clientData['icon'][0]) ? $clientData['icon'][0] : $clientData['icon']) {
            $icon = $this->imageDispose($request->icon, 'icon', $clientData['icon']);
            $all['icon'] = $icon;
        }
        if ((bool)$request->id_card_image_f === true && $request->id_card_image_f != $clientData['id_card_image_f']) {
            $card = $this->imageDispose($request->id_card_image_f, 'card', $clientData['id_card_image_f']);
            $all['id_card_image_f'] = $card;
        }
        if ((bool)$request->id_card_image_b === true && $request->id_card_image_b != $clientData['id_card_image_b']) {
            $card = $this->imageDispose($request->id_card_image_b, 'card', $clientData['id_card_image_b']);
            $all['id_card_image_b'] = $card;
        }
        $clientData->update($all);
        if ((bool)$clientData === false) {
            DB::rollback();
            abort(400, '客户修改失败');
        }
        $this->clientHasTags->where('client_id', $clientData->id)->delete();
        $this->clientHasShops->where('client_id', $clientData->id)->delete();
        $this->clientHasBrands->where('client_id', $clientData->id)->delete();
        $this->clientHasLevel->where('client_id', $clientData->id)->delete();
        $this->clientHasLinkages->where('client_id', $clientData->id)->delete();
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
        if (isset($request->linkages) && $request->linkages != []) {
            foreach ($request->linkages as $val) {
                $linkageSql[] = [
                    'client_id' => $clientData->id,
                    'linkage_id' => $val['linkage_id'],
                ];
            }
            $this->clientHasLinkages->insert($linkageSql);
        }// 客户等级
        if (isset($request->levels) && $request->levels != []) {
            foreach ($request->levels as $value) {
                $levelSql[] = [
                    'client_id' => $clientData->id,
                    'level_id' => $value['level_id'],
                ];
            }
            $this->clientHasLevel->insert($levelSql);
        }
        $this->saveClientLog($specialHandling, $all, $request);
//            DB::commit();
//        } catch (\Exception $e) {
//            DB::rollback();
//            abort(400, '客户修改失败');
//        }
        return response($this->client->where('id', $clientData->id)->with(['tags', 'shops', 'brands', 'levels', 'linkages'])->first(), 201);
    }

    /**
     * 写入记录表
     *
     * @param $model
     * @param $commit
     * @param $request
     */
    private function saveClientLog($model, $commit, $request)
    {
        $model = $model->toArray();
        if (isset($model['tags']) && (bool)$model['tags'] === true) {//模型标签
            foreach ($model['tags'] as $i) {
                $tags[] = $i['tag_id'];
            }
            $model['tags'] = implode(',', $this->sort(isset($tags) ? $tags : []));
        } else {
            $model['tags'] = '';
        }
        if (isset($model['brands']) && (bool)$model['brands'] === true) {//模型品牌
            foreach ($model['brands'] as $item) {
                $brands[] = $item['brand_id'];
            }
            $model['brands'] = implode(',', $this->sort(isset($brands) ? $brands : []));
        } else {
            $model['brands'] = '';
        }

        if (isset($model['shops']) && (bool)$model['shops'] === true) {//模型店铺
            foreach ($model['shops'] as $items) {
                $shops[] = $items['shop_sn'];
            }
            $model['shops'] = implode(',', $this->sort(isset($shops) ? $shops : []));
        } else {
            $model['shops'] = '';
        }

        if (isset($model['linkages']) && (bool)$model['linkages'] === true) {//模型合作省份
            foreach ($model['linkages'] as $linkage) {
                $linkageArr[] = $linkage['linkage_id'];
            }
            $model['linkages'] = implode(',', $this->sort(isset($linkageArr) ? $linkageArr : []));
        } else {
            $model['linkages'] = '';
        }

        if (isset($model['levels']) && (bool)$model['levels'] === true) {//模型等级
            foreach ($model['levels'] as $level) {
                $levelArr[] = $level['level_id'];
            }
            $model['levels'] = implode(',', $this->sort(isset($levelArr) ? $levelArr : []));
        } else {
            $model['levels'] = '';
        }

        if (isset($commit['tags']) && (bool)$commit['tags'] === true) {
            foreach ($commit['tags'] as $v) {
                $commitTag[] = $v['tag_id'];
            }
            $commit['tags'] = implode(',', $this->sort(isset($commitTag) ? $commitTag : []));
        } else {
            $commit['tags'] = '';
        }

        if (isset($commit['brands']) && (bool)$commit['brands'] === true) {
            foreach ($commit['brands'] as $val) {
                $commitBrand[] = $val['brand_id'];
            }
            $commit['brands'] = implode(',', $this->sort(isset($commitBrand) ? $commitBrand : []));
        } else {
            $commit['brands'] = '';
        }

        if (isset($commit['shops']) && (bool)$commit['shops'] === true) {
            foreach ($commit['shops'] as $value) {
                $commitShop[] = $value['shop_sn'];
            }
            $commit['shops'] = implode(',', $this->sort(isset($commitShop) ? $commitShop : []));
        } else {
            $commit['shops'] = '';
        }

        if (isset($commit['linkages']) && (bool)$commit['linkages'] === true) {
            foreach ($commit['linkages'] as $linkages) {
                $linkagesArr[] = $linkages['linkage_id'];
            }
            $commit['linkages'] = implode(',', $this->sort(isset($linkagesArr) ? $linkagesArr : []));
        } else {
            $commit['linkages'] = '';
        }

        if (isset($commit['levels']) && (bool)$commit['levels'] === true) {
            foreach ($commit['levels'] as $levels) {
                $levelsArr[] = $levels['level_id'];
            }
            $commit['levels'] = implode(',', $this->sort(isset($levelsArr) ? $levelsArr : []));
        } else {
            $commit['levels'] = '';
        }

        if (isset($model['icon']) && (bool)$model['icon'] === true) {
            $model['icon'] = json_encode($model['icon']);
        }
        if (isset($commit['icon']) && (bool)$model['icon'] === true) {
            $commit['icon'] = json_encode($commit['icon']);
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
        if (isset($changes['icon'])) {
            $changes['icon'][0] = json_decode($changes['icon'][0]);
            $changes['icon'][1] = json_decode($changes['icon'][1]);
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

    /**
     * 冒泡排序
     *
     * @param $arr
     * @return mixed
     */
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

    /**
     * 删除执行
     *
     * @param $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function delClient($request)
    {
        $id = $request->route('id');
        $client = $this->client->find($id);
        if ((bool)$client === false) {
            abort(404, '未找到数据');
        }
        try {
            DB::beginTransaction();
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
        return $this->client->with(['tags', 'shops', 'brands', 'levels', 'linkages'])->where('id', $request->route('id'))
            ->whereHas('brands', function ($query) use ($arr) {
                $query->whereIn('brand_id', $arr);
            })->first();
    }

    /**
     * 状态转换
     * @param $data
     * @return mixed
     */
    protected function transform($data)
    {
        $arr = [
            '-1' => '合作完毕',
            '0' => '待合作',
            '1' => '合作中',
            '2' => '合作完成',
        ];
        return $arr[$data];
    }

    protected function transTags($arr)
    {
        $data = [];
        foreach ($arr as $key => $val) {
            if (isset($val['tag']['name'])) {
                $data[] = $val['tag']['name'];
            }
        }
        return implode(',', $data);
    }

    protected function imageDispose($path, $type, $action = '')
    {
        if (is_array($path)) {
            return $this->muchImage($path, $type, $action);
        }
        return $this->singleImage($path, $type, $action);
    }

    protected function muchImage($path, $type, $action)
    {
        if ($action != '') {
            if (is_array($action)) {
                $this->more($action, $type);
            } else {
                $this->single($action, $type);
            }
        }
        $url = [];
        foreach ($path as $k => $v) {
            $fileName = basename($v);
            $src = '/temporary/' . $fileName;
            $dst = '/' . $type . '/' . $fileName;
            if (Storage::disk('public')->exists($src)) {
                Storage::disk('public')->move($src, $dst);
            }
            if ($type == 'icon') {
                $fileNameArr = explode('.', $fileName);
                $fileNameArr[0] = $fileNameArr[0] . '_thumb';
                $acr = '/temporary/' . implode('.', $fileNameArr);
                $std = '/' . $type . '/' . implode('.', $fileNameArr);
                if (Storage::disk('public')->exists($acr)) {
                    Storage::disk('public')->move($acr, $std);
                    $url[] = config('app.url') . '/storage' . $std;
                }
            }
            $url[] = config('app.url') . '/storage' . $dst;
        }
        return $url;
    }

    protected function singleImage($path, $type, $action)
    {
        if ($action != '') {
            if (is_array($action)) {
                $this->more($action, $type);
            } else {
                $this->single($action, $type);
            }
        }
        $fileName = basename($path);
        $src = '/temporary/' . $fileName;
        $dst = '/' . $type . '/' . $fileName;
        if (Storage::disk('public')->exists($src)) {
            Storage::disk('public')->move($src, $dst);
        }
        if ($type == 'icon') {
            $fileNameArr = explode('.', $fileName);
            $fileNameArr[0] = $fileNameArr[0] . '_thumb';
            $acr = '/temporary/' . implode('.', $fileNameArr);
            $std = '/' . $type . '/' . implode('.', $fileNameArr);
            if (Storage::disk('public')->exists($acr)) {
                Storage::disk('public')->move($acr, $std);
                $icon[] = config('app.url') . '/storage' . $dst;
                $icon[] = config('app.url') . '/storage' . $std;
                return $icon;
            }
        }
        return config('app.url') . '/storage' . $dst;
    }

    protected function single($action, $type)
    {
        $getFileName = basename($action);
        $rawSrc = '/' . $type . '/' . $getFileName;
        $abandonDst = '/abandon/' . $getFileName;
        if (Storage::disk('public')->exists($rawSrc)) {
            Storage::disk('public')->move($rawSrc, $abandonDst);
        }
        if ($type == 'icon') {
            $fileArr = explode('.', $getFileName);
            $fileArr[0] = $fileArr[0] . '_thumb';
            $acronym = '/' . $type . '/' . implode('.', $fileArr);
            $stu = '/abandon/' . implode('.', $fileArr);
            if (Storage::disk('public')->exists($acronym)) {
                Storage::disk('public')->move($acronym, $stu);
            }
        }
    }

    protected function more($action, $type)
    {
        foreach ($action as $item) {
            $getFileName = basename($item);
            $typeSrc = '/' . $type . '/' . $getFileName;
            $abandon = '/abandon/' . $getFileName;
            if (Storage::disk('public')->exists($typeSrc)) {
                Storage::disk('public')->move($typeSrc, $abandon);
            }
            if ($type == 'icon') {
                $fileArr = explode('.', $getFileName);
                $fileArr[0] = $fileArr[0] . '_thumb';
                $acronym = '/' . $type . '/' . implode('.', $fileArr);
                $stu = '/abandon/' . implode('.', $fileArr);
                if (Storage::disk('public')->exists($acronym)) {
                    Storage::disk('public')->move($acronym, $stu);
                }
            }
        }
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