<?php

namespace App\Http\Requests;

class BookSearchRequest extends AbstractFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * バリデーション後のデータを取得（ISBNの場合は正規化）
     */
    public function validatedData(): array
    {
        $data = parent::validatedData();
        
        if (isset($data['q']) && $data['q'] !== '') {
            // ISBNの可能性がある場合は正規化
            $normalized = preg_replace('/[^0-9Xx]/', '', $data['q']);
            
            // 10桁または13桁の数字のみの場合はISBNとして正規化
            if (in_array(strlen($normalized), [10, 13], true)) {
                $data['q'] = $normalized;
            }
            // キーワードの場合はそのまま（正規化しない）
        }
        
        return $data;
    }
}
