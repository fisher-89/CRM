<?php

namespace App\Models;

use App\Models\Traits\ListScopes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class NoteHasBrand extends Model
{
    use ListScopes;

    protected $table = 'note_has_brand';

    protected $fillable = ['note_id','brand_id' ];

    public $timestamps =false;

}