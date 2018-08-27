<?php

namespace App\Services\Admin\Traits;

Trait Filterable
{
    /**
     * filter 格式化.
     *
     * @author 28youth
     * @return mixed
     */
    public function formatFilter(string $filters): array
    {
        $maps = $fields = [];
        $filters = array_filter(explode(';', $filters));
        foreach ($filters as $key => $value) {
            preg_match('/(?<mark>=|~|>=|>|<=|<)/', $value, $match);
            $filter = explode($match['mark'], $value);
            switch ($match['mark']) {
                case '=':
                    if (strpos($filter[1], '[', 0) !== false) {
                        $toArr = explode(',', trim($filter[1], '[]'));
                        array_push($maps, [
                            $filter[0] => function ($query) use ($filter, $toArr) {
                                $query->whereIn($filter[0], $toArr);
                            }
                        ]);
                        continue;
                    }
                    array_push($maps, [
                        $filter[0] => function ($query) use ($filter) {
                            $query->where($filter[0], $filter[1]);
                        }
                    ]);
                    break;

                case '~':
                    array_push($maps, [
                        $filter[0] => function ($query) use ($filter) {
                            $query->where($filter[0], 'like', "%{$filter[1]}%");
                        }
                    ]);
                    break;

                default:
                    array_push($maps, [
                        $filter[0] => function ($query) use ($filter, $match) {
                            $query->where($filter[0], $match['mark'], $filter[1]);
                        }
                    ]);
                    break;
            }

            array_push($fields, $filter[0]);
        }

        return [
            'maps' => $maps,
            'fields' => $fields,
        ];
    }

}