<?php

namespace App\Services\Admin;

use App\Http\Resources\ClientLogCollection;
use App\Models\ClientHasBrands;
use App\Models\ClientHasShops;
use App\Models\ClientHasTags;
use DB;
use App\Models\Clients;
use App\Models\ClientLogs;
use Illuminate\Http\Request;

class ClientLogsService
{
    use Traits\GetInfo;
    protected $clientHasBrands;
    protected $clientHasShops;
    protected $clientHasTags;
    protected $clientLogs;
    protected $clients;

    public function __construct(ClientLogs $clientLogs, Clients $clients, ClientHasTags $clientHasTags,
                                ClientHasShops $clientHasShops, ClientHasBrands $clientHasBrands)
    {
        $this->clients = $clients;
        $this->clientLogs = $clientLogs;
        $this->clientHasTags = $clientHasTags;
        $this->clientHasShops = $clientHasShops;
        $this->clientHasBrands = $clientHasBrands;
    }

    public function getClientLogsList($request, $obj)
    {
        foreach ($obj as $item) {
            foreach ($item->visibles as $items) {
                $data[] = $items->brand_id;
            };
        }
        $array = isset($data) ? $data : [];
        $arr = array_filter($array);
        $list = $this->clientLogs->orderBy('id', 'desc')->with('clients.brands')
            ->whereHas('clients.brands', function ($query) use ($arr) {
                $query->whereIn('brand_id', $arr);
            })->filterByQueryString()->withPagination($request->get('pagesize', 10));
        if (isset($list['data'])) {
            $list['data'] = new ClientLogCollection(collect($list['data']));
            return $list;
        } else {
            return new ClientLogCollection($list);
        }
    }

    public function restoreClient($request)
    {
        $log = $this->clientLogs->where('id', $request->route('id'))->first();
        if (false === (bool)$log->changes) {
            abort(404, '未找到还原数据');
        }
        $changes = $this->dataDispose($log->changes);
        if (isset($changes['mobile'])) {
            $mobile = $this->clients->withTrashed()->where('mobile', $changes['mobile'])->whereNotIn('id', explode(',', $log->client_id))->first();
            if (true === (bool)$mobile) {
                if ($mobile->deleted_at == true) {
                    abort(400, '还原失败，电话号码冲突,冲突id为:' . $mobile->id . ',经检测该数据已被删除，请联系管理员');
                } else {
                    abort(400, '还原失败，电话号码冲突,冲突id为:' . $mobile->id);
                }
            }
        }
        if (isset($changes['id_card_number'])) {
            $idCardNumber = $this->clients->withTrashed()->where('id_card_number', $changes['id_card_number'])->whereNotIn('id', explode(',', $log->client_id))->first();
            if (true === (bool)$idCardNumber) {
                if ($idCardNumber->deleted_at == true) {
                    abort(400, '还原失败，身份证号码冲突,冲突id为:' . $idCardNumber->id . ',经检测该数据已被删除，请联系管理员');
                } else {
                    abort(400, '还原失败，身份证号码冲突,冲突id为:' . $idCardNumber->id);
                }
            }
        }
//        try {
//            DB::beginTransaction();
        if (isset($changes['tags'])) {
            $this->clientHasTags->where('client_id', $log->client_id)->delete();
            $tags = explode(',', $changes['tags']);
            foreach ($tags as $value) {
                $tagSql = [
                    'client_id' => $log->client_id,
                    'tag_id' => $value
                ];
                $this->clientHasTags->create($tagSql);
            }
        }
        if (isset($changes['shops'])) {
            $this->clientHasShops->where('client_id', $log->client_id)->delete();
            $shop = explode(',', $changes['shops']);
            foreach ($shop as $items) {
                $shopSql = [
                    'client_id' => $log->client_id,
                    'shop_sn' => $items
                ];
                $this->clientHasShops->create($shopSql);
            }
        }
        if (isset($changes['brands'])) {
            $this->clientHasBrands->where('client_id', $log->client_id)->delete();
            $brands = explode(',', $changes['brands']);
            foreach ($brands as $item) {
                $brandSql = [
                    'client_id' => $log->client_id,
                    'brand_id' => $item
                ];
                $this->clientHasBrands->create($brandSql);
            }
        }
        $client = $this->clients->find($log->client_id);
        if (false === (bool)$client) {
            $this->clients->where('id', $log->client_id)->restore();
            $client = $this->clients->find($log->client_id);
            if (false === (bool)$client) {
                abort(404, '还原失败，未找到数据');
            }
        }
        $bool = $client->update($changes);//执行
        $clientLog = [
            'restore_sn' => $request->user()->staff_sn,
            'restore_time' => date('Y-m-d H:i:s'),
        ];
        $log->update($clientLog);
        $logs = $this->clientLogs->orderBy('id', 'desc')->where('restore_sn', 0)->whereNotIn('changes', ['[]'])->first();
        if ($logs == true) {
            $logSql = [
                'restore_sn' => 1,
                'restore_time' => null
            ];
            $logs->update($logSql);
        }
//            DB::commit();
//        } catch (\Exception $e) {
//            DB::rollback();
//            abort(400, '还原失败');
//        }
        return (bool)$bool === true ? response($this->clients->find($log->client_id), 201) : abort(400, '还原失败');
    }

    protected function dataDispose($changes)
    {
        $k = [];
        foreach ($changes as $key => $value) {
            $k[$key] = $value[0];
        }
        return $k;
    }
}