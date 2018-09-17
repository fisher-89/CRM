<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class NoteCollection extends ResourceCollection
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
            foreach ($data->brands as $i){
                $brand[]=$i['brand_id'];
            }
            return [
                "id"=> $data->id,
                "note_type_id"=> $data->note_type_id,
                "client_id"=> $data->client_id,
                "client_name"=> $data->client_name,
                "took_place_at"=> $data->took_place_at,
                "recorder_sn"=> $data->recorder_sn,
                "recorder_name"=> $data->recorder_name,
                "title"=> $data->title,
                "content"=> $data->content,
                "attachments"=> $data->attachments,
                "task_deadline"=> $data->task_deadline,
                "finished_at"=> $data->finished_at,
                "task_result"=> $data->task_result,
                "created_at"=> $data->created_at == null ? null : $data->created_at->format('Y-m-d H:i:s'),
                "updated_at"=> $data->updated_at == null ? null : $data->updated_at->format('Y-m-d H:i:s'),
                "deleted_at"=> $data->deleted_at,
                "brands"=> isset($brand) ? $brand :[],
            ];
        })->toArray();
    }
}
