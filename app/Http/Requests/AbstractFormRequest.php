<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

abstract class AbstractFormRequest extends FormRequest
{
    /**
     * バリデーション前に自動的にトリムを適用
     */
    protected function prepareForValidation(): void
{
    $input = $this->all();

    foreach ($input as $key => $value) {
        if (is_string($value)) {
            $input[$key] = $this->trimWithFullSpace($value);
        } elseif (is_array($value)) {
            $input[$key] = $this->trimArray($value);
        }
    }

    $this->merge($input);
}


    /**
     * 文字列をトリム
     */
    private function trimWithFullSpace(string $value): string
{
    // ASCII空白(\s) + 全角空白(　) を両端から削除
    return preg_replace('/\A[\s　]+|[\s　]+\z/u', '', $value) ?? $value;
}


    /**
     * 配列を再帰的にトリム
     */
    private function trimArray(array $array): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            if (is_string($value)) {
                $result[$key] = $this->trimWithFullSpace($value);
            } elseif (is_array($value)) {
                $result[$key] = $this->trimArray($value);
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * validated()のエイリアス（Eneboxパターンに合わせる）
     */
    public function validatedData(): array
    {
        return $this->validated();
    }
}
