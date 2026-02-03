<?php

namespace App\Resources;

use App\Models\AiSummary;
use Illuminate\Http\Resources\Json\JsonResource;

class AiSummaryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var AiSummary $this->resource */
        return [
            'id' => $this->resource->id,
            'content' => $this->resource->content,
            'created_at' => $this->resource->created_at?->toIso8601String(),
        ];
    }
}
