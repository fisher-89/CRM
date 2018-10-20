<?php

namespace App\Models;

use App\Models\Traits\ListScopes;
use Illuminate\Database\Eloquent\Model;

class ClientHasLinkage extends Model
{
    use ListScopes;

    protected $table = 'client_has_linkage';

    protected $fillable = ['client_id', 'linkage_id'];

    public $timestamps =false;

    public function linkages()
    {
        return $this->hasOne(Linkages::class, 'id', 'linkage_id');
    }
}