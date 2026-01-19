<?php

namespace App\Http\Requests;

class BookHighlightImportPreviewRequest extends AbstractFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'raw_text' => ['required', 'string', 'min:20'],
        ];
    }
}
