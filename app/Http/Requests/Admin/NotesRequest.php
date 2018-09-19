<?php

namespace App\Http\Requests\Admin;

use App\Models\Clients;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class NotesRequest extends FormRequest
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
            'note_type_id' => 'required|numeric|exists:note_types,id',
            'client_id' => ['required','numeric','exists:clients,id'],
            'client_name' => ['required', 'max:10',
                function ($attribute, $value, $event) {
                    $name=Clients::where('id',$this->all('client_id'))->value('name');
                    if($value != $name){
                        return $event('名字不正确');
                    }
                }
            ],
            'took_place_at' => ['required','date','before_or_equal:'.date('Y-m-d H:i:s')],
            'title' => 'required|max:20',
            'content' => 'required',
            'attachments' => 'nullable',//附件
            'task_deadline' => [
                function ($attribute, $value, $event) {
                    if ($value == false) {
                        $task = DB::table('note_types')->where('id', $this->note_type_id)->value('is_task');
                        if ($task == true) {
                            return $event('请填写定时时间');
                        }
                    } else {
                        if (!strtotime($value)) {
                            return $event('不是时间格式');
                        }
                        if (strtotime($value) < strtotime($this->took_place_at)) {
                            return $event('任务定时应在事件日期之后');
                        }
                    }
                }
            ],//定时时间
            'finished_at' => 'date|nullable',
            'task_result' => 'between:-1,1|nullable',
            'brands'=>'array|required',
            'brands.*.brand_id'=>'numeric'
        ];
    }

    public function attributes()
    {
        return [
            'note_type_id' => '事件分类',
            'client_id' => '客户信息',
            'client_name' => '名字',
            'took_place_at' => '事件时间',
            'title' => '标题',
            'content' => '内容',
            'attachments' => '附件',
            'task_deadline' => '截止事件',
            'finished_at' => '完成时间',
            'task_result' => '结果',
            'brands'=>'品牌'
        ];
    }

    public function getData()
    {
//        $user=$this->user()->staff_sn;
        return true;
    }
}
