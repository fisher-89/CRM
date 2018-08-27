<?php 

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Builder;

Trait HasCompositePrimaryKey
{
    /**
     * Get the value indicating whether the IDs are incrementing.
     *
     * @return bool
     */
    public function getIncrementing()
    {
        return false;
    }

    /**
     * Set the keys for a save update query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function setKeysForSaveQuery(Builder $query)
    {
        foreach ($this->getKeyName() as $key) {
            $query->where($key, '=', $this->original[$key] ?? $this->getAttribute($key));
        }

        return $query;
    }
}