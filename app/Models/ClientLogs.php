<?php

namespace App\Models;

use App\Models\Traits\ListScopes;
use Illuminate\Database\Eloquent\Model;

class ClientLogs extends Model
{
    use ListScopes;

    protected $table = 'client_logs';

    protected $fillable = ['client_id', 'type', 'staff_sn', 'staff_name', 'operation_address', 'changes', 'status', 'restore_sn', 'restore_name', 'restore_at'];

    protected $casts = [
        'changes' => 'array',
        'operation_address' => 'array',
    ];

    public function clients()
    {
        return $this->hasOne(Clients::class, 'id', 'client_id')->withTrashed();
    }
}