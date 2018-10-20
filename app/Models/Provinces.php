<?php

namespace App\Models;

use App\Models\Traits\ListScopes;
use Illuminate\Database\Eloquent\Model;

class Provinces extends Model
{
    use ListScopes;

    protected $table = 'provinces';

    protected $fillable = ['name'];

    public $timestamps =false;

}