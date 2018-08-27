<?php

namespace App\Models;

use App\Models\Traits\ListScopes;
use Illuminate\Database\Eloquent\Model;

class ClientHasShops extends Model
{
    use ListScopes;

    protected $table = 'client_has_shops';

    protected $fillable = ['client_id', 'shop_id'];

    public $timestamps = false;
}