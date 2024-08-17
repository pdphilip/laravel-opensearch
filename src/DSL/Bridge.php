<?php

namespace PDPhilip\OpenSearch\DSL;

use OpenSearch\Common\Exceptions\ClientErrorResponseException as ClientResponseException;
use OpenSearch\Common\Exceptions\Missing404Exception as MissingParameterException;
use OpenSearch\Common\Exceptions\ServerErrorResponseException as ServerResponseException;
use Exception;
use OpenSearch\Client;
use PDPhilip\OpenSearch\DSL\exceptions\ParameterException;
use PDPhilip\OpenSearch\DSL\exceptions\QueryException;
use PDPhilip\OpenSearch\Connection;


class Bridge
{

    use QueryBuilder, IndexInterpreter;

    protected Connection $connection;

    protected Client $client;

    protected string|bool $errorLogger = false;

    protected int|null $maxSize = 10; //OS default

    private string|null $index;

    private string|null $indexPrefix;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
        $this->client = $this->connection->getClient();
        $this->index = $this->connection->getIndex();
        $this->maxSize = $this->connection->getMaxSize();
        $this->indexPrefix = $this->connection->getIndexPrefix();
        $this->errorLogger = $this->connection->getErrorLoggingIndex();

    }

    //----------------------------------------------------------------------
    // PIT
    //----------------------------------------------------------------------

    /**
     * @throws QueryException
     */
    public function processOpenPit($keepAlive = '5m'): string
    {
        $params = [
            'index'      => $this->index,
            'keep_alive' => $keepAlive,

        ];
        $res = $this->client->createPointInTime($params);
        try {
            if (!empty($res['pit_id'])) {
                return $res['pit_id'];
            }

            throw new Exception('Error on PIT creation. No ID returned.');
        } catch (Exception $e) {
            $this->throwError($e, $params, $this->_queryTag(__FUNCTION__));
        }

    }

    /**
     * @throws QueryException
     */
    public function processPitFind($wheres, $options, $columns, $pitId, $searchAfter = false, $keepAlive = '5m')
    {
        $params = $this->buildParams($this->index, $wheres, $options, $columns);
        unset($params['index']);

        $params['body']['pit'] = [
            'id'         => $pitId,
            'keep_alive' => $keepAlive,
        ];
        if (empty($params['body']['sort'])) {
            $params['body']['sort'] = [];
            $params['body']['sort'][] = ['_id' => ['order' => 'asc']];
        }
        //order catch by shard doc


        if ($searchAfter) {
            $params['body']['search_after'] = $searchAfter;
        }
        try {
            $process = $this->client->search($params);


            return $this->_sanitizePitSearchResponse($process, $params, $this->_queryTag(__FUNCTION__));


        } catch (Exception $e) {
            $this->throwError($e, $params, $this->_queryTag(__FUNCTION__));
        }


    }


    /**
     * @throws QueryException
     */
    public function processClosePit($id): bool
    {

        $params = [
            'body' => [
                'pit_id' => $id,
            ],

        ];
        try {
            $res = $this->client->deletePointInTime($params);

            return !empty($res['pits'][0]['successful']);

        } catch (Exception $e) {
            $this->throwError($e, $params, $this->_queryTag(__FUNCTION__));
        }
    }

    //----------------------------------------------------------------------
    //  BYO Query
    //----------------------------------------------------------------------

    /**
     * @throws Exception
     */
    public function processSearchRaw($bodyParams, $returnRaw): Results
    {
        $params = [
            'index' => $this->index,
            'body'  => $bodyParams,

        ];
        try {
            $process = $this->client->search($params);
            if ($returnRaw) {
                return $this->_return($process, [], $params, $this->_queryTag(__FUNCTION__));
            }

            return $this->_sanitizeSearchResponse($process, $params, $this->_queryTag(__FUNCTION__));
        } catch (Exception $e) {

            $this->throwError($e, $params, $this->_queryTag(__FUNCTION__));
        }
    }

    /**
     * @throws QueryException
     */
    public function processAggregationRaw($bodyParams): Results
    {
        $params = [
            'index' => $this->index,
            'body'  => $bodyParams,

        ];
        try {
            $process = $this->client->search($params);

            return $this->_sanitizeRawAggsResponse($process, $params, $this->_queryTag(__FUNCTION__));
        } catch (Exception $e) {

            $this->throwError($e, $params, $this->_queryTag(__FUNCTION__));
        }
    }

    /**
     * @throws QueryException
     */
    public function processIndicesDsl($method, $params): Results
    {
        try {
            $process = $this->client->indices()->{$method}($params);

            return $this->_sanitizeSearchResponse($process, $params, $this->_queryTag(__FUNCTION__));
        } catch (Exception $e) {

            $this->throwError($e, $params, $this->_queryTag(__FUNCTION__));
        }
    }

    //----------------------------------------------------------------------
    // To DSL
    //----------------------------------------------------------------------

    public function processToDsl($wheres, $options, $columns)
    {
        return $this->buildParams($this->index, $wheres, $options, $columns);
    }

    public function processToDslForSearch($searchParams, $searchOptions, $wheres, $opts, $fields, $cols)
    {
        return $this->buildSearchParams($this->index, $searchParams, $searchOptions, $wheres, $opts, $fields, $cols);
    }


    //----------------------------------------------------------------------
    // Read Queries
    //----------------------------------------------------------------------

    /**
     * @throws QueryException
     */
    public function processFind($wheres, $options, $columns): Results
    {
        $params = $this->buildParams($this->index, $wheres, $options, $columns);

        return $this->_returnSearch($params, __FUNCTION__);
    }

    /**
     * @throws QueryException
     */
    public function processSearch($searchParams, $searchOptions, $wheres, $opts, $fields, $cols)
    {
        $params = $this->buildSearchParams($this->index, $searchParams, $searchOptions, $wheres, $opts, $fields, $cols);

        return $this->_returnSearch($params, __FUNCTION__);

    }

    /**
     * @throws QueryException
     */
    protected function _returnSearch($params, $source)
    {
        if (empty($params['size'])) {
            $params['size'] = $this->maxSize;
        }
        try {

            $process = $this->client->search($params);

            return $this->_sanitizeSearchResponse($process, $params, $this->_queryTag($source));

        } catch (Exception $e) {

            $this->throwError($e, $params, $this->_queryTag(__FUNCTION__));
        }
    }

    /**
     * @throws QueryException
     * @throws ParameterException
     */
    public function processDistinct($wheres, $options, $columns, $includeDocCount = false): Results
    {
        if ($columns && !is_array($columns)) {
            $columns = [$columns];
        }
        $sort = $options['sort'] ?? [];
        $skip = $options['skip'] ?? false;
        $limit = $options['limit'] ?? false;
        unset($options['sort']);
        unset($options['skip']);
        unset($options['limit']);

        if ($sort) {
            $sortField = key($sort);
            $sortDir = $sort[$sortField]['order'] ?? 'asc';
            $sort = [$sortField => $sortDir];
        }


        $params = $this->buildParams($this->index, $wheres, $options);
        try {

            $params['body']['aggs'] = $this->createNestedAggs($columns, $sort);

            $response = $this->client->search($params);


            $data = [];
            if (!empty($response['aggregations'])) {
                $data = $this->_sanitizeDistinctResponse($response['aggregations'], $columns, $includeDocCount);
            }

            //process limit and skip from all results
            if ($skip || $limit) {
                $data = array_slice($data, $skip, $limit);
            }

            return $this->_return($data, $response, $params, $this->_queryTag(__FUNCTION__));
        } catch (Exception $e) {

            $this->throwError($e, $params, $this->_queryTag(__FUNCTION__));
        }

    }

    /**
     * @throws ParameterException
     */
    public function processShowQuery($wheres, $options, $columns)
    {
        $params = $this->buildParams($this->index, $wheres, $options, $columns);

        return $params['body']['query']['query_string']['query'] ?? null;
    }


    //----------------------------------------------------------------------
    // Write Queries
    //----------------------------------------------------------------------

    /**
     * @throws QueryException
     */
    public function processSave($data, $refresh): Results
    {
        $id = null;
        if (isset($data['_id'])) {
            $id = $data['_id'];
            unset($data['_id']);
        }
        if (isset($data['_index'])) {
            unset($data['_index']);
        }
        if (isset($data['_meta'])) {
            unset($data['_meta']);
        }
        $params = [
            'index' => $this->index,
            'body'  => $data,
        ];
        if ($id) {
            $params['id'] = $id;

        }
        if ($refresh) {
            $params['refresh'] = $refresh;
        }

        try {
            $response = $this->client->index($params);
            $savedData = ['_id' => $response['_id']] + $data;

            return $this->_return($savedData, $response, $params, $this->_queryTag(__FUNCTION__));
        } catch (Exception $e) {
            $this->throwError($e, $params, $this->_queryTag(__FUNCTION__));
        }


    }

    /**
     * @throws QueryException
     */
    public function processInsertOne($values, $refresh): Results
    {
        return $this->processSave($values, $refresh);
    }

    /**
     * @throws QueryException
     * @throws ParameterException
     */
    public function processUpdateMany($wheres, $newValues, $options, $refresh = null): Results
    {
        $resultMeta['modified'] = 0;
        $resultMeta['failed'] = 0;
        $resultData = [];
        $data = $this->processFind($wheres, $options, []);

        if (!empty($data->data)) {
            foreach ($data->data as $currentData) {

                foreach ($newValues as $field => $value) {
                    $currentData[$field] = $value;
                }
                $updated = $this->processSave($currentData, $refresh);
                if ($updated->isSuccessful()) {
                    $resultMeta['modified']++;
                    $resultData[] = $updated->data;
                } else {
                    $resultMeta['failed']++;
                }
            }
        }

        $params['query'] = $this->_buildQuery($wheres);
        $params['queryOptions'] = $options;
        $params['updateValues'] = $newValues;


        return $this->_return($resultData, $resultMeta, $params, $this->_queryTag(__FUNCTION__));
    }

    /**
     * @throws QueryException
     * @throws ParameterException
     */
    public function processIncrementMany($wheres, $newValues, $options, $refresh): Results
    {
        //TODO INC on nested objects - maybe

        $incField = '';
        foreach ($newValues['inc'] as $field => $incValue) {
            $incField = $field;
        }
        $resultMeta['modified'] = 0;
        $resultMeta['failed'] = 0;
        $resultData = [];
        $data = $this->processFind($wheres, $options, []);
        if (!empty($data->data)) {
            foreach ($data->data as $currentData) {

                $currentValue = $currentData[$incField] ?? 0;
                $currentValue += $newValues['inc'][$incField];
                $currentData[$incField] = (int)$currentValue;

                if (!empty($newValues['set'])) {
                    foreach ($newValues['set'] as $field => $value) {
                        $currentData[$field] = $value;
                    }
                }
                $updated = $this->processSave($currentData, $refresh);
                if ($updated->isSuccessful()) {
                    $resultMeta['modified']++;
                    $resultData[] = $updated->data;
                } else {
                    $resultMeta['failed']++;
                }
            }
        }
        $params['query'] = $this->_buildQuery($wheres);
        $params['queryOptions'] = $options;
        $params['updateValues'] = $newValues;


        return $this->_return($resultData, $resultMeta, $params, $this->_queryTag(__FUNCTION__));
    }


    //----------------------------------------------------------------------
    // Delete Queries
    //----------------------------------------------------------------------

    /**
     * @throws QueryException
     * @throws ParameterException
     */
    public function processDeleteAll($wheres, $options = []): Results
    {
        if (isset($wheres['_id'])) {
            $params = [
                'index' => $this->index,
                'id'    => $wheres['_id'],
            ];
            try {
                $response = $this->client->delete($params);
                $response['deleteCount'] = $response['result'] === 'deleted' ? 1 : 0;

                return $this->_return($response['deleteCount'], $response, $params, $this->_queryTag(__FUNCTION__));
            } catch (Exception $e) {
                $this->throwError($e, $params, $this->_queryTag(__FUNCTION__));
            }
        }
        $params = $this->buildParams($this->index, $wheres, $options);
        try {
            $response = $this->client->deleteByQuery($params);
//            $response = $responseObject->asArray();
            $response['deleteCount'] = $response['deleted'] ?? 0;

            return $this->_return($response['deleteCount'], $response, $params, $this->_queryTag(__FUNCTION__));
        } catch (Exception $e) {
            $this->throwError($e, $params, $this->_queryTag(__FUNCTION__));
        }

    }

    public function processScript($id, $script)
    {
//        $params = [
//            'id'    => $id,
//            'index' => $this->index,
//        ];
//        if ($script) {
//            $params['body']['script']['source'] = $script;
//        }
//
//        $response = $this->client->update($params);
//
//        $n = new self($this->index);
//        $find = $n->processFind($id);

//        return $this->_return($find->data, $response, $params, $this->_queryTag(__FUNCTION__));
    }


    //----------------------------------------------------------------------
    // Index administration
    //----------------------------------------------------------------------

    /**
     */
    public function processGetIndices($all): array
    {
        $index = $this->index;
        if ($all) {
            $index = '*';
        }

        return $this->client->indices()->get(['index' => $index]);

    }

    public function processIndexExists($index): bool
    {
        $params = ['index' => $index];

        try {
            return $this->client->indices()->exists($params);
        } catch (Exception $e) {
            return false;
        }

    }


    /**
     * @throws QueryException
     */
    public function processIndexMappings($index): mixed
    {
        $params = ['index' => $index];
        try {
            $response = $this->client->indices()->getMapping($params);
            $result = $this->_return($response, $response, $params, $this->_queryTag(__FUNCTION__));

            return $result->data;
        } catch (Exception $e) {
            $this->throwError($e, $params, $this->_queryTag(__FUNCTION__));
        }
    }

    /**
     * @throws QueryException
     */
    public function processIndexSettings($index): mixed
    {
        $params = ['index' => $index];
        try {
            $response = $this->client->indices()->getSettings($params);
            $result = $this->_return($response, $response, $params, $this->_queryTag(__FUNCTION__));

            return $result->data;

//            return $result->data->asArray();
        } catch (Exception $e) {
            $this->throwError($e, $params, $this->_queryTag(__FUNCTION__));
        }
    }

    /**
     * @throws QueryException
     */
    public function processIndexCreate($settings)
    {
        $params = $this->buildIndexMap($this->index, $settings);
        try {

            $response = $this->client->indices()->create($params);
            $result = $this->_return(true, $response, $params, $this->_queryTag(__FUNCTION__));

            return true;
        } catch (Exception $e) {

            $this->throwError($e, $params, $this->_queryTag(__FUNCTION__));
        }

    }

    /**
     * @throws QueryException
     */
    public function processIndexDelete(): bool
    {
        $params = ['index' => $this->index];
        try {
            $response = $this->client->indices()->delete($params);
            $this->_return(true, $response, $params, $this->_queryTag(__FUNCTION__));

            return true;
        } catch (Exception $e) {
            $this->throwError($e, $params, $this->_queryTag(__FUNCTION__));
        }


    }

    /**
     * @throws QueryException
     */
    public function processIndexModify($settings): bool
    {

        $params = $this->buildIndexMap($this->index, $settings);
        $params['body']['_source']['enabled'] = true;
        $props = $params['body']['mappings']['properties'];
        unset($params['body']['mappings']);
        $params['body']['properties'] = $props;

        try {
            $response = $this->client->indices()->putMapping($params);
            $result = $this->_return(true, $response, $params, $this->_queryTag(__FUNCTION__));

            return true;
        } catch (Exception $e) {
            $this->throwError($e, $params, $this->_queryTag(__FUNCTION__));
        }

    }

    /**
     * @throws QueryException
     */
    public function processReIndex($oldIndex, $newIndex): Results
    {
        $prefix = $this->indexPrefix;
        if ($prefix) {
            $oldIndex = $prefix.'_'.$oldIndex;
            $newIndex = $prefix.'_'.$newIndex;
        }
        $params['body']['source']['index'] = $oldIndex;
        $params['body']['dest']['index'] = $newIndex;
        try {
            $result = $this->client->reindex($params);
//            $result = $response->asArray();
            $resultData = [
                'took'              => $result['took'],
                'total'             => $result['total'],
                'created'           => $result['created'],
                'updated'           => $result['updated'],
                'deleted'           => $result['deleted'],
                'batches'           => $result['batches'],
                'version_conflicts' => $result['version_conflicts'],
                'noops'             => $result['noops'],
                'retries'           => $result['retries'],
            ];

            return $this->_return($resultData, $result, $params, $this->_queryTag(__FUNCTION__));

        } catch (Exception $e) {
            $this->throwError($e, $params, $this->_queryTag(__FUNCTION__));
        }
    }

    /**
     * @throws QueryException
     */
    public function processIndexAnalyzerSettings($settings): bool
    {
        $params = $this->buildAnalyzerSettings($this->index, $settings);
        try {
            $this->client->indices()->close(['index' => $this->index]);
            $response = $this->client->indices()->putSettings($params);
            $result = $this->_return(true, $response, $params, $this->_queryTag(__FUNCTION__));
            $this->client->indices()->open(['index' => $this->index]);

            return true;
        } catch (Exception $e) {
            $this->throwError($e, $params, $this->_queryTag(__FUNCTION__));
        }
    }


    //----------------------------------------------------------------------
    // Aggregates
    //----------------------------------------------------------------------

    public function processMultipleAggregate($functions, $wheres, $options, $column)
    {
        $params = $this->buildParams($this->index, $wheres, $options);
        try {
            $params['body']['aggs'] = ParameterBuilder::multipleAggregations($functions, $column);
            $process = $this->client->search($params);

            return $this->_return($process['aggregations'] ?? [], $process, $params, $this->_queryTag(__FUNCTION__));

        } catch (Exception $e) {

            $this->throwError($e, $params, $this->_queryTag(__FUNCTION__));
        }
    }

    /**
     *  Aggregate entry point
     *
     * @param $function
     * @param $wheres
     * @param $options
     * @param $columns
     *
     * @return mixed
     */
    public function processAggregate($function, $wheres, $options, $columns): Results
    {
        return $this->{'_'.$function.'Aggregate'}($wheres, $options, $columns);
    }

    /**
     * @throws QueryException
     * @throws ParameterException
     */
    public function _countAggregate($wheres, $options, $columns): Results
    {
        $params = $this->buildParams($this->index, $wheres);
        try {
            $process = $this->client->count($params);

            return $this->_return($process['count'] ?? 0, $process, $params, $this->_queryTag(__FUNCTION__));
        } catch (Exception $e) {

            $this->throwError($e, $params, $this->_queryTag(__FUNCTION__));
        }

    }

    /**
     * @throws ParameterException
     * @throws QueryException
     */
    private function _maxAggregate($wheres, $options, $columns): Results
    {
        $params = $this->buildParams($this->index, $wheres, $options);
        if (is_array($columns[0])) {
            $columns = $columns[0];
        }
        try {
            foreach ($columns as $column) {
                $params['body']['aggs']['max_'.$column] = ParameterBuilder::maxAggregation($column);
            }
            $process = $this->client->search($params);

            return $this->_sanitizeAggsResponse($process, $params, $this->_queryTag(__FUNCTION__));
        } catch (Exception $e) {

            $this->throwError($e, $params, $this->_queryTag(__FUNCTION__));
        }
    }

    /**
     * @throws QueryException
     * @throws ParameterException
     */
    private function _minAggregate($wheres, $options, $columns): Results
    {
        $params = $this->buildParams($this->index, $wheres, $options);
        if (is_array($columns[0])) {
            $columns = $columns[0];
        }
        try {
            foreach ($columns as $column) {
                $params['body']['aggs']['min_'.$column] = ParameterBuilder::minAggregation($column);
            }
            $process = $this->client->search($params);

            return $this->_sanitizeAggsResponse($process, $params, $this->_queryTag(__FUNCTION__));
        } catch (Exception $e) {
            $this->throwError($e, $params, $this->_queryTag(__FUNCTION__));
        }
    }

    /**
     * @throws QueryException
     * @throws ParameterException
     */
    private function _sumAggregate($wheres, $options, $columns): Results
    {

        $params = $this->buildParams($this->index, $wheres, $options);
        if (is_array($columns[0])) {
            $columns = $columns[0];
        }
        try {
            foreach ($columns as $column) {
                $params['body']['aggs']['sum_'.$column] = ParameterBuilder::sumAggregation($column);
            }
            $process = $this->client->search($params);

            return $this->_sanitizeAggsResponse($process, $params, $this->_queryTag(__FUNCTION__));
        } catch (Exception $e) {

            $this->throwError($e, $params, $this->_queryTag(__FUNCTION__));
        }

    }

    /**
     * @throws ParameterException
     * @throws QueryException
     */
    private function _avgAggregate($wheres, $options, $columns): Results
    {
        $params = $this->buildParams($this->index, $wheres, $options);
        if (is_array($columns[0])) {
            $columns = $columns[0];
        }
        try {
            foreach ($columns as $column) {
                $params['body']['aggs']['avg_'.$column] = ParameterBuilder::avgAggregation($column);
            }
            $process = $this->client->search($params);

            return $this->_sanitizeAggsResponse($process, $params, $this->_queryTag(__FUNCTION__));
        } catch (Exception $e) {
            $this->throwError($e, $params, $this->_queryTag(__FUNCTION__));
        }
    }

    /**
     * @throws QueryException
     * @throws ParameterException
     */
    private function _matrixAggregate($wheres, $options, $columns): Results
    {
        $params = $this->buildParams($this->index, $wheres, $options);
        try {
            $params['body']['aggs']['statistics'] = ParameterBuilder::matrixAggregation($columns);
            $process = $this->client->search($params);

            return $this->_return($process['aggregations']['statistics'] ?? [], $process, $params, $this->_queryTag(__FUNCTION__));
        } catch (Exception $e) {
            $this->throwError($e, $params, $this->_queryTag(__FUNCTION__));
        }

    }

    /**
     * @throws QueryException
     */
    public function parseRequiredKeywordMapping($field)
    {
        $mappings = $this->processIndexMappings($this->index);
        $map = reset($mappings);
        if (!empty($map['mappings']['properties'][$field])) {
            $fieldMap = $map['mappings']['properties'][$field];
            if (!empty($fieldMap['type']) && $fieldMap['type'] === 'keyword') {
                //primary Map is field. Use as is
                return $field;
            }
            if (!empty($fieldMap['fields']['keyword'])) {
                return $field.'.keyword';
            }
        }

        return false;

    }

    //----------------------------------------------------------------------
    // Distinct Aggregates
    //----------------------------------------------------------------------

    public function processDistinctAggregate($function, $wheres, $options, $columns): Results
    {
        return $this->{'_'.$function.'DistinctAggregate'}($wheres, $options, $columns);
    }

    /**
     * @throws ParameterException
     * @throws QueryException
     */
    private function _countDistinctAggregate($wheres, $options, $columns): Results
    {
        $params = $this->buildParams($this->index, $wheres);
        try {
            $process = $this->processDistinct($wheres, $options, $columns);
            $count = count($process->data);

            return $this->_return($count, $process->getMetaData(), $params, $this->_queryTag(__FUNCTION__));
        } catch (Exception $e) {

            $this->throwError($e, $params, $this->_queryTag(__FUNCTION__));
        }

    }


    /**
     * @throws ParameterException
     * @throws QueryException
     */
    private function _minDistinctAggregate($wheres, $options, $columns): Results
    {
        $params = $this->buildParams($this->index, $wheres);
        try {
            $process = $this->processDistinct($wheres, $options, $columns);

            $min = 0;
            $hasBeenSet = false;
            if (!empty($process->data)) {
                foreach ($process->data as $datum) {
                    if (!empty($datum[$columns[0]]) && is_numeric($datum[$columns[0]])) {
                        if (!$hasBeenSet) {
                            $min = $datum[$columns[0]];
                            $hasBeenSet = true;
                        } else {
                            $min = min($min, $datum[$columns[0]]);
                        }

                    }
                }
            }

            return $this->_return($min, $process->getMetaData(), $params, $this->_queryTag(__FUNCTION__));
        } catch (Exception $e) {

            $this->throwError($e, $params, $this->_queryTag(__FUNCTION__));
        }

    }

    /**
     * @throws ParameterException
     * @throws QueryException
     */
    private function _maxDistinctAggregate($wheres, $options, $columns): Results
    {
        $params = $this->buildParams($this->index, $wheres);
        try {
            $process = $this->processDistinct($wheres, $options, $columns);

            $max = 0;
            if (!empty($process->data)) {
                foreach ($process->data as $datum) {
                    if (!empty($datum[$columns[0]]) && is_numeric($datum[$columns[0]])) {
                        $max = max($max, $datum[$columns[0]]);
                    }
                }
            }


            return $this->_return($max, $process->getMetaData(), $params, $this->_queryTag(__FUNCTION__));
        } catch (Exception $e) {

            $this->throwError($e, $params, $this->_queryTag(__FUNCTION__));
        }

    }


    /**
     * @throws ParameterException
     * @throws QueryException
     */
    private function _sumDistinctAggregate($wheres, $options, $columns): Results
    {
        $params = $this->buildParams($this->index, $wheres);
        try {
            $process = $this->processDistinct($wheres, $options, $columns);
            $sum = 0;
            if (!empty($process->data)) {
                foreach ($process->data as $datum) {
                    if (!empty($datum[$columns[0]]) && is_numeric($datum[$columns[0]])) {
                        $sum += $datum[$columns[0]];
                    }
                }
            }

            return $this->_return($sum, $process->getMetaData(), $params, $this->_queryTag(__FUNCTION__));
        } catch (Exception $e) {

            $this->throwError($e, $params, $this->_queryTag(__FUNCTION__));
        }

    }

    /**
     * @throws ParameterException
     * @throws QueryException
     */
    private function _avgDistinctAggregate($wheres, $options, $columns)
    {
        $params = $this->buildParams($this->index, $wheres);
        try {
            $process = $this->processDistinct($wheres, $options, $columns);
            $sum = 0;
            $count = 0;
            $avg = 0;
            if (!empty($process->data)) {
                foreach ($process->data as $datum) {
                    if (!empty($datum[$columns[0]]) && is_numeric($datum[$columns[0]])) {
                        $count++;
                        $sum += $datum[$columns[0]];
                    }
                }
            }
            if ($count > 0) {
                $avg = $sum / $count;
            }


            return $this->_return($avg, $process->getMetaData(), $params, $this->_queryTag(__FUNCTION__));
        } catch (Exception $e) {

            $this->throwError($e, $params, $this->_queryTag(__FUNCTION__));
        }

    }

    /**
     * @throws QueryException
     */
    private function _matrixDistinctAggregate($wheres, $options, $columns): Results
    {
        $this->throwError(new Exception('Matrix distinct aggregate not supported', 500), [], $this->_queryTag(__FUNCTION__));
    }

    //======================================================================
    // Private & Sanitization methods
    //======================================================================


    private function _queryTag($function)
    {
        return str_replace('process', '', $function);
    }

    private function _sanitizeSearchResponse($response, $params, $queryTag)
    {

        $meta['took'] = $response['took'] ?? 0;
        $meta['timed_out'] = $response['timed_out'];
        $meta['total'] = $response['hits']['total']['value'] ?? 0;
        $meta['max_score'] = $response['hits']['max_score'] ?? 0;
        $meta['shards'] = $response['_shards'] ?? [];
        $data = [];
        if (!empty($response['hits']['hits'])) {
            foreach ($response['hits']['hits'] as $hit) {
                $datum = [];
                $datum['_index'] = $hit['_index'];
                $datum['_id'] = $hit['_id'];
                if (!empty($hit['_source'])) {

                    foreach ($hit['_source'] as $key => $value) {
                        $datum[$key] = $value;
                    }

                }
                if (!empty($hit['inner_hits'])) {
                    foreach ($hit['inner_hits'] as $innerKey => $innerHit) {
                        $datum[$innerKey] = $this->_filterInnerHits($innerHit);
                    }
                }

                //Meta data
                if (!empty($hit['highlight'])) {
                    $datum['_meta']['highlights'] = $this->_sanitizeHighlights($hit['highlight']);
                }

                $datum['_meta']['_index'] = $hit['_index'];
                $datum['_meta']['_id'] = $hit['_id'];
                if (!empty($hit['_score'])) {
                    $datum['_meta']['_score'] = $hit['_score'];
                }
                $datum['_meta']['_query'] = $meta;

                $data[] = $datum;
            }
        }

        return $this->_return($data, $meta, $params, $queryTag);
    }

    private function _sanitizeHighlights($highlights)
    {
        //remove keyword results
        foreach ($highlights as $field => $vals) {
            if (str_contains($field, '.keyword')) {
                $cleanField = str_replace('.keyword', '', $field);
                if (isset($highlights[$cleanField])) {
                    unset($highlights[$field]);
                } else {
                    $highlights[$cleanField] = $vals;
                }
            }
        }

        return $highlights;
    }

    public function _sanitizeAggsResponse($response, $params, $queryTag)
    {
        $meta['timed_out'] = $response['timed_out'];
        $meta['total'] = $response['hits']['total']['value'] ?? 0;
        $meta['max_score'] = $response['hits']['max_score'] ?? 0;
        $meta['sorts'] = [];

        $aggs = $response['aggregations'];
        $data = (count($aggs) === 1)
            ? reset($aggs)['value'] ?? 0
            : array_map(fn($value) => $value['value'] ?? 0, $aggs);

        return $this->_return($data, $meta, $params, $queryTag);
    }

    private function _sanitizeRawAggsResponse($response, $params, $queryTag)
    {
        $meta['timed_out'] = $response['timed_out'];
        $meta['total'] = $response['hits']['total']['value'] ?? 0;
        $meta['max_score'] = $response['hits']['max_score'] ?? 0;
        $meta['sorts'] = [];
        $data = [];
        if (!empty($response['aggregations'])) {
            foreach ($response['aggregations'] as $key => $values) {
                $data[$key] = $this->_formatAggs($key, $values)[$key];
            }
        }

        return $this->_return($data, $meta, $params, $queryTag);
    }

    private function _formatAggs($key, $values)
    {
        $data[$key] = [];
        $aggTypes = ['buckets', 'values'];

        foreach ($values as $subKey => $value) {
            if (in_array($subKey, $aggTypes)) {
                $data[$key] = $this->_formatAggs($subKey, $value)[$subKey];
            } elseif (is_array($value)) {
                $data[$key][$subKey] = $this->_formatAggs($subKey, $value)[$subKey];
            } else {
                $data[$key][$subKey] = $value;
            }

        }

        return $data;

    }

    private function _filterInnerHits($innerHit)
    {
        $hits = [];
        foreach ($innerHit['hits']['hits'] as $inner) {
            $innerDatum = [];
            if (!empty($inner['_source'])) {
                foreach ($inner['_source'] as $innerSourceKey => $innerSourceValue) {
                    $innerDatum[$innerSourceKey] = $innerSourceValue;
                }
            }
            $hits[] = $innerDatum;
        }

        return $hits;
    }

    private function _sanitizePitSearchResponse($response, $params, $queryTag)
    {

        $meta['timed_out'] = $response['timed_out'];
        $meta['total'] = $response['hits']['total']['value'] ?? 0;
        $meta['max_score'] = $response['hits']['max_score'] ?? 0;
        $meta['last_sort'] = null;
        $data = [];
        if (!empty($response['hits']['hits'])) {
            foreach ($response['hits']['hits'] as $hit) {
                $datum = [];
                $datum['_index'] = $hit['_index'];
                $datum['_id'] = $hit['_id'];
                if (!empty($hit['_source'])) {
                    foreach ($hit['_source'] as $key => $value) {
                        $datum[$key] = $value;
                    }
                }
                if (!empty($hit['sort'][0])) {
                    $meta['last_sort'] = $hit['sort'];
                }
                $data[] = $datum;

            }
        }

        return $this->_return($data, $meta, $params, $queryTag);
    }


    private function _parseSort($sort, $sortParams)
    {
        $sortValues = [];
        foreach ($sort as $key => $value) {
            $sortValues[array_key_first($sortParams[$key])] = $value;
        }

        return $sortValues;
    }

    private function _sanitizeDistinctResponse($response, $columns, $includeDocCount)
    {
        $keys = [];
        foreach ($columns as $column) {
            $keys[] = 'by_'.$column;
        }

        return $this->processBuckets($columns, $keys, $response, 0, $includeDocCount);

    }

    private function processBuckets($columns, $keys, $response, $index, $includeDocCount, $currentData = [])
    {
        $data = [];
        if (!empty($response[$keys[$index]]['buckets'])) {
            foreach ($response[$keys[$index]]['buckets'] as $res) {

                $datum = $currentData;

                $col = $columns[$index];
                if (str_contains($col, '.keyword')) {
                    $col = str_replace('.keyword', '', $col);
                }

                $datum[$col] = $res['key'];

                if ($includeDocCount) {
                    $datum[$col.'_count'] = $res['doc_count'];
                }

                if (isset($columns[$index + 1])) {
                    $nestedData = $this->processBuckets($columns, $keys, $res, $index + 1, $includeDocCount, $datum);

                    if (!empty($nestedData)) {
                        $data = array_merge($data, $nestedData);
                    } else {
                        $data[] = $datum;
                    }
                } else {
                    $data[] = $datum;
                }
            }
        }

        return $data;
    }

    private function _return($data, $meta, $params, $queryTag): Results
    {
        if (is_object($meta)) {
            $metaAsArray = [];
            if (method_exists($meta, 'asArray')) {
                $metaAsArray = $meta->asArray();
            }
            $results = new Results($data, $metaAsArray, $params, $queryTag);
        } else {
            $results = new Results($data, $meta, $params, $queryTag);
        }


        return $results;
    }


    /**
     * @throws QueryException
     */
    private function throwError(Exception $exception, $params, $queryTag): QueryException
    {
        $previous = get_class($exception);
        $errorMsg = $exception->getMessage();
        $errorCode = $exception->getCode();
        $queryTag = str_replace('_', '', $queryTag);
        $this->connection->rebuildConnection();
        $error = new Results([], [], $params, $queryTag);
        $error->setError($errorMsg, $errorCode);

        $meta = $error->getMetaData();
        $details = [
            'error'     => $meta['error']['msg'],
            'details'   => $meta['error']['data'],
            'code'      => $errorCode,
            'exception' => $previous,
            'query'     => $queryTag,
            'params'    => $params,
            'original'  => $errorMsg,
        ];
        if ($this->errorLogger) {
            $this->_logQuery($error, $details);
        }
        // For details catch $exception then $exception->getDetails()
        throw new QueryException($meta['error']['msg'], $errorCode, new $previous, $details);
    }

    private function _logQuery(Results $results, $details)
    {
        $body = $results->getLogFormattedMetaData();
        if ($details) {
            $body['details'] = (array)$details;
        }
        $params = [
            'index' => $this->errorLogger,
            'body'  => $body,
        ];
        try {
            $this->client->index($params);
        } catch (Exception $e) {
            //ignore if problem writing query log
        }
    }

}
