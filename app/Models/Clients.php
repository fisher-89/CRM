<?php

namespace App\Models;

use App\Models\Traits\ListScopes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Clients extends Model
{
    use ListScopes, SoftDeletes;

    protected $table = 'clients';

    protected $casts = [
        'icon' => 'array',
        'id_card_image_f' => 'array',
        'id_card_image_b' => 'array',
    ];

    protected $fillable = ['name', 'source_id', 'status', 'gender', 'mobile', 'wechat', 'nation', 'id_card_number',
        'native_place', 'first_cooperation_at', 'vindicator_sn', 'vindicator_name', 'remark', 'province_id', 'city_id',
        'county_id', 'address', 'icon', 'id_card_image_f', 'id_card_image_b', 'develop_sn', 'develop_name', 'recommend_id',
        'recommend_name',
    ];

    public function getIdCardNumberAttribute($value)
    {
        if(strlen($value) == 18 && substr($value,0,4) != 'auto'){
            return $value;
        }
    }

    public function source()
    {
        return $this->belongsTo(Source::class,'source_id','id');
    }

    public function tags()
    {
        return $this->hasMany(ClientHasTags::class,'client_id','id');
    }

    public function brands()
    {
        return $this->hasMany(ClientHasBrands::class,'client_id','id');
    }

    public function shops()
    {
        return $this->hasMany(ClientHasShops::class,'client_id','id');
    }

    public function levels()
    {
        return $this->hasMany(ClientHasLevel::class,'client_id','id');
    }

    public function linkages()
    {
        return $this->hasMany(ClientHasLinkage::class,'client_id','id');
    }
}