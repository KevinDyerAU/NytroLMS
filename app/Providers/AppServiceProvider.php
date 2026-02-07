<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\LazyLoadingViolationException;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Opcodes\LogViewer\Facades\LogViewer;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->register(RepositoryServiceProvider::class);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->lazyLoadingSolution();
        $this->dbListner();
        $this->loadSettings();

        Validator::excludeUnvalidatedArrayKeys();
        Paginator::useBootstrap();

        LogViewer::auth(function ($request) {
            // return true to allow viewing the Log Viewer.
            // auth()->user()->email === 'mohsin@inceptionsol.com' || auth()->user()->username === 'mohsina'
            return $request->user()
                && (
                    in_array($request->user()->email, ['mohsin@inceptionsol.com'])
                    || in_array($request->user()->username, ['mohsina'])
                );
        });
    }

    /**
     * Load settings from the database and share them with views.
     */
    public function loadSettings(): void
    {
        if (Schema::hasTable('settings')) {
            View::composer('*', function ($view) {
                // Cache site-wide settings (user_id is null)
                $siteSettings = Cache::remember('settings.site.all', now()->addHours(24), function () {
                    return Setting::whereNull('user_id')
                        ->where('key', '!=', '_token')
                        ->get()
                        ->map(function ($setting) {
                            $value = is_string($setting->value) && json_validate($setting->value)
                                ? json_decode($setting->value, true)
                                : $setting->value;

                            return [$setting->key => $value];
                        })->collapse()->all();
                });

                // Ensure config is set for cached settings
                foreach ($siteSettings as $key => $value) {
                    Config::set('settings.site.'.$key, $value);
                }

                // Cache user-specific settings if authenticated
                $userSettings = [];
                if (Auth::check()) {
                    $userId = Auth::id();
                    $userSettings = Cache::remember("user_settings_{$userId}", now()->addHours(24), function () use ($userId) {
                        return Setting::where('user_id', $userId)
                            ->where('key', '!=', '_token')
                            ->get()
                            ->pluck('value', 'key')
                            ->map(function ($item, $key) {
                                if (is_string($item) && json_validate($item)) {
                                    return json_decode($item, true);
                                }

                                return $item;
                            })->all();
                    });
                }

                // Share both settings with views separately
                $view->with('settings', $siteSettings);
                $view->with('userSettings', $userSettings);
            });
        }
    }

    /**
     * Listen to database queries and log them.
     */
    public function dbListner(): void
    {
        if (Auth::check()) {
            DB::listen(
                function ($query) {
                    if (Str::startsWith(strtolower($query->sql), 'update')
                        || Str::startsWith(strtolower($query->sql), 'insert')
                        || Str::startsWith(strtolower($query->sql), 'delete')) {
                        Log::info(
                            'DB QUERY',
                            [
                            'user_id' => auth()->user()->id,
                            'query' => $query->sql,
                            'bindings' => $query->bindings,
                            'time' => $query->time,
                    ]
                        );
                    }
                }
            );
        }
    }

    /**
     * Prevent lazy loading in production and report violations.
     */
    public function lazyLoadingSolution(): void
    {
        Model::preventLazyLoading(!app()->isProduction());
        Model::handleLazyLoadingViolationUsing(function (Model $model, string $relation): void {
            // retrieve our configured lottery odds...
            $lottery = $this->app['config']->get('logging.lazy_loading_reporting_lottery');

            // determine if this particular lazy loading violation "wins" the lottery...
            if (random_int(1, $lottery[1]) <= $lottery[0]) {
                // ding, ding! We have a winner. silently report to Sentry...
                $exception = new LazyLoadingViolationException($model, $relation);

                // throw, rather than report, on "local"...
                $this->app['config']->get('app.env') === 'local'
                    ? throw $exception
                    : logger($exception);
            }

            // Add a header to the response. It's crude but it works.
            if (!app()->runningInConsole()) {
                header('Lazy-Loading-Violation: 1');
            }
        });
    }
}
