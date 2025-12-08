<?php

// app/Http/Requests/UpdateReadingLogRequest.php

namespace App\Http\Requests;

use App\Models\ReadingLog;
use Illuminate\Foundation\Http\FormRequest;

class UpdateReadingLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var \App\Models\Domain\ReadingLog $readingLog */
        $readingLog = $this->route('readingLog');

        return $readingLog
            && $this->user()
            && $readingLog->user_id === $this->user()->id;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:' . implode(',', ReadingLog::statuses())],
        ];
    }
}
