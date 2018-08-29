<?php

namespace App\Models;

use App\Models\Traits\ListScopes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Clients extends Model
{
    use ListScopes, SoftDeletes;

    protected $table = 'clients';

    protected $fillable = ['name', 'source_id', 'status', 'gender', 'mobile', 'wechat', 'nation', 'id_card_number',
        'native_place', 'present_address', 'first_cooperation_at', 'vindicator_sn', 'vindicator_name', 'remark'];

    public function source()
    {
        return $this->belongsTo(Source::class,'source_id','id');
    }

    public function Tags()
    {
        return $this->hasMany(ClientHasTags::class,'client_id','id');
    }

    public function Brands()
    {
        return $this->hasMany(ClientHasBrands::class,'client_id','id');
    }

    public function Shops()
    {
        return $this->hasMany(ClientHasShops::class,'client_id','id');
    }
}