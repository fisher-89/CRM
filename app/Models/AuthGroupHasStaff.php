<?php

namespace App\Models;

use App\Models\Traits\ListScopes;
use Illuminate\Database\Eloquent\Model;

class AuthGroupHasStaff extends Model
{
    use ListScopes;

    protected $table = 'auth_group_has_staff';

    protected $fillable = ['authority_group_id', 'staff_sn', 'staff_name'];

    public $timestamps =false;
}