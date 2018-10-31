<?php

namespace App\Services\Admin;

use App\Http\Resources\ClientLogCollection;
use App\Models\ClientHasBrands;
use App\Models\ClientHasLevel;
use App\Models\ClientHasLinkage;
use App\Models\ClientHasShops;
use App\Models\ClientHasTags;
use DB;
use App\Models\Clients;
use App\Models\ClientLogs;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ClientLogsService
{
    use Traits\GetInfo;
    protected $clientHasLinkage;
    protected $clientHasBrands;
    protected $clientHasShops;
    protected $clientHasLevel;
    protected $clientHasTags;
    protected $clientLogs;
    protected $clients;

    public function __construct(ClientLogs $clientLogs, Clients $clients, ClientHasTags $clientHasTags, ClientHasShops $clientHasShops,
                                ClientHasBrands $clientHasBrands, ClientHasLevel $clientHasLevel, ClientHasLinkage $clientHasLinkage)
    {
        $this->clients = $clients;
        $this->clientLogs = $clientLogs;
        $this->clientHasTags = $clientHasTags;
        $this->clientHasShops = $clientHasShops;
        $this->clientHasLevel = $clientHasLevel;
        $this->clientHasBrands = $clientHasBrands;
        $this->clientHasLinkage = $clientHasLinkage;
    }

    public function getClientLogsList($request, $obj)
    {
        foreach ($obj as $item) {
            foreach ($item['visibles'] as $items) {
                $data[] = $items['brand_id'];
            };
        }
        $array = isset($data) ? $data : [];
        $arr = array_filter($array);
        $list = $this->clientLogs->with('clients.brands')
            ->whereHas('clients.brands', function ($query) use ($arr) {
                $query->whereIn('brand_id', $arr);
            })->SortByQueryString()->filterByQueryString()->withPagination($request->get('pagesize', 10));
        if (isset($list['data'])) {
            $list['data'] = new ClientLogCollection(collect($list['data']));
            return $list;
        } else {
            return new ClientLogCollection($list);
        }
    }

    public function restoreClient($request)
    {
        $id = $request->route('id');
        $log = $this->clientLogs->where('id', $id)->first();
        if (false === (bool)$log->changes) {
            abort(404, '未找到还原数据');
        }
        $changes = $this->dataDispose($log->changes);
        if (isset($changes['mobile'])) {
            $mobile = $this->clients->withTrashed()->where('mobile', $changes['mobile'])->whereNotIn('id', explode(',', $log->client_id))->first();
            if (true === (bool)$mobile) {
                if ($mobile->deleted_at == true) {
                    abort(400, '还原失败，电话号码与姓名为:' . $mobile->name . '的电话号码冲突了,经检测该数据已被删除，请联系管理员');
                } else {
                    abort(400, '还原失败，电话号码与姓名为:' . $mobile->name . '的电话号码冲突');
                }
            }
        }
        if (isset($changes['id_card_number'])) {
            $idCardNumber = $this->clients->withTrashed()->where('id_card_number', $changes['id_card_number'])->whereNotIn('id', explode(',', $log->client_id))->first();
            if (true === (bool)$idCardNumber) {
                if ($idCardNumber->deleted_at == true) {
                    abort(400, '还原失败，身份证号码与姓名为:' . $idCardNumber->name . '的身份证号码冲突了,经检测该数据已被删除，请联系管理员');
                } else {
                    abort(400, '还原失败，身份证号码与姓名为:' . $idCardNumber->name);
                }
            }
        }
        if (isset($changes['icon'])) {
            $this->ImageRestore($changes['icon'], 'icon');
        }
        if (isset($changes['id_card_image_f'])) {
            $this->ImageRestore($changes['id_card_image_f'], 'card');
        }
        if (isset($changes['id_card_image_b'])) {
            $this->ImageRestore($changes['id_card_image_b'], 'card');
        }
        try {
            DB::beginTransaction();
        if (isset($changes['tags'])) {
            $this->actionLists($this->clientHasTags, $log->client_id, $changes, 'tags', 'tag_id');
        }
        if (isset($changes['shops'])) {
            $this->actionLists($this->clientHasShops, $log->client_id, $changes, 'shops', 'shop_sn');
        }
        if (isset($changes['brands'])) {
            $this->actionLists($this->clientHasBrands, $log->client_id, $changes, 'brands', 'brand_id');
        }
        if (isset($changes['levels'])) {
            $this->actionLists($this->clientHasLevel, $log->client_id, $changes, 'levels', 'level_id');
        }
        if (isset($changes['linkages'])) {
            $this->actionLists($this->clientHasLinkage, $log->client_id, $changes, 'linkages', 'linkage_id');
        }
        $client = $this->clients->find($log->client_id);
        if (false === (bool)$client) {
            $OA = $request->user()->authorities['oa'];
            if (!in_array('191', $OA)) {
                abort(401, '抱歉，该数据已被删除，且你没有已删除数据还原权限');
            }
            $this->clients->where('id', $log->client_id)->restore();
            $client = $this->clients->find($log->client_id);
            if (false === (bool)$client) {
                abort(404, '还原失败，未找到数据');
            }
        }
        $bool = $client->update($changes);//执行
        $clientLog = [
            'status' => 2,
            'restore_sn' => $request->user()->staff_sn,
            'restore_name' => $request->user()->realname,
            'restore_at' => date('Y-m-d H:i:s'),
        ];
        $log->update($clientLog);
        $logs = $this->clientLogs->orderBy('id', 'desc')->where('client_id', $log->client_id)->where('status', 0)->whereNotIn('changes', ['[]'])->first();
        if ($logs == true) {
            $logSql = [
                'status' => 1,
                'restore_sn' => null,
                'restore_name' => null,
                'restore_at' => null
            ];
            $logs->update($logSql);
        }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            abort(400, '还原失败');
        }
        $data = $this->clients->where('id', $log->client_id)->with(['tags', 'shops', 'brands', 'levels', 'linkages'])->first();
        return (bool)$bool === true ? response($data, 201) : abort(400, '还原失败');
    }

    protected function actionLists($model, $id, $changes, $arr, $key)
    {
        $model->where('client_id', $id)->delete();
        $sql = [];
        if ((bool)$changes[$arr] === true) {
            $brands = explode(',', $changes[$arr]);
            foreach ($brands as $item) {
                $sql[] = [
                    'client_id' => $id,
                    $key => $item
                ];
            }
            $model->insert($sql);
        }
    }

    protected function ImageRestore($icon, $type)
    {
        $fileName = basename(is_array($icon) ? $icon[0] : $icon);
        $src = '/abandon/' . $fileName;
        $dst = '/' . $type . '/' . $fileName;
        if (Storage::disk('public')->exists($src)) {
            Storage::disk('public')->move($src, $dst);
        }
        if (is_array($icon) && count($icon) === 2) {
            $acr = '/abandon/' . basename($icon[1]);
            $std = '/' . $type . '/' . basename($icon[1]);
            if (Storage::disk('public')->exists($acr)) {
                Storage::disk('public')->move($acr, $std);
//                $url[] = config('app.url') . '/storage' . $dst;
//                $url[] = config('app.url') . '/storage' . $std;
//                return $url;
            }
        }
//        return config('app.url') . '/storage' . $dst;
    }

    /**
     * 提取还原值
     *
     * @param $changes
     * @return array
     */
    protected function dataDispose($changes)
    {
        $k = [];
        foreach ($changes as $key => $value) {
            $k[$key] = $value[0];
        }
        return $k;
    }

    /**
     * 删除还原处理
     *
     * @param $request
     * @param $client_id
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response|void
     */
    public function restoreClientDelete($request, $client_id)
    {
        $id = $request->route('id');
        $client = $this->clients->find($client_id);
        $log = $this->clientLogs->where('id', $id)->first();
        if (false === (bool)$client) {
            $OA = $request->user()->authorities['oa'];
            if (!in_array('191', $OA)) {
                abort(401, '抱歉，该数据已被删除，且你没有已删除数据还原权限');
            }
            $this->clients->where('id', $client_id)->restore();
            $client = $this->clients->find($client_id);
            if (false === (bool)$client) {
                abort(404, '还原失败，未找到数据');
            }
        }
        $clientLog = [
            'status' => 2,
            'restore_sn' => $request->user()->staff_sn,
            'restore_name' => $request->user()->realname,
            'restore_at' => date('Y-m-d H:i:s'),
        ];
        $log->update($clientLog);
        $logs = $this->clientLogs->orderBy('id', 'desc')->where('client_id', $log->client_id)->where('status', 0)->whereNotIn('changes', ['[]'])->first();
        if ($logs == true) {
            $logSql = [
                'status' => 1,
                'restore_sn' => null,
                'restore_name' => null,
                'restore_at' => null
            ];
            $logs->update($logSql);
        }
        $data = $this->clients->where('id', $log->client_id)->with(['tags', 'shops', 'brands', 'levels', 'linkages'])->first();
        return (bool)$log === true ? response($data, 201) : abort(400, '还原失败');
    }
}