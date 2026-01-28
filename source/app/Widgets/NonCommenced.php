<?php

namespace App\Widgets;

use App\Helpers\Helper;
use App\Models\User;
use Arrilot\Widgets\AbstractWidget;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class NonCommenced extends AbstractWidget
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
        $count = (new User())->newQuery();
        $count = $count->whereHas('roles', function ($query) {
            $query->where('roles.name', '=', 'Student');
        })->whereHas('detail', function ($query) {
            $query->whereNull('user_details.onboard_at');
        })->where('is_active', '=', 1);
        if (auth()->user()->isLeader()) {
            $count = $count->isRelatedLeader();
        }
        $count = $count->count();

        $query = (new User())->newQuery();

        $query = $query->select(DB::raw('DATE(DATE_ADD(created_at, INTERVAL '.$timeZoneOffset.' HOUR)) as "date"'), DB::raw('count(id) as "total"'))
            ->with(['detail', 'roles', 'leaders'])
            ->whereHas('roles', function ($query) {
                $query->where('roles.name', '=', 'Student');
            })->whereHas('detail', function ($query) {
                $query->whereNull('user_details.onboard_at');
            })->where('is_active', '=', 1)
            ->orderBy('date')
            ->groupBy('date')
            ->having('date', '>', Carbon::now()->subMonths(3)->format('Y-m').'-1');

        if (auth()->user()->isLeader()) {
            $query = $query->isRelatedLeader();
        }
        $data = $query->get();

        $dataset = $data->mapWithKeys(function ($item, $key) {
            return [
                $key => [
                    'x' => $item['date'],
                    'y' => $item['total'],
                ],
            ];
        })->toJson();

        return view('widgets.non_commenced', [
            'config' => $this->config,
            'data' => [
                'count' => $count,
                'dataset' => $dataset,
            ],
        ]);
    }

    public function placeholder()
    {
        return 'Loading Disengaged Students...';
    }
}
