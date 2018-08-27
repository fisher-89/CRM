<?php

namespace App\Models;

use App\Models\Traits\ListScopes;
use Illuminate\Database\Eloquent\Model;

class ClientGroupDepartments extends Model
{
    use ListScopes;

    protected $table = 'client_group_departments';

    protected $fillable = ['authority_group_id', 'department_id', 'department_name'];

    public $timestamps = false;
}