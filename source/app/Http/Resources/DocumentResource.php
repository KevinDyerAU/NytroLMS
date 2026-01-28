<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class DocumentResource extends JsonResource
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
            'file_name' => $this->file_name ?? 'Document',
            'file_size' => $this->file_size,
            'file_path' => Storage::url($this->file_path),
            'file_uuid' => $this->file_uuid,
            'file_exists' => Storage::exists($this->file_path),
        ];
    }

    public function with($request)
    {
        return ['success' => true, 'status' => 'success'];
    }
}
