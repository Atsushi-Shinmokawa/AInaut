<?php

namespace App\Http\Requests;

class BookChatSendRequest extends AbstractFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'content'   => ['required', 'string', 'max:4000'],
            'thread_id' => ['nullable', 'uuid'],
        ];
    }
}
