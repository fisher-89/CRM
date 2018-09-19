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
        return $this->collection->map(function ($data) {
            return [
                "id"=> $data->id,
                "name"=> $data->name,
                "source_id"=> $data->source_id,
                "status"=> $data->status,
                "gender"=> $data->gender,
                "mobile"=> $this->mobile($data->mobile),
                "wechat"=> $data->wechat,
                "nation"=> $data->nation,
                "native_place"=> $data->native_place,
                "present_address"=> $data->present_address,
                "first_cooperation_at"=> $data->first_cooperation_at,
                "vindicator_sn"=> $data->vindicator_sn,
                "vindicator_name"=> $data->vindicator_name,
                "remark"=> $data->remark,
                "created_at"=> $data->created_at == null ? '' : $data->created_at->format('Y-m-d H:i:s'),
                "updated_at"=> $data->updated_at == null ? '' : $data->updated_at->format('Y-m-d H:i:s'),
                "deleted_at"=> $data->deleted_at,
                "tags"=> $data->tags,
                "shops"=> $data->shops,
                "brands"=> $data->brands
            ];
        })->toArray();
    }

    public function mobile($mobile)
    {
        return preg_replace("/(\d{3})\d\d(\d{2})/","\$1****\$3",$mobile);
    }

}
