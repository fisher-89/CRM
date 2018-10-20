<?php

namespace App\Models;

use App\Models\Traits\ListScopes;
use Illuminate\Database\Eloquent\Model;

class ClientHasLevel extends Model
{
    use ListScopes;

    protected $table = 'client_has_level';

    protected $fillable = ['client_id', 'level_id'];

    public $timestamps =false;

    public function level()
    {
        return $this->hasOne(Levels::class, 'id', 'level_id');
    }
}