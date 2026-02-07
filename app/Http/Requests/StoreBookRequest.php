<?php

namespace App\Http\Requests;

class StoreBookRequest extends AbstractFormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'isbn' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    // 正規化（ハイフン等を除去）
                    $normalized = preg_replace('/[^0-9Xx]/', '', $value);
                    
                    // 10桁または13桁の数字のみかチェック
                    if (!in_array(strlen($normalized), [10, 13], true)) {
                        $fail('ISBNは10桁または13桁の数字である必要があります。');
                    }
                },
            ],
        ];
    }

    /**
     * バリデーション後のデータを取得（正規化済み）
     */
    public function validatedData(): array
    {
        $data = parent::validatedData();
        
        // ISBNを正規化（ハイフン等を除去）
        if (isset($data['isbn'])) {
            $data['isbn'] = preg_replace('/[^0-9Xx]/', '', $data['isbn']);
        }
        
        return $data;
    }
}