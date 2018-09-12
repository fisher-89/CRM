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
        $list = $this->clientLogs->orderBy('id', 'desc')
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
            $mobile = $this->clients->withTrashed()->where('mobile', $changes['mobile'])->whereNotIn('id',$log->client_id)->first();
            if (true === (bool)$mobile) {
                abort(400, '还原失败，电话号码冲突');
            }
        }
        if (isset($changes['id_card_number'])) {
            $idCardNumber = $this->clients->withTrashed()->where('id_card_number', $changes['id_card_number'])->whereNotIn('id',$log->client_id)->first();
            if (true === (bool)$idCardNumber) {
                abort(400, '还原失败，身份证号码冲突');
            }
        }
//        try {
//            DB::beginTransaction();
        if (isset($changes['tags'])) {
            $this->clientHasTags->where('client_id', $log->client_id)->delete();
            $tags=explode(',',$changes['tags']);
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
            $shop=explode(',',$changes['shops']);
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
            $brands=explode(',',$changes['brands']);
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
            'client_id' => $log->client_id,
            'type' => '还原到第' . $request->route('id') . '数据',
            'staff_sn' => $request->user()->staff_sn,
            'staff_name' => $request->user()->realname,
            'operation_address' =>
                [
                    '电话号码' => $this->getOperation(),
                    '设备类型' => $this->getPhoneType(),
                    'IP地址' => $request->getClientIp()
                ],
            'changes' => []
        ];
        $this->clientLogs->create($clientLog);
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