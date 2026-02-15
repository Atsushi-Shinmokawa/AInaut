<?php

namespace App\Services\Tts;

use Illuminate\Support\Facades\Log;

class TtsService
{
    public function __construct(
        private OpenAiTtsEngine $openAi
    ) {}

    /**
     * テキストを音声に変換し、MP3バイナリを返す
     *
     * @param string $text 読み上げるテキスト（最大4096文字）
     * @param array{model?: string, voice?: string, speed?: float} $options
     * @return string MP3のバイナリ
     */
    public function synthesize(string $text, array $options = []): string
    {
        $text = trim($text);
        if ($text === '') {
            throw new \InvalidArgumentException('Text cannot be empty.');
        }

        $driver = config('tts.driver', 'openai');
        if ($driver !== 'openai') {
            throw new \InvalidArgumentException("Unsupported TTS driver: {$driver}");
        }

        $model = $options['model'] ?? config('tts.openai.model', 'tts-1');
        $voice = $options['voice'] ?? config('tts.openai.voice', 'alloy');
        $speed = (float) ($options['speed'] ?? config('tts.openai.speed', 1.0));

        return $this->openAi->synthesize($text, $model, $voice, $speed);
    }
}
