<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Builder;

Trait ListScopes
{
    /**
     * 格式化 filter 参数，转换成sql
     *
     * @author 28youth
     * @return  \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFilterByQueryString(Builder $query): Builder
    {
        $filters = array_filter(explode(';', request('filters', '')));
        return $query->when($filters, function ($query) use ($filters) {
            foreach ($filters as $filter) {
                preg_match('/((?<relation>.*)\.|^)(?<key>.+?)(?<mark>=|~|>=|>|<=|<)(?<value>.+?)$/', $filter, $match);
                $relation = trim($match['relation']);
                if ($relation) {
                    $query->whereHas($relation, function ($query) use ($match) {
                        $this->filterBuilder($query, $match);
                    });
                } else {
                    $this->filterBuilder($query, $match);
                }
            }
        });
    }

    /**
     * 格式化 sort 参数.
     *
     * @author 28youth
     * @param  @return  \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSortByQueryString(Builder $query): Builder
    {
        $sort = request()->get('sort', '');
        $sortby = array_filter(explode('-', $sort));
        return $query->when($sortby, function ($query) use ($sortby) {
            $query->orderBy($sortby[0], $sortby[1]);
        });
    }

    /**
     * 返回带分页信息的数据
     *
     * @author 28youth
     * @param  \Illuminate\Database\Eloquent\Builder
     * @param  integer $pagesize
     * @return mixed
     */
    public function scopeWithPagination(Builder $query, int $pagesize = 10)
    {
        if (request()->has('page') && is_numeric(request('page'))) {
            $pagesize = request()->has('pagesize') ? request('pagesize') : $pagesize;
            $items = $query->paginate($pagesize);

            return [
                'data' => $items->items(),
                'total' => $items->total(),
                'page' => $items->currentPage(),
                'pagesize' => $items->perPage(),
                'totalpage' => ceil($items->total() / $items->perPage()),
            ];
        } else {
            return $query->get();
        }
    }

    protected function filterBuilder(Builder $query, $filter)
    {
        $mark = $filter['mark'];
        $key = trim($filter['key']);
        $value = trim($filter['value']);
        switch ($mark) {
            case '=':
                if (strpos($value, '[') !== false) {
                    $toArr = explode(',', trim($value, '[]'));
                    $query->whereIn($key, $toArr);
                    continue;
                }
                $query->where($key, $value);
                break;

            case '~':
                $query->where($key, 'like', "%{$value}%");
                break;

            default:
                $query->where($key, $mark, $value);
                break;
        }
    }
}