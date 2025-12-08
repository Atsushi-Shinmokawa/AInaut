<?php

// app/Http/Requests/StoreReadingLogRequest.php

namespace App\Http\Requests;

use App\Models\ReadingLog;
use Illuminate\Foundation\Http\FormRequest;

class StoreReadingLogRequest extends FormRequest
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
