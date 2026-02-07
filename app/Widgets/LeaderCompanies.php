<?php

namespace App\Widgets;

use App\Models\Company;
use Arrilot\Widgets\AbstractWidget;

class LeaderCompanies extends AbstractWidget
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
        $leader_companies = auth()->user()->isLeader() ? auth()->user()->companies : null;
        $data = [];
        if ($leader_companies) {
            $data['list'] = $leader_companies->map(function (Company $company) {
                //                dd($company->students->toArray());
                return [
                    'id' => $company->id,
                    'name' => $company->name,
                    'associated_users' => $company->users,
                    'associated_students' => $company->students,
                ];
            });
            $data['count'] = count($leader_companies);
        }

        return view('widgets.leader_companies', [
            'config' => $this->config,
            'data' => $data ?? [],
        ]);
    }

    public function placeholder()
    {
        return 'Loading Leader\'s Companies...';
    }
}
