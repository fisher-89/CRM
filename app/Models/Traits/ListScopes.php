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
        $filters = $this->unserializeFilters();
        return $query->when($filters, function ($query) use ($filters) {
            $this->addFiltersToQuery($query, $filters);
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
            $items = $query->paginate(request()->get('pagesize', $pagesize));

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

    protected function unserializeFilters()
    {
        $inputString = request('filters');
        if (!$inputString) return false;
        $inputArr = str_split(request('filters'));
        $filters = [];
        $key = [0];
        $implodedKey = '0';
        foreach ($inputArr as $string) {
            switch ($string) {
                case'(':
                    array_push($key, 0);
                    $implodedKey = implode('.', $key);
                    break;
                case')':
                    array_pop($key);
                    $implodedKey = implode('.', $key);
                    break;
                case';':
                    array_push($key, abs(array_pop($key)) + 1);
                    $implodedKey = implode('.', $key);
                    break;
                case'|':
                    array_push($key, -(abs(array_pop($key)) + 1));
                    $implodedKey = implode('.', $key);
                    break;
                default:
                    $originalValue = array_get($filters, $implodedKey) ?: '';
                    array_set($filters, $implodedKey, $originalValue . $string);
                    break;
            }
        }
        return $filters;
    }

    protected function addFiltersToQuery($query, $filters)
    {
        foreach ($filters as $key => $filter) {
            $isOrWhere = $key < 0;
            if (is_array($filter)) {
                if ($isOrWhere) {
                    $query->orWhere(function ($query) use ($filter) {
                        $this->addFiltersToQuery($query, $filter);
                    });
                } else {
                    $query->where(function ($query) use ($filter) {
                        $this->addFiltersToQuery($query, $filter);
                    });
                }
            } else {
                preg_match('/((?<relation>.*)\.|^)(?<key>.+?)(?<mark>=|~|>=|>|<=|<)(?<value>.+?)$/', $filter, $match);
                $relation = trim($match['relation']);
                if ($relation) {
                    $query->whereHas($relation, function ($query) use ($match, $isOrWhere) {
                        $this->filterBuilder($query, $match, $isOrWhere);
                    });
                } else {
                    $this->filterBuilder($query, $match, $isOrWhere);
                }
            }
        }
    }

    protected function filterBuilder(Builder $query, $filter, $isOrWhere = false)
    {
        $mark = $filter['mark'];
        $key = trim($filter['key']);
        $value = trim($filter['value']);
        switch ($mark) {
            case '=':
                if (strpos($value, '[') !== false) {
                    $toArr = explode(',', trim($value, '[]'));
                    if ($isOrWhere) {
                        $query->orWhereIn($key, $toArr);
                    } else {
                        $query->whereIn($key, $toArr);
                    }
                    continue;
                }
                if ($isOrWhere) {
                    $query->orWhere($key, $value);
                } else {
                    $query->where($key, $value);
                }

                break;
            case '~':
                if ($isOrWhere) {
                    $query->orWhere($key, 'like', "%{$value}%");
                } else {
                    $query->where($key, 'like', "%{$value}%");
                }

                break;
            default:
                if ($isOrWhere) {
                    $query->orWhere($key, $mark, $value);
                } else {
                    $query->where($key, $mark, $value);
                }
                break;
        }
    }
}