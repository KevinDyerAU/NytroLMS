<?php

namespace App\Widgets;

use App\Models\QuizAttempt;
use Arrilot\Widgets\AbstractWidget;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class PendingAssessments extends AbstractWidget
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
        $count = $count->latestAttemptSubmittedOnly()->onlyPending()->count();

        $query = (new QuizAttempt())->newQuery();

        $query = $query->select(DB::raw('DATE(created_at) as "date"'), DB::raw('count(id) as "total"'))
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
        $data = $query->latestAttemptSubmittedOnly()->onlyPending()->get();

        $dataset = $data->mapWithKeys(function ($item, $key) {
            return [
                ''.$key => [
                    'x' => $item['date'],
                    'y' => $item['total'],
                ],
            ];
        })->toJson();

        return view('widgets.pending_assessments', [
            'config' => $this->config,
            'data' => [
                'count' => $count,
                'dataset' => $dataset,
            ],
        ]);
    }

    public function placeholder()
    {
        return 'Loading Pending Assessments...';
    }
}
