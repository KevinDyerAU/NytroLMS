<?php

namespace App\Repository\Eloquent;

use App\Models\StudentActivity;
use App\Repository\Contracts\StudentActivityRepositoryInterface;

class StudentActivityRepository extends BaseRepository implements StudentActivityRepositoryInterface
{
    /**
     * @var Model
     */
    protected $model;

    /**
     * BaseRepository constructor.
     *
     * @param Model $model
     */
    public function __construct(StudentActivity $model)
    {
        $this->model = $model;
    }
}
