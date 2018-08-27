<?php

namespace App\Models;

use App\Models\Traits\ListScopes;
use Illuminate\Database\Eloquent\Model;

class ClientGroupStaff extends Model
{
    use ListScopes;

    protected $table = 'client_group_staff';

    protected $fillable = ['authority_group_id', 'staff_sn', 'staff_name'];

    public $timestamps = false;
}