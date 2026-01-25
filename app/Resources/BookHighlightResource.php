<?php

namespace App\Resources;

use App\Models\BookHighlight;
use Illuminate\Http\Resources\Json\JsonResource;

class BookHighlightResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var BookHighlight $this->resource */
        return [
            'id' => $this->resource->id,
            'content' => $this->resource->content,
            'page' => $this->resource->page,
            'location' => $this->resource->location,
            'created_at' => $this->resource->created_at?->toIso8601String(),
            'title_raw' => $this->resource->title_raw,
        ];
    }
}
