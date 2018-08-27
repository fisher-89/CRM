<?php

namespace App\Models;

use App\Models\Traits\ListScopes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tags extends Model
{
    use ListScopes, SoftDeletes;

    protected $table = 'tags';

    protected $fillable = ['type_id', 'name',  'describe', 'sort'];

    public function tagType()
    {
        return $this->belongsTo(TagType::class,'type_id','id');
    }
}