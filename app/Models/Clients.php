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
        'id_card_image' => 'array',
    ];

    protected $fillable = ['name', 'source_id', 'status', 'gender', 'mobile', 'wechat', 'nation', 'id_card_number',
        'native_place', 'first_cooperation_at', 'vindicator_sn', 'vindicator_name', 'remark', 'province', 'city', 'county',
        'detailed_address', 'icon', 'id_card_image', 'develop_sn', 'develop_name', 'recommend_id', 'recommend_name',
    ];

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
}