<?php

namespace App\Repository\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface EloquentRepositoryInterface
{
    /**
     * Get all models.
     */
    public function all(array $columns = ['*'], array $relations = []): Collection;

    /**
     * Get all trashed models.
     */
    public function allTrashed(): Collection;

    /**
     * Find model by id.
     */
    public function findById(
        int $modelId,
        array $columns = ['*'],
        array $relations = [],
        array $appends = []
    ): ?Model;

    public function findBy($field, $value, array $columns = ['*'], array $relations = [], array $appends = []);

    public function findWhere(array $where, array $columns = ['*'], array $relations = [], array $appends = []);

    /**
     * Find trashed model by id.
     */
    public function findTrashedById(int $modelId): ?Model;

    /**
     * Find only trashed model by id.
     */
    public function findOnlyTrashedById(int $modelId): ?Model;

    /**
     * Create a model.
     */
    public function create(array $payload): ?Model;

    /**
     * Update existing model.
     */
    public function update(int $modelId, array $payload): bool;

    /**
     * Delete model by id.
     */
    public function deleteById(int $modelId): bool;

    /**
     * Restore model by id.
     */
    public function restoreById(int $modelId): bool;

    /**
     * Permanently delete model by id.
     */
    public function permanentlyDeleteById(int $modelId): bool;
}
