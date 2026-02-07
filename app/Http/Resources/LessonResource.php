<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LessonResource extends JsonResource
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
            'has_topic' => $this->has_topic,
            'course' => new CourseResource($this->whenLoaded('course')),
            'topics' => new TopicCollection($this->whenLoaded('topics')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    public function with($request)
    {
        return ['success' => true, 'status' => 'success'];
    }
}
