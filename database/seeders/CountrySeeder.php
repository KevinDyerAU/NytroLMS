<?php

namespace Database\Seeders;

use App\Http\Controllers\Settings\CountryController;
use App\Models\Country;
use App\Models\Timezone;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class CountrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Schema::disableForeignKeyConstraints();
        if (Storage::disk('dump')->exists('countries.sql')) {
            DB::table('countries')->truncate();
            DB::unprepared(Storage::disk('dump')->get('countries.sql'));
        }
        if (Storage::disk('dump')->exists('timezones.sql')) {
            DB::table('timezones')->truncate();
            DB::unprepared(Storage::disk('dump')->get('timezones.sql'));
        }
        Schema::enableForeignKeyConstraints();
        //        $countries = new CountryController();
        //        $toDb = $countries->toDatabaseArray();
        //
        //        $timezones = $toDb->pluck('timezones')->flatten()->filter()->toArray();
        //        $toDbTimezones = array_filter(Arr::flatten($timezones));
        //        foreach ($toDbTimezones as $timezone) {
        //            $tz = Timezone::firstOrCreate(['name'=> $timezone],
        //            ['region' => substr($timezone,0, strpos($timezone,'/'))]);
        //            $tz->save();
        //        }
        //
        //        foreach ($toDb as $country){
        //            $toDbCountry = $country->forget('timezones')->toArray();
        //            Country::create($toDbCountry);
        //        }
    }
}
