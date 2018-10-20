<?php

namespace App\Models;

use App\Models\Traits\ListScopes;
use Illuminate\Database\Eloquent\Model;

class ClientHasProvincial extends Model
{
    use ListScopes;

    protected $table = 'client_has_provincial';

    protected $fillable = ['client_id', 'provincial_id'];

    public $timestamps =false;

    public function provincial()
    {
        return $this->hasOne(Provincial::class, 'id', 'provincial_id');
    }
}