<?php

namespace App\Http\Resources;

use App\Http\Resources\BranchResource;
use Illuminate\Http\Resources\Json\JsonResource;

class DoctorResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'user' => UserResource::make($this->whenLoaded('user')),

            'id' => $this->id,
            'user_id' => $this->user_id,
            'clinic_id' => $this->clinic_id,
            'branch_id' => $this->branch_id,
            'specialty' => $this->specialty,
            'bio' => $this->bio,
            'consultation_fee' => (string) $this->consultation_fee,
            'license_number' => $this->license_number,
            'years_experience' => $this->years_experience,
            'ai_summary_enabled' => $this->ai_summary_enabled,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            
            'clinic' => ClinicResource::make($this->whenLoaded('clinic')),
            'branch' => BranchResource::make($this->whenLoaded('branch')),
            'schedules' => DoctorScheduleResource::collection($this->whenLoaded('schedules')),


        ];
    }
}
