<?php

namespace App\Models;

use App\Models\Traits\ListScopes;
use Illuminate\Database\Eloquent\Model;

class Source extends Model
{
    use ListScopes;

    public $timestamps = false;

    protected $table = 'source';

    protected $fillable = ['name', 'describe', 'sort'];

}