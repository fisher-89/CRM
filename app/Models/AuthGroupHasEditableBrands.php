<?php

namespace App\Models;

use App\Models\Traits\ListScopes;
use Illuminate\Database\Eloquent\Model;

class AuthGroupHasEditableBrands extends Model
{
    use ListScopes;

    protected $table = 'auth_group_has_editable_brands';

    protected $fillable = ['authority_group_id', 'brand_id'];

    public $timestamps = false;
}