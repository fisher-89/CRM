<?php

namespace App\Models;

use App\Models\Traits\ListScopes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Nations extends Model
{
    use ListScopes;

    protected $table = 'nations';

    protected $fillable = ['name', 'sort'];

    public $timestamps =false;
}