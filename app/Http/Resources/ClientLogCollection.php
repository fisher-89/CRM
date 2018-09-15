<?php

namespace App\Http\Resources;

use App\Models\Source;
use App\Models\Tags;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ClientLogCollection extends ResourceCollection
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
//        return parent::toArray($request);

        return $this->collection->map(function ($data) {
            return [
                "id" => $data->id,
                "client_id" => $data->client_id,
                "type" => $data->type,
                "staff_sn" => $data->staff_sn,
                "staff_name" => $data->staff_name,
                "operation_address" => $data->operation_address,
                "changes" => $this->trans($data->changes),
                "restore_sn" => $data->restore_sn,
                "restore_time" => $data->restore_time,
                "created_at" => $data->created_at->format('Y-m-d H:i:s'),
                "updated_at" => $data->updated_at->format('Y-m-d H:i:s'),
                "clients" => $data->clients,
            ];
        })->toArray();
    }

    private function trans($arr)
    {
        foreach ($arr as $key => $value) {
            $value = $this->chineseValue($key, $value);
            $data[$this->chinese($key)] = $value;
        }
        return isset($data) ? $data : [];
    }

    private function chineseValue($key, $value)
    {
        switch ($key) {
            case 'source_id':
                return [Source::where('id', $value[0])->value('name'), Source::where('id', $value[1])->value('name')];
                break;
            case 'status':
                foreach ($value as $item) {
                    if ($item == '-1') {
                        $status[] = '黑名单';
                    } elseif ($item == '0') {
                        $status[] = '潜在客户';
                    } elseif ($item == '1') {
                        $status[] = '合作中';
                    } else {
                        $status[] = '合作完成';
                    }
                }
                return isset($status) ? $status : [];
                break;
            case 'brands':
                $brand = app('api')->getBrands($value);
                $brandOne = explode(',', $value[0]);
                $brandTow = explode(',', $value[1]);
                foreach ($brand as $k => $val) {
                    if (in_array($val['id'],$brandOne)) {
                        $brandOneArray[] = $val['name'];
                    }
                    if (in_array($val['id'],$brandTow)) {
                        $brandTowArray[] = $val['name'];
                    }
                }
                $one = implode('、', isset($brandOneArray) ? $brandOneArray : []);
                $tow = implode('、', isset($brandTowArray) ? $brandTowArray : []);
                return [$one, $tow];
                break;
            case 'tags':
                return [Tags::where('id', $value[0])->value('name'), Tags::where('id', $value[1])->value('name')];
                break;
            case 'shops':
                return [];
                break;
        }
        return $value;
    }

    private function chinese($key)
    {
        $array = [
            'id' => 'id',
            'name' => '姓名',
            'source_id' => '来源',
            'status' => '状态',
            'gender' => '性别',
            'mobile' => '电话',
            'wechat' => '微信',
            'nation' => '名族',
            'id_card_number' => '身份证号码',
            'native_place' => '省份',
            'present_address' => '现住地址',
            'first_cooperation_at' => '初次合作时间',
            'vindicator_sn' => '维护人编号',
            'vindicator_name' => '维护人姓名',
            'remark' => '备注',
            'brands' => '品牌',
            'tags' => '标签',
            'shops' => '店铺',
            'created_at' => '添加时间',
            'updated_at' => '修改时间',
            'deleted_at' => '删除时间',
        ];
        return $array[$key];
    }
}
