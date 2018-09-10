<?php

namespace App\Models;

use App\Models\Traits\ListScopes;
use Illuminate\Database\Eloquent\Model;

class NoteLogs extends Model
{
    use ListScopes;

    protected $table = 'note_logs';

    protected $fillable = ['note_id', 'type','staff_sn','operation_address','staff_name','changes'];

    protected $casts = [
        'operation_address' => 'array',
        'changes'=>'array',
    ];

    public $timestamps = false;

    public function notes()
    {
        return $this->hasOne(Notes::class,'id','note_id')->withTrashed();
    }
}