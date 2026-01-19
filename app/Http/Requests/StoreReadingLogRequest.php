<?php

// app/Http/Requests/StoreReadingLogRequest.php

namespace App\Http\Requests;

use App\Models\ReadingLog;
class StoreReadingLogRequest extends AbstractFormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'book_id' => ['required', 'uuid', 'exists:books,id'],
            'status'  => ['nullable', 'in:' . implode(',', ReadingLog::statuses())],
        ];
    }
}
