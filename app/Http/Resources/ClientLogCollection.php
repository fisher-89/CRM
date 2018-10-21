<?php

namespace App\Http\Resources;

use App\Models\Levels;
use App\Models\Linkages;
use App\Models\Source;
use App\Models\Tags;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\DB;

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
        $brand = app('api')->getBrands([1]);
        return $this->collection->map(function ($data)use($brand) {
            return [
                "id" => $data->id,
                "client_id" => $data->client_id === null ? '' : $data->client_id,
                "type" => $data->type === null ? '' : $data->type,
                "staff_sn" => $data->staff_sn === null ? '' : $data->staff_sn,
                "staff_name" => $data->staff_name === null ? '' : $data->staff_name,
                "operation_address" => $data->operation_address === null ? '' : $data->operation_address,
                "changes" => $this->trans($data->changes,$brand),
                "status" => $data->status,
                "restore_sn" => $data->restore_sn === null ? '' : $data->restore_sn,
                "restore_name" => $data->restore_name === null ? '' : $data->restore_name,
                "restore_at" => $data->restore_at === null ? '' : $data->restore_at,
                "created_at" => $data->created_at === null ? '' : $data->created_at->format('Y-m-d H:i:s'),
                "updated_at" => $data->updated_at === null ? '' : $data->updated_at->format('Y-m-d H:i:s'),
                "clients" => $data->clients,
            ];
        })->toArray();
    }

    private function trans($arr,$brand)
    {
        foreach ($arr as $key => $value) {
            $value = $this->chineseValue($key, $value,$brand);
            $data[$this->chinese($key)] = $value;
        }
        return isset($data) ? $data : [];
    }

    private function chineseValue($key, $value,$brand)
    {
        switch ($key) {
            case 'source_id':
                $source = [];
                foreach (explode(',', $value[0]) as $k => $sou) {
                    $source[] = Source::where('id', $sou)->value('name');
                }
                $source1 = [];
                foreach (explode(',', $value[1]) as $key => $sour) {
                    $source1[] = Source::where('id', $sour)->value('name');
                }
                return [implode('、', $source), implode('、', $source1)];
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
                $brandOne = explode(',', $value[0]);
                $brandTow = explode(',', $value[1]);
                foreach ($brand as $k => $val) {
                    if (in_array($val['id'], $brandOne)) {
                        $brandOneArray[] = $val['name'];
                    }
                    if (in_array($val['id'], $brandTow)) {
                        $brandTowArray[] = $val['name'];
                    }
                }
                $one = implode('、', isset($brandOneArray) ? $brandOneArray : []);
                $tow = implode('、', isset($brandTowArray) ? $brandTowArray : []);
                return [$one, $tow];
                break;
            case 'tags':
                $name = [];
                foreach (explode(',', $value[0]) as $k => $v) {
                    $name[] = Tags::where('id', $v)->value('name');
                }
                $name1 = [];
                foreach (explode(',', $value[1]) as $key => $val) {
                    $name1[] = Tags::where('id', $val)->value('name');
                }
                return [implode('、', $name), implode('、', $name1)];
                break;
            case 'levels';
                $level = [];
                foreach (explode(',', $value[0]) as $le){
                    $level[] = Levels::where('id',$le)->value('name');
                }
                $level1 = [];
                foreach (explode(',', $value[1]) as $lv){
                    $level1[] = Levels::where('id',$lv)->value('name');
                }
                return [implode('、', $level), implode('、', $level1)];
                break;
            case 'linkages';
                $linkage = [];
                foreach (explode(',', $value[0]) as $linkage){
                    $linkage[] = Linkages::where('id',$linkage)->value('name');
                }
                $linkage1 = [];
                foreach (explode(',', $value[1]) as $linkages){
                    $linkage1[] = Linkages::where('id',$linkages)->value('name');
                }
                return [implode('、', $linkage), implode('、', $linkage1)];
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
            'province_id' => '省级',
            'city_id' => '市级',
            'county_id' => '县级',
            'address' => '详细地址',
            'icon' => '头像照片',
            'id_card_image_f' => '身份证照片正面',
            'id_card_image_b' => '身份证照片反面',
            'linkages' => '合作省份',
            'levels' => '客户等级',
            'develop_sn' => '开发人编号',
            'develop_name' => '开发人姓名',
            'recommend_id' => '介绍人id',
            'recommend_name' => '介绍人姓名',
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
