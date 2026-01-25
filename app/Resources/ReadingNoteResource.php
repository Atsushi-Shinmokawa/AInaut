<?php

namespace App\Resources;

use App\Models\ReadingNote;
use Illuminate\Http\Resources\Json\JsonResource;

class ReadingNoteResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var ReadingNote $this->resource */
        return [
            'id' => $this->resource->id,
            'content' => $this->resource->content,
            'page' => $this->resource->page_number,
            'created_at' => $this->resource->created_at->format('Y-m-d H:i'),
        ];
    }
}
