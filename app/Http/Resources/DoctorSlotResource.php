<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DoctorSlotResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'start_time' => is_array($this->resource) ? ($this->resource['start_time'] ?? null) : $this->start_time,
            'end_time' => is_array($this->resource) ? ($this->resource['end_time'] ?? null) : $this->end_time,
        ];
    }
}
