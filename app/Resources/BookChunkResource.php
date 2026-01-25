<?php

namespace App\Resources;

use App\Models\BookChunk;
use Illuminate\Http\Resources\Json\JsonResource;

class BookChunkResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var BookChunk $this->resource */
        return [
            'id' => $this->resource->id,
            'content' => $this->resource->content,
            'chunk_index' => $this->resource->chunk_index,
        ];
    }
}
