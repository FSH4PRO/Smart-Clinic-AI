<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'role' => $this->role?->value,
            'avatar' => $this->avatar,
            'phone_verified_at' => optional($this->phone_verified_at)?->toISOString(),
            'email_verified_at' => optional($this->email_verified_at)?->toISOString(),
            'created_at' => optional($this->created_at)?->toISOString(),
            'updated_at' => optional($this->updated_at)?->toISOString(),
        ];
 
        // Include profile data if relationship is loaded
        if ($this->patient !== null) {
        $data['profile'] = new PatientResource($this->patient);
    } elseif ($this->doctor !== null) {
        $data['profile'] = new DoctorResource($this->doctor);
    }
        return $data;
    }
}
