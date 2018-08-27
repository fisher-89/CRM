<?php

namespace App\Models;

use App\Models\Traits\ListScopes;
use Illuminate\Database\Eloquent\Model;

class ClientHasTags extends Model
{
    use ListScopes;

    protected $table = 'client_has_tags';

    public $timestamps = false;

    protected $fillable = ['client_id', 'tag_id'];

    public function tag()
    {
        return $this->hasOne(Tags::class, 'id', 'tag_id');
    }
}