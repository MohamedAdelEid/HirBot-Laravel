<?php

namespace App\Shared\Repositories;

use App\Shared\Interfaces\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;

class BaseRepository implements BaseRepositoryInterface
{
    /**
     * @var Model
     */
    protected $model;

    /**
     * BaseRepository constructor.
     *
     * @param Model|null $model
     */
    public function __construct(Model $model = null)
    {
        if ($model) {
            $this->model = $model;
        }
    }

    /**
     * @inheritDoc
     */
    public function all(array $columns = ['*']): Collection
    {
        return $this->model->all($columns);
    }

    /**
     * @inheritDoc
     */
    public function find(int $id, array $columns = ['*']): ?Model
    {
        return $this->model->find($id, $columns);
    }

    /**
     * @inheritDoc
     */
    public function findOrFail(int $id, array $columns = ['*']): Model
    {
        return $this->model->findOrFail($id, $columns);
    }

    /**
     * @inheritDoc
     */
    public function create(array $data): Model
    {
        return $this->model->create($data);
    }

    /**
     * @inheritDoc
     */
    public function update(int $id, array $data): Model
    {
        $record = $this->findOrFail($id);
        $record->update($data);
        return $record->fresh();
    }

    /**
     * @inheritDoc
     */
    public function delete(int $id): bool
    {
        return $this->findOrFail($id)->delete();
    }

    /**
     * @inheritDoc
     */
    public function getModel(): Model
    {
        return $this->model;
    }

    /**
     * @inheritDoc
     */
    public function setModel(Model $model): self
    {
        $this->model = $model;
        return $this;
    }

    /**
     * Find by custom field
     *
     * @param string $field
     * @param mixed $value
     * @return Model|null
     */
    public function findBy(string $field, $value): ?Model
    {
        return $this->model->where($field, $value)->first();
    }

    /**
     * Find all by custom field
     *
     * @param string $field
     * @param mixed $value
     * @return Collection
     */
    public function findAllBy(string $field, $value): Collection
    {
        return $this->model->where($field, $value)->get();
    }
}

