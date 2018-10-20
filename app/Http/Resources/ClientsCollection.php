<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class ClientsCollection extends ResourceCollection
{
    public static $wrap = null;

    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        return $this->collection->map(function ($data) {
            return [
                "id" => $data->id,
                "name" => $data->name,
                "source_id" => $data->source_id,
                "status" => $data->status,
                "gender" => $data->gender,
                "mobile" => $this->mobile($data->mobile),
                "wechat" => $data->wechat,
                "nation" => $data->nation,
                "native_place" => $data->native_place,
                'id_card_number' => $data->id_card_number,//'身份证号码',
                'province_id' => $data->province_id,//'省级',
                'city_id' => $data->city_id,//'市级',
                'county_id' => $data->county_id,//'县级',
                'address' => $data->address,//'详细地址',
                'icon' => $data->icon,//'头像照片',
                'id_card_image_f' => $data->id_card_image_f,//'身份证照片正面',
                'id_card_image_b' => $data->id_card_image_b,//'身份证照片反面',
//                'provinces' => $this->province($data->provinces),//'合作省份',todo
                'provinces' => $data->provinces,//'合作省份',todo
//                'levels' => $this->level($data->levels),//'客户等级',todo
                'levels' => $data->levels,//'客户等级',todo
                'develop_sn' => $data->develop_sn,//'开发人编号',
                'develop_name' => $data->develop_name,//'开发人姓名',
                'recommend_id' => $data->recommend_id,//'介绍人id',
                'recommend_name' => $data->recommend_name,//'介绍人姓名',
                "first_cooperation_at" => $data->first_cooperation_at,
                "vindicator_sn" => $data->vindicator_sn,
                "vindicator_name" => $data->vindicator_name,
                "remark" => $data->remark,
                "created_at" => $data->created_at == null ? '' : $data->created_at->format('Y-m-d H:i:s'),
                "updated_at" => $data->updated_at == null ? '' : $data->updated_at->format('Y-m-d H:i:s'),
                "deleted_at" => $data->deleted_at,
                "tags" => $data->tags,
                "shops" => $data->shops,
                "brands" => $data->brands,
            ];
        })->toArray();
    }

    public function mobile($mobile)
    {
        return preg_replace("/(\d{3})\d\d(\d{2})/", "\$1****\$3", $mobile);
    }

    protected function province($province)
    {

    }

    protected function level($level)
    {

    }
}
