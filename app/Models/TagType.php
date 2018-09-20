<?php

namespace App\Models;

use App\Models\Traits\ListScopes;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;

class TagType extends Model
{
    use ListScopes,SoftDeletes;

    protected $table = 'tag_types';

    protected $fillable = ['name', 'color', 'sort', 'created_at', 'updated_at'];

}