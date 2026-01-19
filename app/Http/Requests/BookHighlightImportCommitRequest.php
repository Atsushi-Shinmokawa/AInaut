<?php

namespace App\Http\Requests;

class BookHighlightImportCommitRequest extends AbstractFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.source' => ['required', 'string'],
            'items.*.title_raw' => ['nullable', 'string'],
            'items.*.location' => ['nullable', 'string'],
            'items.*.page' => ['nullable', 'string'],
            'items.*.highlighted_at' => ['nullable', 'string'],
            'items.*.content' => ['required', 'string'],
            'items.*.content_hash' => ['nullable', 'string'],
        ];
    }
}
