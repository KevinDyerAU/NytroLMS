<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CourseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'course_length_days' => $this->course_length_days,
            'next_course_after_days' => $this->next_course_after_days,
            'next_course' => $this->next_course,
            'auto_register_next_course' => $this->auto_register_next_course,
            'visibility' => $this->visibility,
            'status' => $this->status,
            'revisions' => $this->revisions,
            'lessons' => new LessonCollection($this->whenLoaded('lessons')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    public function with($request)
    {
        return ['success' => true, 'status' => 'success'];
    }
}
