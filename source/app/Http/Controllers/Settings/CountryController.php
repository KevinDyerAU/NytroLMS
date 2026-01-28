<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Support\Arr;
use PragmaRX\Countries\Package\Countries as CountryPackage;

class CountryController extends Controller
{
    public function toDatabaseArray()
    {
        $countriesPackage = new CountryPackage();

        $toDb = $countriesPackage->all()->map(function ($country) {
            $timezones = $country->hydrate('timezones')->timezones ?? '';
            if (isset($timezones)) {
                $timezones = $timezones->pluck('zone_name', 'zone_id');
            }

            return [
                'name' => $country->name->common ?? '',
                'calling_codes' => !empty($country->calling_codes) ? $country->calling_codes->first() : '',
                'abbreviation' => $country->cca3 ?? '',
                'code' => $country->cca2 ?? '',
                'languages' => $country->languages ?? '',
                'currency' => !empty($country->currencies) ? $country->currencies->first() : '',
                'flag' => !empty($country->flag) ? $country->flag->flag_icon : '',
                'timezones' => $timezones,
            ];
        });

        //        $timezones = $toDb->pluck('timezones')->flatten()->toArray();
        //        $toDbTimezones = array_filter(Arr::flatten($timezones));

        return $toDb;
    }
}
