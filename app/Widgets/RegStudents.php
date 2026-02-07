<?php

namespace App\Widgets;

use App\Helpers\Helper;
use App\Models\User;
use Arrilot\Widgets\AbstractWidget;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RegStudents extends AbstractWidget
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
        $count = User::onlyStudents()->count();
        $data = User::onlyStudents()->select(DB::raw('DATE(DATE_ADD(created_at, INTERVAL '.$timeZoneOffset.' HOUR)) as "date"'), DB::raw('count(id) as "total"'))->orderBy('date')->groupBy('date')->having('date', '>', Carbon::now()->subMonths(3)->format('Y-m').'-1')->get();

        $dataset = $data->mapWithKeys(function ($item, $key) {
            return [
                $key => [
                    'x' => $item['date'],
                    'y' => $item['total'],
                ],
            ];
        })->toJson();

        return view('widgets.reg_students', [
            'config' => $this->config,
            'registeredStudents' => [
                'count' => $count,
                'dataset' => $dataset,
            ],
        ]);
    }

    public function placeholder()
    {
        return 'Loading Registered Students...';
    }
}
