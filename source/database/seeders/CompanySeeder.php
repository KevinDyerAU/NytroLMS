<?php

namespace Database\Seeders;

use App\Models\AccountManager\Company;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Company::factory()->count(5)->create();
        Company::factory()->count(2)->create(['deleted_at' => now()]);
    }
}
