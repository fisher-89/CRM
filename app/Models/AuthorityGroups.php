<?php

namespace App\Models;

use App\Models\Traits\ListScopes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AuthorityGroups extends Model
{
    use ListScopes,SoftDeletes;

    protected $table = 'authority_groups';

    protected $fillable = ['name','description'];

    public function staffs()
    {
        return $this->hasMany(AuthGroupHasStaff::class,'authority_group_id','id');
    }

    public function editables()
    {
        return $this->hasMany(AuthGroupHasEditableBrands::class,'authority_group_id','id');
    }

    public function visibles()
    {
        return $this->hasMany(AuthGroupHasVisibleBrands::class,'authority_group_id','id');
    }
}