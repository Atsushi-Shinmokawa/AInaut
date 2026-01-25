<?php

namespace App\Resources;

use App\Models\BookMessage;
use Illuminate\Http\Resources\Json\JsonResource;

class BookMessageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var BookMessage $this->resource */
        return [
            'id' => $this->resource->id,
            'role' => $this->resource->role,
            'content' => $this->resource->content,
            'created_at' => $this->resource->created_at?->toIso8601String(),
        ];
    }
}
