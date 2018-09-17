<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class AuthorityCollection extends ResourceCollection
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
            if ((bool)$data->editables === true) {
                foreach ($data->editables as $item) {
                    $editables[] = $item['brand_id'];
                }
            }
            if ((bool)$data->visibles === true) {
                foreach ($data->visibles as $items) {
                    $visibles[] = $items['brand_id'];
                }
            }
            return [
                "id" => $data->id,
                "name" => $data->name,
                "description" => $data->description,
                "created_at" => $data->created_at == null ? null : $data->created_at->format('Y-m-d H:i:s'),
                "updated_at" => $data->updated_at == null ? null : $data->updated_at->format('Y-m-d H:i:s'),
                "deleted_at" => $data->deleted_at,
                "editables" => isset($editables) ? $editables : [],
                "visibles" => isset($visibles) ? $visibles : [],
                "staffs" => $data->staffs
            ];
        })->toArray();
    }
}
