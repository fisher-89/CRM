<?php

namespace App\Models;

use App\Models\Traits\ListScopes;
use Illuminate\Database\Eloquent\Model;

class Province extends Model
{
    use ListScopes;

    protected $table = 'province';

    protected $fillable = ['name'];

    public $timestamps =false;

}