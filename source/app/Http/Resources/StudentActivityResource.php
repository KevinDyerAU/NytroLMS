<?php

namespace App\Http\Resources;

use App\Helpers\Helper;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentActivityResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        //        if($this->user_id === 268){
        //            Helper::debug(get_class($this->resource), 'dd');
        //        }

        if (get_class($this->resource) === "App\Models\Competency") {
            return $this->CompetencyResourceArray();
        }

        //        $timeZone = Helper::getTimeZone();
        return [
            'id' => $this->id,
            'url' => '',
            'title' => \Str::title($this->activity_event),
            'timezone' => Helper::getTimeZone(),
            'start' => Carbon::parse($this->created_at)->timezone(Helper::getTimeZone())->toAtomString(),
            'end' => Carbon::parse($this->created_at)->timezone(Helper::getTimeZone())->toAtomString(),
            'allDay' => false,
            'extendedProps' => [
                //                'tz' => Helper::getTimeZone(),
                //                'date_default_timezone_get' => date_default_timezone_get(),
                //                'created_at'=> $this->created_at,
                //                'created_at_converted' => Carbon::parse($this->created_at)->tz(Helper::getTimeZone())->isoFormat('llll'),
                'calendar' => $this->getColour($this->activity_event),
                'description' => $this->activity_details,
                'related' => $this->when($this->relationLoaded('actionable'), function () {
                    if (empty($this->actionable)) {
                        return [];
                    }

                    return $this->actionable->toArray();
                }),
            ],
        ];
    }

    public function with($request)
    {
        return ['success' => true, 'status' => 'success'];
    }

    private function getColour(mixed $activity_event)
    {
        return ($activity_event === 'SIGN IN' || $activity_event === 'SIGN OUT') ? 'AUTH' : (strstr($activity_event, 'QUIZ') ? 'LMS' : 'OTHER');
    }

    private function CompetencyResourceArray()
    {
        $lesson_end = empty($this->competent_on) ? $this->lesson_end : $this->competent_on;

        return [
            'id' => $this->id,
            'url' => '',
            'title' => 'Lesson End',
            'timezone' => Helper::getTimeZone(),
            'start' => Carbon::parse($lesson_end)->timezone(Helper::getTimeZone())->toAtomString(),
            'end' => Carbon::parse($lesson_end)->timezone(Helper::getTimeZone())->toAtomString(),
            'allDay' => false,
            'extendedProps' => [
                //                'tz' => Helper::getTimeZone(),
                //                'date_default_timezone_get' => date_default_timezone_get(),
                //                'created_at'=> $this->created_at,
                //                'created_at_converted' => Carbon::parse($this->created_at)->tz(Helper::getTimeZone())->isoFormat('llll'),
                'calendar' => $this->getColour('LESSON END'),
                'description' => 'Lesson: '.$this->lesson?->title.' at '.Carbon::parse($lesson_end)->timezone(Helper::getTimeZone())->isoFormat('llll'),
                'related' => $this->when($this->relationLoaded('lesson'), function () {
                    if (empty($this->lesson)) {
                        return [];
                    }

                    return $this->lesson->toArray();
                }),
            ],
        ];
    }
}
