<?php

namespace App\Models;

use App\Models\Traits\ListScopes;
use Illuminate\Database\Eloquent\Model;

class ClientHasBrands extends Model
{
    use ListScopes;

    protected $table = 'client_has_brands';

    protected $fillable = [ 'client_id', 'brand_id'];
    public $timestamps = false;
}