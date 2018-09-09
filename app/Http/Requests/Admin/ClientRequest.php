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
            'mobile' => ['required', 'digits:11','regex:/^1[3456789]\d{9}$/',
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
                'max:18|',
                'regex:/(^[1-9]\d{5}(18|19|([23]\d))\d{2}((0[1-9])|(10|11|12))(([0-2][1-9])|10|20|30|31)\d{3}[0-9Xx]$)|(^[1-9]\d{5}\d{2}((0[1-9])|(10|11|12))(([0-2][1-9])|10|20|30|31)\d{2}$)/'],
            'native_place' => 'nullable|max:8',
            'present_address' => 'nullable|max:150',
            'tags'=>'array|nullable',
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
            'vindicator_name' => 'max:10',
            'remark' => 'max:200',
            'brands.*.brand_id' => [
                'numeric', 'required'
            ],
            'brands'=>['array',function($attribute, $value, $event){
                if(count($value) == 0){
                    return $event('未选择品牌');
                }
            }],
            'shops'=>'array|nullable',
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
            'present_address' => '现住地址',
            'tag_id' => '标签',
            'first_cooperation_at' => '第一次合作时间',
            'vindicator_sn' => '维护人编号',
            'vindicator_name' => '维护人姓名',
            'remark' => '备注'
            //品牌  店铺
        ];
    }

    public function getData()
    {
//        $user=$this->user()->staff_sn;
        return true;
    }
}
