<?php

namespace App\Resources;

use App\Models\ReadingLog;
use Illuminate\Http\Resources\Json\JsonResource;

class ReadingLogResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var ReadingLog $this->resource */
        return [
            'id' => $this->resource->id,
            'status' => $this->resource->status->value,
            'added_at' => $this->resource->created_at->format('Y-m-d'),
            'book' => [
                'id' => $this->resource->book->id,
                'title' => $this->resource->book->title,
                'author' => optional($this->resource->book->author)->name,
            ],
            'notes' => ReadingNoteResource::collection($this->resource->readingNotes)->resolve(),
        ];
    }
}
