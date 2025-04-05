<?php

namespace App\Shared\Interfaces;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;

interface BaseRepositoryInterface
{
    /**
     * Get all records
     *
     * @param array $columns
     * @return Collection
     */
    public function all(array $columns = ['*']): Collection;

    /**
     * Find record by id
     *
     * @param int $id
     * @param array $columns
     * @return Model|null
     */
    public function find(int $id, array $columns = ['*']): ?Model;

    /**
     * Find record by id or fail
     *
     * @param int $id
     * @param array $columns
     * @return Model
     */
    public function findOrFail(int $id, array $columns = ['*']): Model;

    /**
     * Create new record
     *
     * @param array $data
     * @return Model
     */
    public function create(array $data): Model;

    /**
     * Update record
     *
     * @param int $id
     * @param array $data
     * @return Model
     */
    public function update(int $id, array $data): Model;

    /**
     * Delete record
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool;

    /**
     * Get model instance
     *
     * @return Model
     */
    public function getModel(): Model;

    /**
     * Set model instance
     *
     * @param Model $model
     * @return $this
     */
    public function setModel(Model $model): self;
}

