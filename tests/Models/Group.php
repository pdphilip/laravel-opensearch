<?php

declare(strict_types=1);

namespace PDPhilip\OpenSearch\Tests\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use PDPhilip\OpenSearch\Eloquent\Model;

/** @property Carbon $created_at */
class Group extends Model
{
    protected $connection = 'elasticsearch';

    protected $table = 'groups';

    protected static $unguarded = true;

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'users', 'groups', 'users', 'id', 'id', 'users');
    }
}
