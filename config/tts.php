<?php

return [
    'driver' => env('TTS_DRIVER', 'openai'),

    'openai' => [
        'model' => env('OPENAI_TTS_MODEL', 'tts-1'),
        'voice' => env('OPENAI_TTS_VOICE', 'alloy'),
        'speed' => (float) env('OPENAI_TTS_SPEED', 1.0),
    ],

    'quality_options' => [
        ['id' => 'fast', 'label' => '速い', 'model' => 'tts-1'],
        ['id' => 'hd', 'label' => 'きれい', 'model' => 'tts-1-hd'],
    ],

    // フロント用: 読み上げモード（OpenAI / VOICEVOXローカル / 自動）
    'backend_options' => [
        ['id' => 'openai', 'label' => 'OpenAI'],
        ['id' => 'voicevox_local', 'label' => 'VOICEVOX ローカル'],
        ['id' => 'auto', 'label' => '自動（ローカル→サーバー）'],
    ],

    // ローカル VOICEVOX Engine のベースURL（フロントから参照）
    'voicevox_local_url' => env('VOICEVOX_LOCAL_URL', 'http://127.0.0.1:50021'),

    // キャラ → VOICEVOX 話者ID（ずんだもん=3, 四国めたん=2, つむぎ=8）
    'character_speaker_ids' => [
        'zundamon' => 3,
        'metan' => 2,
        'tsumugi' => 8,
    ],
];
