<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;

class NoteResource extends JsonResource
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
            'note_body' => $this->note_body,
            'for' => new UserResource(User::find($this->student_id)),
            'by' => new UserResource(User::find($this->user_id)),
            'at' => $this->created_at,
        ];
    }

    public function with($request)
    {
        return ['success' => true, 'status' => 'success'];
    }
}
