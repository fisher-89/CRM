<?php

namespace App\Models;

use App\Models\Traits\ListScopes;
use Illuminate\Database\Eloquent\Model;

class Provincial extends Model
{
    use ListScopes;

    protected $table = 'provincial';

    protected $fillable = ['name'];

    public $timestamps =false;

}