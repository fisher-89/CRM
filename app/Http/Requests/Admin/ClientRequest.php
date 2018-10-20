<?php

namespace App\Http\Requests\Admin;

use App\Models\Clients;
use App\Models\Nations;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ClientRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->getData();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $recommend = $this->recommend_id;
        $develop = $this->develop_sn;
        return [
            'name' => 'required|max:10',
            'source_id' => 'required|numeric|max:5|exists:source,id',
            'status' => ['required', 'max:2', 'numeric', function ($attribute, $value, $event) {
                if ($value != '-1' && $value != '0' && $value != '1' && $value != '2') {
                    return $event('未知状态');
                }
            }],
            'gender' => ['required', 'max:1', function ($attribute, $value, $event) {
                if ($value != '男' && $value != '女') {
                    return $event('性别不正确');
                }
            }],
            'mobile' => ['required', 'digits:11', 'regex:/^1[3456789]\d{9}$/',
                function ($attribute, $value, $event) {
                    $id = $this->route('id');
                    if (isset($id)) {
                        $mobileNot = DB::table('clients')->where('mobile', $value)->whereNotIn('id', explode(' ', $id))->first();
                        if (true === (bool)$mobileNot) {
                            return $event('电话号码已经存在');
                        }
                    } else {
                        $mobile = DB::table('clients')->where('mobile', $value)->first();
                        if (true === (bool)$mobile) {
                            return $event('电话号码已经存在');
                        }
                    }
                }
            ],
            'wechat' => 'max:20|nullable',
            'nation' => 'required|max:5|exists:nations,name',
            'id_card_number' => ['required',
                function ($attribute, $value, $event) {
                    $id = $this->route('id');
                    if (isset($id)) {
                        $cardNumberNot = DB::table('clients')->where('id_card_number', $value)->whereNotIn('id', explode(' ', $id))->first();
                        if (true === (bool)$cardNumberNot) {
                            return $event('身份证已经存在');
                        }
                    } else {
                        $cardNumber = DB::table('clients')->where('id_card_number', $value)->first();
                        if (true === (bool)$cardNumber) {
                            return $event('身份证已经存在');
                        }
                    }
                },
                'max:18',
                'regex:/(^[1-9]\d{5}(18|19|([23]\d))\d{2}((0[1-9])|(10|11|12))(([0-2][1-9])|10|20|30|31)\d{3}[0-9Xx]$)|(^[1-9]\d{5}\d{2}((0[1-9])|(10|11|12))(([0-2][1-9])|10|20|30|31)\d{2}$)/'],
            'native_place' => 'nullable|max:8|exists:provinces,name',
            'province_id' => 'nullable|numeric|exists:linkage,id',
            'city_id' => 'nullable|numeric|exists:linkage,id',
            'county_id' => 'nullable|numeric|exists:linkage,id',
            'address' => 'nullable|max:100',
            'icon' => 'nullable',
            'id_card_image_f' => 'nullable',
            'id_card_image_b' => 'nullable',
            'develop_sn' => ['nullable', 'max:6', function ($attribute, $value, $event) use ($recommend) {
                if ((bool)$value === true) {
                    try {
                        $develop = app('api')->withRealException()->getStaff($value);
                        if ($develop == false) {
                            return $event('错误');
                        }
                    } catch (\Exception $exception) {
                        return $event('错误');
                    }
                } else {
                    if ((bool)$recommend === false) {
                        return $event('员工开发人或客户介绍人必须任选其一');
                    }
                }
            }],
            'develop_name' => 'nullable|max:10',
            'recommend_id' => ['nullable', 'exists:clients,id', function ($attribute, $value, $event) use ($develop) {
                if ((bool)$value === false) {
                    if ((bool)$develop === false) {
                        return $event('员工开发人或客户介绍人必须任选其一');
                    }
                }
            }],
            'recommend_name' => 'nullable|max:10',
            'tags' => 'array|nullable',
            'tags.*.tag_id' => ['exists:tags,id', 'numeric', 'nullable'],
            'first_cooperation_at' => 'nullable|date',
            'vindicator_sn' => ['numeric', 'nullable',
                function ($attribute, $value, $event) {
                    if ((bool)$value === true) {
                        try {
                            $oa = app('api')->withRealException()->getStaff($value);
                            if ((bool)$oa === false) {
                                return $event('维护人错误');
                            }
                        } catch (\Exception $e) {
                            return $event('维护人错误');
                        }
                    }
                }
            ],
            'provinces' => ['array',function($attribute, $value, $event){
                if (count($value) == 0) {
                    return $event('合作省份必选');
                }
            }],
            'provinces.*.province_id' => 'numeric|exists:provinces,id|required',
            'levels' => 'array',
            'levels.*.level_id' => 'numeric|exists:levels,id',
            'vindicator_name' => 'max:10',
            'remark' => 'max:200',
            'brands.*.brand_id' => [
                'numeric', 'required'
            ],
            'brands' => ['array', function ($attribute, $value, $event) {
                if (count($value) == 0) {
                    return $event('未选择品牌');
                }
            }],
            'shops' => 'array|nullable',
            'shops.*.shop_sn' => [
                'required',
            ]
        ];
    }

    public function attributes()
    {
        return [
            'name' => '客户姓名',
            'source_id' => '客户来源',
            'status' => '客户状态',
            'gender' => '性别',
            'mobile' => '电话',
            'wechat' => '微信',
            'nation' => '民族',
            'id_card_number' => '身份证号码',
            'native_place' => '籍贯',
            'province_id' => '省级',
            'city_id' => '市级',
            'county_id' => '县级',
            'address' => '详细地址',
            'icon' => '头像照片',
            'id_card_image_f' => '身份证照片正面',
            'id_card_image_b' => '身份证照片反面',
            'provinces' => '合作省份',
            'levels' => '客户等级',
            'develop_sn' => '开发人编号',
            'develop_name' => '开发人姓名',
            'recommend_id' => '介绍人id',
            'recommend_name' => '介绍人姓名',
            'tags' => '标签',
            'first_cooperation_at' => '第一次合作时间',
            'vindicator_sn' => '维护人编号',
            'vindicator_name' => '维护人姓名',
            'remark' => '备注',
            'brands' => '品牌',
            //品牌  店铺
        ];
    }

    public function getData()
    {
//        $user=$this->user()->staff_sn;
        return true;
    }
}
