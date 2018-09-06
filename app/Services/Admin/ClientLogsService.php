<?php

namespace App\Services\Admin;

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

    public function getClientLogsList($request,$obj)
    {
        foreach ($obj as $item){
            $data[]=$item->auth_brand;
        }
        $array = isset($data) ? $data : [];
        $arr = array_filter($array);
        return $this->clientLogs->orderBy('id','desc')
            ->whereHas('clients.Brands',function($query)use($arr){
                $query->whereIn('brand_id',$arr);
            })->filterByQueryString()->withPagination($request->get('pagesize', 10));
    }

    public function restoreClient($request)
    {
        $log = $this->clientLogs->where('id', $request->route('id'))->first();
        if (false === (bool)$log->changes) {
            abort(404, '未找到还原数据');
        }
        if (true === (bool)$log->changes['mobile']) {
            $mobile = $this->clients->withTrashed()->where('mobile', $log->changes['mobile']['original'])->first();
            if (true === (bool)$mobile) {
                abort(400, '还原失败，电话号码冲突');
            }
        }
        if (true === (bool)$log->changes['id_card_number']) {
            $idCardNumber = $this->clients->withTrashed()->where('id_card_number', $log->changes['id_card_number']['original'])->first();
            if (true === (bool)$idCardNumber) {
                abort(400, '还原失败，身份证号码冲突');
            }
        }
//        try {
//            DB::beginTransaction();
            if (isset($log->changes['Tags'])) {
                $this->clientHasTags->whereIn('client_id', $log->changes['Tags'])->delete();
                foreach ($log->changes['tag_id'] as $value) {
                    $tagSql = [
                        'client_id' => $log->client_id,
                        'tag_id' => $value['id']
                    ];
                    $this->clientHasTags->create($tagSql);
                }
            }
            if (true === (bool)$log->changes['Shops']) {
                $this->clientHasShops->whereIn('client_id', $log->changes['Shops'])->delete();
                foreach ($log->changes['shop_id'] as $items) {
                    $shopSql = [
                        'client_id' => $log->client_id,
                        'shop_id' => $items['id']
                    ];
                    $this->clientHasShops->create($shopSql);
                }
            }
            if (true === (bool)$log->changes['Brands']) {
                $this->clientHasBrands->whereIn('client_id', $log->changes['Brands'])->delete();
                foreach ($log->changes['brand_id'] as $item) {
                    $brandSql = [
                        'client_id' => $log->client_id,
                        'brand_id' => $item['id']
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
            $bool = $client->update($log->changes);
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
                'changes' => $log->changes
            ];
            $this->clientLogs->create($clientLog);
//            DB::commit();
//        } catch (\Exception $e) {
//            DB::rollback();
//            abort(400, '还原失败');
//        }
        return (bool)$bool === true ? response($this->clients->find($log->client_id), 201) : abort(400, '还原失败');
    }
}