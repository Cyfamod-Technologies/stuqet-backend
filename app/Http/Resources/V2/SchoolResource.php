<?php

namespace App\Http\Resources\V2;

use Illuminate\Http\Resources\Json\JsonResource;

class SchoolResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => (int) $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'logo_id' => $this->logo === null ? null : (int) $this->logo,
            'logo_url' => $this->logo ? uploaded_asset($this->logo) : null,
        ];
    }
}
