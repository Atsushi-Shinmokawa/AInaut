import { ref, watch, onUnmounted } from "vue";

/** CSRF トークン取得（meta 優先、無ければ XSRF-TOKEN Cookie）。戻りは { header, value } */
function getCsrfHeader(): { header: string; value: string } {
    const meta = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content");
    if (meta) return { header: "X-CSRF-TOKEN", value: meta };
    const m = document.cookie.match(/\bXSRF-TOKEN=([^;]+)/);
    const value = m ? decodeURIComponent(m[1]) : "";
    return { header: "X-XSRF-TOKEN", value };
}

export type TtsBackendOption = { id: string; label: string };
export type TtsConfig = {
    qualityOptions: { id: string; label: string; model: string }[];
    defaultModel: string;
    defaultSpeed: number;
    backendOptions?: TtsBackendOption[];
    voicevoxLocalBaseUrl?: string;
    characterSpeakerIds?: Record<string, number>;
};

/** 読み上げ用にテキストを文・チャンク単位に分割（最初の一文を早く再生するため） */
const MAX_CHUNK_LEN = 200;
const SENTENCE_END = /([。．？！\n]+)/;

function splitTextForTts(text: string): string[] {
    const trimmed = text.trim();
    if (!trimmed) return [];

    const parts = trimmed.split(SENTENCE_END).filter(Boolean);
    const segments: string[] = [];
    for (let i = 0; i < parts.length; i++) {
        const isDelim = SENTENCE_END.test(parts[i]);
        if (isDelim && segments.length > 0) {
            segments[segments.length - 1] += parts[i];
        } else if (!isDelim) {
            segments.push(parts[i]);
        }
    }

    const result: string[] = [];
    for (const seg of segments) {
        const s = seg.trim();
        if (!s) continue;
        if (s.length <= MAX_CHUNK_LEN) {
            result.push(s);
        } else {
            for (let i = 0; i < s.length; i += MAX_CHUNK_LEN) {
                result.push(s.slice(i, i + MAX_CHUNK_LEN));
            }
        }
    }
    return result;
}

/** ローカル VOICEVOX Engine で音声合成（audio_query → synthesis） */
async function synthesizeVoicevoxLocal(
    baseUrl: string,
    text: string,
    speakerId: number
): Promise<Blob> {
    const trimmed = text.trim().slice(0, 4096);
    if (!trimmed) throw new Error("テキストが空です");

    const queryUrl = `${baseUrl.replace(/\/$/, "")}/audio_query?speaker=${speakerId}&text=${encodeURIComponent(trimmed)}`;
    const queryRes = await fetch(queryUrl, { method: "POST" });
    if (!queryRes.ok) throw new Error(`VOICEVOX audio_query: ${queryRes.status}`);
    const queryJson = await queryRes.json();

    const synthUrl = `${baseUrl.replace(/\/$/, "")}/synthesis?speaker=${speakerId}`;
    const synthRes = await fetch(synthUrl, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(queryJson),
    });
    if (!synthRes.ok) throw new Error(`VOICEVOX synthesis: ${synthRes.status}`);
    return await synthRes.blob();
}

