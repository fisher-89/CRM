<?php

namespace App\Http\Resources;

use App\Models\Clients;
use App\Models\NoteTypes;
use Illuminate\Http\Resources\Json\ResourceCollection;

class NoteLogCollection extends ResourceCollection
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
                "client_id" => $data->client_id,
                "type" => $data->type,
                "staff_sn" => $data->staff_sn,
                "staff_name" => $data->staff_name,
                "operation_address" => $data->operation_address,
//                "changes" => $this->trans($data->changes),
                "changes" => $data->changes,
                "created_at" => $data->created_at,
                "updated_at" => $data->updated_at
            ];
        })->toArray();
    }

    private function trans($changes)
    {
        foreach ($changes as $key => $value) {
            $value = $this->chineseValue($key, $value);
            $data[$this->chinese($key)] = $value;
        }
        return isset($data) ? $data : [];
    }

    private function chineseValue($key, $value)
    {
        switch ($key) {
            case 'note_type_id':
                return [NoteTypes::where('id', $value[0])->first(), NoteTypes::where('id', $value[1])->first()];
                break;
            case 'client_id':
                return [Clients::where('id', $value[0])->first(), Clients::where('id', $value[1])->first()];
                break;
            case 'task_result':
                foreach ($value as $item) {
                    if ($item == '-1') {
                        $status[] = '失败';
                    } elseif ($item == '0') {
                        $status[] = '待处理';
                    } elseif ($item == '1') {
                        $status[] = '成功';
                    }
                }
                return isset($status) ? $status : [];
                break;
            case 'brands':
                $brand = app('api')->getBrands($value);
                $brandOne = explode(',', $value[0]);
                $brandTow = explode(',', $value[1]);
                $i = 0;
                $s = 0;
                foreach ($brand as $k => $val) {
                    if ($val['id'] == $brandOne[$i]) {
                        $brandOneArray[] = $val['name'];
                        if (count($brandOne) > $i + 1) {
                            $i++;
                        }
                    }
                    if ($val['id'] == $brandTow[$s]) {
                        $brandTowArray[] = $val['name'];
                        if (count($brandTow) > $s + 1) {
                            $s++;
                        }
                    }
                }
                $one = implode('、', isset($brandOneArray) ? $brandOneArray : []);
                $tow = implode('、', isset($brandTowArray) ? $brandTowArray : []);
                return [$one, $tow];
                break;
        }
        return $value;
    }

    private function chinese($key)
    {
        $array = [
            'id' => 'id',
            'note_type_id' => '类型',
            'client_id' => '客户',
            'client_name' => '客户姓名',
            'took_place_at' => '发生时间',
            'recorder_sn' => '记录人编号',
            'recorder_name' => '记录人姓名',
            'title' => '标题',
            'content' => '内容',
            'attachments' => '附件地址',
            'task_deadline' => '任务截止日期',
            'finished_at' => '任务完成时间',
            'task_result' => '任务结果',
            'created_at' => '添加时间',
            'updated_at' => '修改时间',
            'brands' => '品牌'
        ];
        return $array[$key];
    }
}
