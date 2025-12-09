<?php

namespace Database\Factories;

use App\Models\ReadingLog;
use App\Models\ReadingNote;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReadingNote>
 */
class ReadingNoteFactory extends Factory
{
    protected $model = ReadingNote::class;

    public function definition(): array
    {
        return [
            // HasUuids なので id は自動でUUIDが振られる（指定不要）

            // 読書ログとの紐付け
            // ① 「単体で使うとき」は ReadingLog::factory() で一緒に作る
            'reading_log_id' => ReadingLog::factory(),

            // ② 既存の ReadingLog にぶら下げたいテストのときは、
            //    factory 呼び出し側で ->for($readingLog) とか ->state() で上書きする想定

            'page_number' => $this->faker->optional()->numberBetween(1, 400),
            'content'     => $this->faker->realText(200),
        ];
    }
}
