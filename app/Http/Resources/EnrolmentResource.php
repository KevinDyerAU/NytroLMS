<?php

namespace App\Http\Resources;

use App\Helpers\Helper;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class EnrolmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'enrolment_key' => $this->enrolment_key,
            'enrolment_value' => $this->formatValues($this->enrolment_value),
        ];
    }

    public function formatValues($enrolment_value)
    {
        $values = [];

        foreach ($enrolment_value as $step => $v) {
            $values[$step] = [];
            if (is_iterable($v)) {
                foreach ($v as $key => $val) {
                    $values[$step][$key] = [
                        'key' => $key,
                        'value' => $val,
                    ];
                }
            }
        }

        if (isset($values['step-1']['dob'])) {
            $values['step-1']['dob']['value'] = Carbon::parse($values['step-1']['dob']['value'])->format('d-m-Y');
        }
        if (isset($values['step-6']['signed_on'])) {
            $signedOnValue = $values['step-6']['signed_on']['value'];
            // Handle both epoch timestamp (legacy) and ISO 8601 date string (new format)
            $carbonDate = is_numeric($signedOnValue)
                ? Carbon::createFromTimestamp($signedOnValue)
                : Carbon::parse($signedOnValue);
            $values['step-6']['signed_on']['value'] = $carbonDate->timezone(Helper::getTimeZone())->format('d-m-Y');
        }
        if (isset($values['step-5']['signed_on'])) {
            $signedOnValue = $values['step-5']['signed_on']['value'];
            // Handle both epoch timestamp (legacy) and ISO 8601 date string (new format)
            $carbonDate = is_numeric($signedOnValue)
                ? Carbon::createFromTimestamp($signedOnValue)
                : Carbon::parse($signedOnValue);
            $values['step-5']['signed_on']['value'] = $carbonDate->timezone(Helper::getTimeZone())->format('d-m-Y');
        }
        if (isset($values['step-1']['country'])) {
            $values['step-1']['country']['key'] = 'Country of birth';
        }
        if (isset($values['step-1']['birthplace'])) {
            $values['step-1']['birthplace']['key'] = 'City of birth';
        }
        if (isset($values['step-1']['torres_island'])) {
            $values['step-1']['torres_island']['key'] = 'Indigenous status';
        }
        if (isset($values['step-1']['gender'])) {
            if ($values['step-1']['gender']['value'] === 'other') {
                $values['step-1']['gender']['value'] = 'Indeterminate/Intersex/Unspecified';
            } else {
                $values['step-1']['gender']['value'] = \Str::title($values['step-1']['gender']['value']);
            }
        }

        return collect($values);
    }

    public function with($request)
    {
        return ['success' => true, 'status' => 'success'];
    }
}
