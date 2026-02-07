<?php

namespace App\Widgets;

use App\Helpers\Helper;
use App\Models\QuizAttempt;
use Arrilot\Widgets\AbstractWidget;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class TotalAssessments extends AbstractWidget
{
    /**
     * The number of seconds before each reload.
     *
     * @var int|float
     */
    //    public $reloadTimeout = 30;
    /**
     * The configuration array.
     *
     * @var array
     */
    protected $config = [];

    /**
     * Treat this method as a controller action.
     * Return view() or other content to display.
     */
    public function run()
    {
        $timeZoneOffset = Helper::getTimeZoneOffset();
        $count = (new QuizAttempt())->newQuery();
        if (auth()->user()->isLeader()) {
            $count = $count->whereHas('user', function ($iquery) {
                $iquery->whereHas('leaders', function (Builder $aquery) {
                    $aquery->where('id', '=', auth()->user()->id);
                });
            });
        }
        if (auth()->user()->isTrainer()) {
            $count = $count->whereHas('user', function ($iquery) {
                $iquery->whereHas('trainers', function (Builder $aquery) {
                    $aquery->where('id', '=', auth()->user()->id);
                });
            });
        }
        $count = $count
            ->where('quiz_attempts.system_result', '!=', 'EVALUATED')
            ->where('quiz_attempts.system_result', '!=', 'MARKED')
            ->where('system_result', '!=', 'INPROGRESS')
            ->latestAttempt();
        $count = $count->count();

        $query = (new QuizAttempt())->newQuery();

        $query = $query->select(DB::raw('DATE(DATE_ADD(created_at, INTERVAL '.$timeZoneOffset.' HOUR)) as "date"'), DB::raw('count(id) as "total"'))
            ->orderBy('date')
            ->groupBy('date')
            ->having('date', '>', Carbon::now()->subMonths(3)->format('Y-m').'-1');

        if (auth()->user()->isLeader()) {
            $query = $query->whereHas('user', function ($iquery) {
                $iquery->whereHas('leaders', function (Builder $aquery) {
                    $aquery->where('id', '=', auth()->user()->id);
                });
            });
        }
        if (auth()->user()->isTrainer()) {
            $query = $query->whereHas('user', function ($iquery) {
                $iquery->whereHas('trainers', function (Builder $aquery) {
                    $aquery->where('id', '=', auth()->user()->id);
                });
            });
        }

        $data = $query
            ->where('quiz_attempts.system_result', '!=', 'EVALUATED')
            ->where('quiz_attempts.system_result', '!=', 'MARKED')
            ->where('system_result', '!=', 'INPROGRESS')
            ->latestAttempt()->get();

        $dataset = $data->mapWithKeys(function ($item, $key) {
            return [
                $key => [
                    'x' => $item['date'],
                    'y' => $item['total'],
                ],
            ];
        })->toJson();

        return view('widgets.total_assessments', [
            'config' => $this->config,
            'data' => [
                'count' => $count,
                'dataset' => $dataset,
            ],
        ]);
    }

    public function placeholder()
    {
        return 'Loading Total Assessments...';
    }
}
