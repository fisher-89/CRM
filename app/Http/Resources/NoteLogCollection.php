<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class NoteLogCollection extends ResourceCollection
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
                "id" => $data->id,
                "client_id" => $data->client_id,
                "type" => $data->type,
                "staff_sn" => $data->staff_sn,
                "staff_name" => $data->staff_name,
                "operation_address" => $data->operation_address,
                "changes" => $this->trans($data->changes),
                "created_at" => $data->created_at,
                "updated_at" => $data->updated_at
            ];
        })->toArray();
    }
}
