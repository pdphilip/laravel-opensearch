<?php

namespace PDPhilip\OpenSearch\DSL;

class ParameterBuilder
{
    public static function matchAll(): array
    {
        return [
            'query' => [
                'match_all' => new \stdClass(),
            ],
        ];
    }

    public static function queryStringQuery($string): array
    {
        return [
            'query' => [
                'query_string' => [
                    'query' => $string,
                ],
            ],
        ];
    }

    public static function query($dsl): array
    {
        return [
            'query' => $dsl,
        ];
    }


    public static function fieldSort($field, $payload, $allowId = false): array
    {
        if ($field === '_id' && !$allowId) {
            return [];
        }
        if (!empty($payload['is_geo'])) {
            return self::fieldSortGeo($field, $payload);
        }
        if (!empty($payload['is_nested'])) {
            return self::filterNested($field, $payload);
        }
        $sort = [];
        $sort['order'] = $payload['order'] ?? 'asc';
        if (!empty($payload['mode'])) {
            $sort['mode'] = $payload['mode'];
        }
        if (!empty($payload['missing'])) {
            $sort['missing'] = $payload['missing'];
        }

        return [
            $field => $sort,
        ];
    }

    public static function fieldSortGeo($field, $payload): array
    {
        $sort = [];
        $sort[$field] = $payload['pin'];
        $sort['order'] = $payload['order'] ?? 'asc';
        $sort['unit'] = $payload['unit'] ?? 'km';

        if (!empty($payload['mode'])) {
            $sort['mode'] = $payload['mode'];
        }
        if (!empty($payload['type'])) {
            $sort['distance_type'] = $payload['type'];
        }

        return [
            '_geo_distance' => $sort,
        ];
    }

    public static function filterNested($field, $payload)
    {
        $sort = [];
        $pathParts = explode('.', $field);
        $path = $pathParts[0];
        $sort['order'] = $payload['order'] ?? 'asc';
        if (!empty($payload['mode'])) {
            $sort['mode'] = $payload['mode'];
        }
        $sort['nested'] = [
            'path' => $path,
        ];


        return [
            $field => $sort,
        ];
    }

    public static function maxAggregation($field): array
    {
        return [
            'max' => [
                'field' => $field,
            ],
        ];
    }

    public static function minAggregation($field): array
    {
        return [
            'min' => [
                'field' => $field,
            ],
        ];
    }

    public static function avgAggregation($field): array
    {
        return [
            'avg' => [
                'field' => $field,
            ],
        ];
    }

    public static function sumAggregation($field): array
    {
        return [
            'sum' => [
                'field' => $field,
            ],
        ];
    }

    public static function matrixAggregation(array $fields): array
    {
        return [
            'matrix_stats' => [
                'fields' => $fields,
            ],
        ];
    }

    public static function multipleAggregations($aggregations, $field)
    {
        $aggs = [];
        foreach ($aggregations as $aggregation) {
            switch ($aggregation) {
                case 'max':
                    $aggs['max_'.$field] = self::maxAggregation($field);
                    break;
                case 'min':
                    $aggs['min_'.$field] = self::minAggregation($field);
                    break;
                case 'avg':
                    $aggs['avg_'.$field] = self::avgAggregation($field);
                    break;
                case 'sum':
                    $aggs['sum_'.$field] = self::sumAggregation($field);
                    break;
                case 'matrix':
                    $aggs['matrix_'.$field] = self::matrixAggregation([$field]);
                    break;
                case 'count':
                    $aggs['count_'.$field] = [
                        'value_count' => [
                            'field' => $field,
                        ],
                    ];
                    break;
            }
        }

        return $aggs;

    }

}