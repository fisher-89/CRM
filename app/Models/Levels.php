<?php

namespace App\Models;

use App\Models\Traits\ListScopes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Levels extends Model
{
    use ListScopes, SoftDeletes;

    protected $table = 'levels';

    protected $fillable = ['name', 'explain'];

}