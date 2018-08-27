<?php

namespace App\Models;

use App\Models\Traits\ListScopes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Notes extends Model
{
    use ListScopes, SoftDeletes;

    protected $table = 'notes';

    protected $fillable = ['note_type_id', 'client_id', 'took_place_at', 'recorder_sn','title',
        'recorder_name', 'content', 'attachments', 'task_deadline', 'finished_at', 'task_result'];

    protected $casts = [
        'attachments' => 'array',
    ];

    public function clients()
    {
        return $this->hasOne(Clients::class,'id','client_id');
    }

    public function noteType()
    {
        return $this->hasOne(NoteTypes::class,'id','note_type_id');
    }
}