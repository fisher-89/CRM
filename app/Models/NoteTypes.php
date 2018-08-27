<?php

namespace App\Models;

use App\Models\Traits\ListScopes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class NoteTypes extends Model
{
    use ListScopes,SoftDeletes;

    protected $table = 'note_types';

    protected $fillable = ['name', 'is_task','sort'];

    public function tagType()
    {
        return $this->belongsTo(TagType::class,'type_id','id');
    }
}