export function useTtsPlayer(bookId: string, ttsConfig: TtsConfig) {
    const playbackRate = ref(1.0);
    const ttsModel = ref(ttsConfig.defaultModel);
    const ttsBackend = ref<"openai" | "voicevox_local" | "auto">("openai");
    const isPlaying = ref(false);
    const playingMessageId = ref<string | null>(null);
    const loadingMessageId = ref<string | null>(null);

    let currentAudio: HTMLAudioElement | null = null;
    const blobQueue: Blob[] = [];
    let queueMessageId: string | null = null;
    let cancelled = false;

    const defaultSpeed = ttsConfig.defaultSpeed;
    const qualityOptions = ttsConfig.qualityOptions;
    const voicevoxBaseUrl = ttsConfig.voicevoxLocalBaseUrl ?? "http://127.0.0.1:50021";
    const characterSpeakerIds = ttsConfig.characterSpeakerIds ?? { zundamon: 3, metan: 2, tsumugi: 8 };

    function stop() {
        cancelled = true;
        blobQueue.length = 0;
        queueMessageId = null;
        if (currentAudio) {
            currentAudio.pause();
            currentAudio = null;
        }
        isPlaying.value = false;
        playingMessageId.value = null;
        loadingMessageId.value = null;
    }

    function getSpeakerId(character?: string): number {
        const key = (character ?? "zundamon").toLowerCase();
        return characterSpeakerIds[key] ?? 3;
    }

    async function playViaOpenai(
        trimmed: string,
        model: string,
        speed: number,
        messageId: string | null
    ): Promise<Blob> {
        const url = route("books.chat.tts", { book: bookId });
        const body = JSON.stringify({ text: trimmed, tts_model: model, speed });
        const csrf = getCsrfHeader();
        const res = await fetch(url, {
            method: "POST",
            credentials: "same-origin",
            headers: {
                "Content-Type": "application/json",
                Accept: "audio/mpeg",
                "X-Requested-With": "XMLHttpRequest",
                [csrf.header]: csrf.value,
            },
            body,
        });
        if (!res.ok) {
            const errText = await res.text();
            console.error("TTS request failed", res.status, errText);
            throw new Error(res.status === 419 ? "CSRFエラーです。ページを再読み込みしてください。" : `読み上げに失敗しました (${res.status})`);
        }
        const blob = await res.blob();
        if (blob.size === 0 || !blob.type.startsWith("audio/")) {
            console.error("TTS invalid response", blob.type, blob.size);
            throw new Error("音声データの取得に失敗しました。");
        }
        return blob;
    }

    async function playViaVoicevoxLocal(trimmed: string, speakerId: number): Promise<Blob> {
        return synthesizeVoicevoxLocal(voicevoxBaseUrl, trimmed, speakerId);
    }

    /** 1チャンク分の TTS を取得（バックエンドに応じて OpenAI / VOICEVOX） */
    async function requestOneChunk(
        chunk: string,
        backend: "openai" | "voicevox_local" | "auto",
        model: string,
        serverSpeed: number,
        speakerId: number
    ): Promise<Blob> {
        if (backend === "openai") return playViaOpenai(chunk, model, serverSpeed, null);
        if (backend === "voicevox_local") return playViaVoicevoxLocal(chunk, speakerId);
        try {
            return await playViaVoicevoxLocal(chunk, speakerId);
        } catch {
            return playViaOpenai(chunk, model, serverSpeed, null);
        }
    }

    function playNextFromQueue() {
        if (cancelled || blobQueue.length === 0) {
            if (blobQueue.length === 0) {
                isPlaying.value = false;
                playingMessageId.value = null;
                queueMessageId = null;
            }
            return;
        }
        const blob = blobQueue.shift()!;
        const objectUrl = URL.createObjectURL(blob);
        currentAudio = new Audio(objectUrl);
        currentAudio.playbackRate = playbackRate.value;
        playingMessageId.value = queueMessageId;
        isPlaying.value = true;
        currentAudio.onended = () => {
            URL.revokeObjectURL(objectUrl);
            currentAudio = null;
            playNextFromQueue();
        };
        currentAudio.onerror = (e) => {
            console.error("TTS queue playback error", e);
            URL.revokeObjectURL(objectUrl);
            currentAudio = null;
            playNextFromQueue();
        };
        loadingMessageId.value = null;
        currentAudio.play();
    }

    function playAudioFromBlob(blob: Blob, messageId: string | null) {
        const objectUrl = URL.createObjectURL(blob);
        currentAudio = new Audio(objectUrl);
        currentAudio.playbackRate = playbackRate.value;
        playingMessageId.value = messageId;
        loadingMessageId.value = null;
        isPlaying.value = true;
        currentAudio.onended = () => {
            URL.revokeObjectURL(objectUrl);
            currentAudio = null;
            isPlaying.value = false;
            playingMessageId.value = null;
        };
        currentAudio.onerror = (e) => {
            console.error("TTS audio playback error", e);
            URL.revokeObjectURL(objectUrl);
            currentAudio = null;
            isPlaying.value = false;
            playingMessageId.value = null;
            loadingMessageId.value = null;
        };
        return currentAudio.play();
    }

    watch(playbackRate, (rate) => {
        if (currentAudio) currentAudio.playbackRate = rate;
    });

    async function playText(
        text: string,
        options?: { messageId?: string; ttsModel?: string; speed?: number; character?: string }
    ): Promise<void> {
        const trimmed = text?.trim();
        if (!trimmed) return;

        stop();
        cancelled = false;

        const messageId = options?.messageId ?? null;
        loadingMessageId.value = messageId;

        const model = options?.ttsModel ?? ttsModel.value;
        const speakerId = getSpeakerId(options?.character);
        const backend = ttsBackend.value;
        const serverSpeed = 1.0;

        const chunks = splitTextForTts(trimmed);
        if (chunks.length === 0) {
            loadingMessageId.value = null;
            return;
        }

        try {
            if (chunks.length === 1) {
                const blob = await requestOneChunk(chunks[0], backend, model, serverSpeed, speakerId);
                if (!cancelled && blob) playAudioFromBlob(blob, messageId);
                return;
            }

            queueMessageId = messageId;
            for (let i = 0; i < chunks.length; i++) {
                if (cancelled) break;
                const blob = await requestOneChunk(chunks[i], backend, model, serverSpeed, speakerId);
                if (cancelled) break;
                blobQueue.push(blob);
                if (!currentAudio) playNextFromQueue();
            }
        } catch (e) {
            console.error("TTS play error", e);
            loadingMessageId.value = null;
            playingMessageId.value = null;
            queueMessageId = null;
            blobQueue.length = 0;
        }
    }

    onUnmounted(() => stop());

    return {
        playbackRate,
        ttsModel,
        ttsBackend,
        qualityOptions,
        defaultSpeed,
        isPlaying,
        playingMessageId,
        loadingMessageId,
        playText,
        stop,
    };
}
