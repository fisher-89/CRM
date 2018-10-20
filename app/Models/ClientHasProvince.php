<?php

namespace App\Models;

use App\Models\Traits\ListScopes;
use Illuminate\Database\Eloquent\Model;

class ClientHasProvince extends Model
{
    use ListScopes;

    protected $table = 'client_has_provincial';

    protected $fillable = ['client_id', 'province_id'];

    public $timestamps =false;

    public function province()
    {
        return $this->hasOne(Province::class, 'id', 'province_id');
    }
}