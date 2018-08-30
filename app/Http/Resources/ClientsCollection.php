<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class ClientsCollection extends ResourceCollection
{
    public static $wrap = null;
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
//        return parent::toArray($request);
        return $this->collection->map(function ($data) {
            foreach ($data->tags as $item){
                $tags[]=$item['tag_id'];
            }
            foreach ($data->shops as $items){
                $shop[]=$items['shop_id'];
            }
            foreach ($data->brands as $i){
                $brand[]=$i['brand_id'];
            }
            return [
                "id"=> $data->id,
                "name"=> $data->name,
                "source_id"=> $data->source_id,
                "status"=> $data->status,
                "gender"=> $data->gender,
                "mobile"=> $data->mobile,
                "wechat"=> $data->wechat,
                "nation"=> $data->nation,
                "id_card_number"=> $data->id_card_number,
                "native_place"=> $data->native_place,
                "present_address"=> $data->present_address,
                "first_cooperation_at"=> $data->first_cooperation_at,
                "vindicator_sn"=> $data->vindicator_sn,
                "vindicator_name"=> $data->vindicator_name,
                "remark"=> $data->remark,
                "created_at"=> $data->created_at,
                "updated_at"=> $data->updated_at,
                "deleted_at"=> $data->deleted_at,
                "tags"=> $tags,
                "shops"=> $shop,
                "brands"=> $brand
            ];
        })->toArray();
    }
}
