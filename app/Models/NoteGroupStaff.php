<?php

namespace App\Models;

use App\Models\Traits\ListScopes;
use Illuminate\Database\Eloquent\Model;

class NoteGroupStaff extends Model
{
    use ListScopes;

    protected $table = 'note_group_staff';

    protected $fillable = ['authority_group_id', 'staff_sn', 'staff_name'];

    public $timestamps =false;
}