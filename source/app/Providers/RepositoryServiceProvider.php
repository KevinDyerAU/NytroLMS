<?php

namespace App\Providers;

use App\Repository\Contracts\EloquentRepositoryInterface;
use App\Repository\Contracts\StudentActivityRepositoryInterface;
use App\Repository\Eloquent\BaseRepository;
use App\Repository\Eloquent\StudentActivityRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(EloquentRepositoryInterface::class, BaseRepository::class);
        $this->app->bind(StudentActivityRepositoryInterface::class, StudentActivityRepository::class);
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
