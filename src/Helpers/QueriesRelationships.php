<?php

declare(strict_types=1);

namespace PDPhilip\OpenSearch\Helpers;

use Closure;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use PDPhilip\OpenSearch\Eloquent\Model;
use PDPhilip\OpenSearch\Relations\MorphToMany;

trait QueriesRelationships
{
    /**
     * Add a relationship count / exists condition to the query.
     *
     * @param  Relation|string  $relation
     * @param  string  $operator
     * @param  int  $count
     * @param  string  $boolean
     *
     * @throws Exception
     */
    public function has(
        $relation,
        $operator = '>=',
        $count = 1,
        $boolean = 'and',
        ?Closure $callback = null
    ): Builder|static {
        if (is_string($relation)) {
            if (str_contains($relation, '.')) {
                // @phpstan-ignore-next-line
                return $this->hasNested($relation, $operator, $count, $boolean, $callback);
            }

            $relation = $this->getRelationWithoutConstraints($relation);
        }

        // If this is a hybrid relation then we can not use a normal whereExists() query that relies on a subquery
        // We need to use a `whereIn` query
        // @phpstan-ignore-next-line
        if (Model::isOpenSearchModel($this->getModel()) || $this->isAcrossConnections($relation)) {
            return $this->addHybridHas($relation, $operator, $count, $boolean, $callback);
        }

        // If we only need to check for the existence of the relation, then we can optimize
        // the subquery to only run a "where exists" clause instead of this full "count"
        // clause. This will make these queries run much faster compared with a count.
        // @phpstan-ignore-next-line
        $method = $this->canUseExistsForExistenceCheck($operator, $count) ? 'getRelationExistenceQuery' : 'getRelationExistenceCountQuery';

        $hasQuery = $relation->{$method}($relation->getRelated()->newQuery(), $this);

        // Next we will call any given callback as an "anonymous" scope so they can get the
        // proper logical grouping of the where clauses if needed by this Eloquent query
        // builder. Then, we will be ready to finalize and return this query instance.
        if ($callback) {
            $hasQuery->callScope($callback);
        }

        return $this->addHasWhere($hasQuery, $relation, $operator, $count, $boolean);
    }

    protected function isAcrossConnections(Relation $relation): bool
    {
        return $relation->getParent()->getConnectionName() !== $relation->getRelated()->getConnectionName();
    }

    /**
     * Compare across databases.
     *
     *
     * @throws Exception
     */
    public function addHybridHas(
        Relation $relation,
        string $operator = '>=',
        int $count = 1,
        string $boolean = 'and',
        ?Closure $callback = null
    ): mixed {
        $hasQuery = $relation->getQuery();
        if ($callback) {
            $hasQuery->callScope($callback);
        }

        // If the operator is <, <= or !=, we will use whereNotIn.
        $not = in_array($operator, ['<', '<=', '!=']);
        // If we are comparing to 0, we need an additional $not flip.
        if ($count == 0) {
            $not = ! $not;
        }

        $relations = match (true) {
            $relation instanceof MorphToMany => $relation->getInverse() ?
                $this->handleMorphedByMany($hasQuery, $relation) :
                $this->handleMorphToMany($hasQuery, $relation),
            default => $hasQuery->pluck($this->getHasCompareKey($relation))
        };

        $relatedIds = $this->getConstrainedRelatedIds($relations, $operator, $count);

        return $this->whereIn($this->getRelatedConstraintKey($relation), $relatedIds, $boolean, $not);
    }

    /**
     * @param  Builder  $hasQuery
     * @param  Relation  $relation
     * @return Collection
     */
    private function handleMorphedByMany($hasQuery, $relation)
    {
        $hasQuery->whereNotNull($relation->getForeignPivotKeyName());

        return $hasQuery->pluck($relation->getForeignPivotKeyName())->flatten(1);
    }

    /**
     * @param  Builder  $hasQuery
     * @param  Relation  $relation
     * @return Collection
     */
    private function handleMorphToMany($hasQuery, $relation)
    {
        // First we select the parent models that have a relation to our related model,
        // Then extracts related model's ids from the pivot column
        $hasQuery->where($relation->getTable().'.'.$relation->getMorphType(), $relation->getParent()::class);
        $relations = $hasQuery->pluck($relation->getTable());
        $relations = $relation->extractIds($relations->flatten(1)->toArray(), $relation->getForeignPivotKeyName());

        return collect($relations);
    }

    protected function getHasCompareKey(Relation $relation): string
    {
        if (method_exists($relation, 'getHasCompareKey')) {
            return $relation->getHasCompareKey();
        }

        return $relation instanceof HasOneOrMany ? $relation->getForeignKeyName() : $relation->getOwnerKeyName();
    }

    protected function getConstrainedRelatedIds($relations, $operator, $count): array
    {
        $relationCount = array_count_values(array_map(function ($id) {
            return (string) $id; // Convert Back ObjectIds to Strings
        }, is_array($relations) ? $relations : $relations->flatten()->toArray()));
        // Remove unwanted related objects based on the operator and count.
        $relationCount = array_filter($relationCount, function ($counted) use ($count, $operator) {
            // If we are comparing to 0, we always need all results.
            if ($count == 0) {
                return true;
            }
            switch ($operator) {
                case '>=':
                case '<':
                    return $counted >= $count;
                case '>':
                case '<=':
                    return $counted > $count;
                case '=':
                case '!=':
                    return $counted == $count;
            }
        });

        // All related ids.
        return array_keys($relationCount);
    }

    /**
     * Returns key we are constraining this parent model's query with.
     *
     *
     *
     * @throws Exception
     */
    protected function getRelatedConstraintKey(Relation $relation): string
    {
        if ($relation instanceof HasOneOrMany) {
            return $relation->getLocalKeyName();
        }

        if ($relation instanceof BelongsTo) {
            return $relation->getForeignKeyName();
        }

        if ($relation instanceof BelongsToMany && ! $this->isAcrossConnections($relation)) {
            return $this->model->getKeyName();
        }

        throw new Exception(class_basename($relation).' is not supported for hybrid query constraints.');
    }
}
