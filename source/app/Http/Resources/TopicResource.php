<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TopicResource extends JsonResource
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
            'order' => $this->order,
            'slug' => $this->slug,
            'title' => $this->title,
            'has_quiz' => $this->has_quiz,
            'course' => new CourseResource($this->whenLoaded('course')),
            'lesson' => new LessonResource($this->whenLoaded('lesson')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    public function with($request)
    {
        return ['success' => true, 'status' => 'success'];
    }
}
