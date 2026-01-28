<?php

namespace App\Widgets;

use App\Helpers\Helper;
use App\Models\Competency as CompetencyModel;
use Arrilot\Widgets\AbstractWidget;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class Competency extends AbstractWidget
{
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
        $count = (new CompetencyModel())->newQuery();

        if (auth()->user()->isTrainer()) {
            $count = $count->whereHas('user', function ($query) {
                return $query->whereHas('trainers', function (Builder $query) {
                    $query->where('id', '=', auth()->user()->id);
                });
            });
        }
        $count = $count->whereHas('lesson', function ($query) {
            $query->where('title', 'not like', 'Study Tips%');
        });

        $count = $count->where(function ($query) {  // Use a closure to access variables in outer scope
            $query->whereDoesntHave('userDetails', function ($query) {
                $query->whereDate('last_logged_in', '<', '2024-01-01')
                    ->where('competencies.is_competent', '!=', 1);
            })
                ->whereDoesntHave('studentCourseEnrolment', function ($query) {
                    $query->where('competencies.is_competent', '!=', 1)
                        ->where('cert_issued', '=', 1);
                });
        })->distinct();

        $count = $count
//            ->whereNotNull( 'evidence_id' )
//            ->where( 'is_competent', '!=', 1 )
            ->count();

        $query = (new CompetencyModel())->newQuery();
        $query = $query->select(DB::raw('DATE(DATE_ADD(created_at, INTERVAL '.$timeZoneOffset.' HOUR)) as "date"'), DB::raw('count(id) as "total"'));

        if (auth()->user()->isTrainer()) {
            $query = $query->whereHas('user', function ($query) {
                return $query->whereHas('trainers', function (Builder $query) {
                    $query->where('id', '=', auth()->user()->id);
                });
            });
        }

        $query = $query->whereHas('lesson', function ($query) {
            $query->where('title', 'not like', 'Study Tips%');
        });

        $query = $query->where(function ($query) {  // Use a closure to access variables in outer scope
            $query->whereDoesntHave('userDetails', function ($query) {
                $query->whereDate('last_logged_in', '<', '2024-01-01')
                    ->where('competencies.is_competent', '!=', 1);
            })
                ->whereDoesntHave('studentCourseEnrolment', function ($query) {
                    $query->where('competencies.is_competent', '!=', 1)
                        ->where('cert_issued', '=', 1);
                });
        })->distinct();

        $query = $query
//            ->whereNotNull( 'evidence_id' )
//            ->where( 'is_competent', '!=', 1 )
            ->orderBy('date')
            ->groupBy('date')
            ->having('date', '>', Carbon::now()->subMonths(3)->format('Y-m').'-1');
        $data = $query->get();

        $dataset = $data->mapWithKeys(function ($item, $key) {
            return [
                $key => [
                    'x' => $item['date'],
                    'y' => $item['total'],
                ],
            ];
        })->toJson();

        return view('widgets.competency', [
            'config' => $this->config,
            'data' => [
                'count' => $count,
                'dataset' => $dataset,
            ],
        ]);
    }

    public function placeholder()
    {
        return 'Loading Students Competencies...';
    }
}
