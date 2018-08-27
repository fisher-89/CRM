<?php

namespace App\Models;

use App\Models\Traits\ListScopes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AuthorityGroups extends Model
{
    use ListScopes,SoftDeletes;

    protected $table = 'authority_groups';

    protected $fillable = ['name','auth_type','auth_brand'];

    public function staffs()
    {
        return $this->hasMany(clientGroupStaff::class,'authority_group_id','id');
    }

    public function departments()
    {
        return $this->hasMany(ClientGroupDepartments::class,'authority_group_id','id');
    }

    public function noteStaff()
    {
        return $this->hasMany(NoteGroupStaff::class,'authority_group_id','id');
    }
}