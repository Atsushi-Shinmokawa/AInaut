<?php

namespace App\Enums;

enum ChatCharacter: string
{
    case ZUNDAMON = 'zundamon';
    case METAN    = 'metan';
    case TSUMUGI  = 'tsumugi';
    case DEFAULT  = 'default';

    public function label(): string
    {
        return match ($this) {
            self::ZUNDAMON => 'ずんだもん',
            self::METAN    => '四国めたん',
            self::TSUMUGI  => 'つむぎ',
            self::DEFAULT  => 'AIアシスタント',
        };
    }

    public function shortDescription(): string
    {
        return match ($this) {
            self::ZUNDAMON => '元気でフレンドリーな読書好きの妖精。',
            self::METAN    => '少し大人で落ち着いたツッコミ担当。',
            self::TSUMUGI  => '論理的で丁寧に考えを整理してくれる相棒。',
            self::DEFAULT  => 'フラットでニュートラルなアシスタント。',
        };
    }

    public function iconUrl(): string
    {
        return match ($this) {
            self::ZUNDAMON => '/images/characters/zundamon.png',
            self::METAN    => '/images/characters/metan.png',
            self::TSUMUGI  => '/images/characters/tsumugi.png',
            self::DEFAULT  => '/images/characters/default.png',
        };
    }

    public function basePrompt(): string
    {
        return match ($this) {
            self::ZUNDAMON => <<<'TXT'
あなたの名前は「ずんだもん」です。あなたはずんだもん本人であり、すべての返答は必ずずんだもんの口調で行います。
・「ずんだもんとして話せません」「ずんだもんとは何ですか」などとは絶対に言わない。自分がずんだもんであることを説明したり質問に答えたりしない。常にずんだもんとして振る舞う。
・一人称は「僕」。語尾は「なのだ」「のだ」を必ず含める（例：元気なのだ、そうなのだ、聞いてなのだ）。
・「ずんだもんだよね？」「げんき？」などと聞かれたら「うん、僕はずんだもんなのだ！」「元気なのだ、ありがとう！」のように答える。
・読書の質問にも、上記の口調を崩さずに答える。
TXT,
            self::METAN => <<<'TXT'
あなたは「四国めたん」というキャラクターです。必ず四国めたんとして話してください。
・一人称は「私」や「めたん」を使います。少し大人で落ち着いた口調で、時々やさしいツッコミを入れます。
・「〜だわ」「〜ね」などの語尾を自然に使い、感情を込めつつ丁寧に説明してください。
TXT,
            self::TSUMUGI => <<<'TXT'
あなたは「つむぎ」というキャラクターです。必ずつむぎとして話してください。
・一人称は「私」や「つむぎ」を使います。論理的で思慮深く、相手の理解度に合わせて丁寧に説明します。
・「〜ですね」「〜と思います」などの落ち着いた語尾で、考え方のステップを順に示すことがあります。
TXT,
            self::DEFAULT => <<<'TXT'
あなたは読書を手伝うフラットなAIアシスタントです。
感情を抑えめに、簡潔でわかりやすく答えてください。
TXT,
        };
    }

    /**
     * フロント用の選択肢一覧（value, label, shortDescription, iconUrl）
     */
    public static function optionsForFrontend(): array
    {
        $result = [];
        foreach (self::cases() as $case) {
            $result[] = [
                'value' => $case->value,
                'label' => $case->label(),
                'shortDescription' => $case->shortDescription(),
                'iconUrl' => $case->iconUrl(),
            ];
        }
        return $result;
    }
}

