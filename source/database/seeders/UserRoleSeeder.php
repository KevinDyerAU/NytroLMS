<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Enrolment;
use App\Models\UserDetail;
use Database\Factories\AdminFactory;
use Database\Factories\LeaderFactory;
use Database\Factories\StudentFactory;
use Database\Factories\TrainerFactory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class UserRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run() {
        $users = [];
        $users['Student'] = (new StudentFactory())
            ->has(UserDetail::factory(), 'detail')
            ->count(3)
            ->create();

        $users['Leader'] = (new LeaderFactory())
            ->has(UserDetail::factory(), 'detail')
            ->has(Company::factory(), 'companies')
            ->count(1)
            ->create();

        $users['Trainer'] = (new TrainerFactory())
            ->has(UserDetail::factory(), 'detail')
            ->count(1)
            ->create();

        //        $users['Admin'] = (new AdminFactory())
        //            ->has(UserDetail::factory(), 'detail')
        //            ->count(5)
        //            ->create();

        foreach ($users as $role => $data) {
            foreach ($data as $user) {
                if (in_array($role, ['Student', 'Trainer', 'Leader'])) {
                    $user->userable_type = 'App\\Models\\' . $role;
                    $user->userable_id = $user->id;
                    $user->save();
                }
                $user->assignRole($role);
            }
        }
        $employment_service = ['Workforce Australia', 'Disability Employment Service (DES)', 'Inclusive Employment Australia (IEA)', 'Transition to Work (TTW)', 'Parent Pathways', 'Other'];
        $schedule = ['25 Hours', '15 Hours', '8 Hours', 'No Time Limit', 'Not Applicable'];
        if (isset($users['Student']) && count($users['Student']) > 0) {
            foreach ($users['Student'] as $student) {
                $student->trainers()->attach(\Arr::random(collect($users['Trainer'])->pluck('id')->toArray()));
                $student->leaders()->attach(\Arr::random(collect($users['Leader'])->pluck('id')->toArray()));
                $student->companies()->sync(\Arr::random(Company::all()->pluck('id')->toArray()));
                $student->enrolments()->save((new Enrolment([
                    'enrolment_key' => 'basic',
                    'enrolment_value' => new Collection([
                        'schedule' => \Arr::random($schedule),
                        'employment_service' => \Arr::random($employment_service),
                    ]),
                ])));
            }
        }
    }
}
