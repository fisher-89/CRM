<?php

namespace App\Models;

use App\Models\Traits\ListScopes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Linkages extends Model
{
    use ListScopes;

    protected $table = 'linkage';

    protected $fillable = [];

    public $timestamps = false;
}