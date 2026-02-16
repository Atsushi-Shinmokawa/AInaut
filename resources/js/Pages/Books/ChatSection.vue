<script setup lang="ts">
import { computed, ref, watch, nextTick, onUnmounted } from "vue";
import { useForm, usePage, router } from "@inertiajs/vue3";
import { Link } from "@inertiajs/vue3";
import InputError from "@/Components/InputError.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import { useTtsPlayer } from "@/composables/useTtsPlayer";

export type CharacterOption = {
    value: string;
    label: string;
    shortDescription: string;
    iconUrl: string;
};

export type ChatModelOption = { id: string; label: string; model: string };

export type TtsConfig = {
    qualityOptions: { id: string; label: string; model: string }[];
    defaultModel: string;
    defaultSpeed: number;
    backendOptions?: { id: string; label: string }[];
    voicevoxLocalBaseUrl?: string;
    characterSpeakerIds?: Record<string, number>;
};

const props = withDefaults(
    defineProps<{
        bookId: string;
        chatThreads?: { id: string; title: string; character?: string; model?: string | null; updated_at: string }[];
        chatThread?: { id: string; character?: string; model?: string | null } | null;
        chatMessages: any[];
        characterOptions?: CharacterOption[];
        chatModelOptions?: ChatModelOption[];
        ttsConfig?: TtsConfig | null;
    }>(),
    { chatThreads: () => [], chatThread: null, characterOptions: () => [], chatModelOptions: () => [], ttsConfig: null },
);

const ttsEnabled = ref(true);
const autoReadAloud = ref(false);
const ttsPlayer = props.ttsConfig
    ? useTtsPlayer(props.bookId, {
          qualityOptions: props.ttsConfig.qualityOptions,
          defaultModel: props.ttsConfig.defaultModel,
          defaultSpeed: props.ttsConfig.defaultSpeed,
      })
    : null;

// テンプレートで ref をアンラップするため（ttsPlayer.playbackRate は ref のため .toFixed が使えない）
const ttsPlaybackRateDisplay = computed({
    get: () => (ttsPlayer && typeof ttsPlayer.playbackRate?.value === "number" ? ttsPlayer.playbackRate.value : 1),
    set: (v: number) => {
        if (ttsPlayer?.playbackRate) ttsPlayer.playbackRate.value = v;
    },
});

// キャラ選択UI用（default 以外）
const selectableCharacters = computed(() =>
    props.characterOptions.filter((c) => c.value !== "default")
);
// 現在スレッドのキャラ情報（AIアイコン・名前表示用）
const currentCharacterInfo = computed(() => {
    const ch = props.chatThread?.character ?? "zundamon";
    return (
        props.characterOptions.find((c) => c.value === ch) ?? {
            value: ch,
            label: "AI",
            shortDescription: "",
            iconUrl: "",
        }
    );
});

const page = usePage();
const flash = computed(() => (page.props as any).flash ?? {});
const authUser = computed(() => (page.props as any).auth?.user ?? null);
const currentUserProfilePhotoUrl = computed<string | null>(
    () => authUser.value?.profile_photo_url ?? null,
);
const currentUserInitial = computed<string>(() => {
    const name = (authUser.value?.name as string | undefined) ?? "";
    return name.trim().charAt(0) || "?";
});

const bottomRef = ref<HTMLElement | null>(null);
const textareaRef = ref<HTMLTextAreaElement | null>(null);
const isPolling = ref(false);
let pollingTimer: ReturnType<typeof setInterval> | null = null;

const form = useForm<{ content: string; thread_id: string }>({
    content: "",
    thread_id: "",
});

const POLL_INTERVAL_MS = 2500;
const POLL_MAX_ATTEMPTS = 120; // 約5分で打ち切り

function stopPolling() {
    if (pollingTimer !== null) {
        clearInterval(pollingTimer);
        pollingTimer = null;
    }
    isPolling.value = false;
}

function startPolling(initialMessageCount: number) {
    if (isPolling.value) return;
    isPolling.value = true;
    let attempts = 0;

    pollingTimer = setInterval(async () => {
        attempts += 1;
        if (attempts > POLL_MAX_ATTEMPTS) {
            stopPolling();
            return;
        }

        try {
            const statusUrl =
                props.chatThread != null
                    ? route("books.chat.status", {
                          book: props.bookId,
                          thread: props.chatThread.id,
                      })
                    : route("books.chat.status", { book: props.bookId });
            const res = await fetch(statusUrl, {
                headers: {
                    Accept: "application/json",
                    "X-Requested-With": "XMLHttpRequest",
                },
            });
            const data = (await res.json()) as {
                messageCount: number;
                lastMessageRole: string | null;
            };
            if (
                data.messageCount > initialMessageCount &&
                data.lastMessageRole === "assistant"
            ) {
                stopPolling();
                router.reload({ preserveScroll: true });
            }
        } catch {
            // ネットワークエラー時は次回ポーリングで再試行
        }
    }, POLL_INTERVAL_MS);
}

function send() {
    if (!form.content.trim() || form.processing || isPolling.value) return;

    form.thread_id = props.chatThread?.id ?? "";
    form.post(route("books.chat.send", { book: props.bookId }), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset("content");
            form.thread_id = "";
            nextTick(() => textareaRef.value?.focus());
            // 送信成功時点のメッセージ数（ユーザー発言が1件増えた直後）
            const initialCount = props.chatMessages.length;
            startPolling(initialCount);
        },
    });
}

// Enterで送信（Shift+Enterは改行）。IME変換中のEnter（変換確定）では送信しない
function onKeydown(e: KeyboardEvent) {
    if (e.key !== "Enter") return;
    if (e.isComposing) return; // 変換確定のEnterはそのまま通す
    if (e.shiftKey) return; // Shift+Enterは改行
    e.preventDefault();
    send();
}

onUnmounted(() => {
    stopPolling();
});

// 新しいメッセージが来たら下にスクロール
watch(
    () => props.chatMessages.length,
    async () => {
        await nextTick();
        bottomRef.value?.scrollIntoView({ behavior: "smooth" });
    },
);

const isWaitingForAi = computed(() => form.processing || isPolling.value);

const switchingCharacter = ref(false);
const switchingModel = ref(false);
const assistantAvatarFailed = ref(false);
watch(
    () => [props.chatThread?.id, props.chatThread?.character],
    () => {
        assistantAvatarFailed.value = false;
    },
    { deep: true }
);
function setThreadCharacter(characterValue: string) {
    if (!props.chatThread || switchingCharacter.value) return;
    switchingCharacter.value = true;
    router.patch(
        route("books.threads.update", {
            book: props.bookId,
            thread: props.chatThread.id,
        }),
        { character: characterValue },
        { preserveScroll: true, onFinish: () => { switchingCharacter.value = false; } }
    );
}
function setThreadModel(modelId: string | null) {
    if (!props.chatThread || switchingModel.value) return;
    switchingModel.value = true;
    router.patch(
        route("books.threads.update", {
            book: props.bookId,
            thread: props.chatThread.id,
        }),
        { model: modelId },
        { preserveScroll: true, onFinish: () => { switchingModel.value = false; } }
    );
}

function playMessage(m: { id: string; content?: string; role: string }) {
    if (m.role !== "assistant" || !m.content?.trim() || !ttsPlayer || !ttsEnabled.value) return;
    const character = props.chatThread?.character ?? "zundamon";
    ttsPlayer.playText(m.content, {
        messageId: m.id,
        ttsModel: ttsPlayer.ttsModel.value,
        speed: ttsPlayer.defaultSpeed * ttsPlayer.playbackRate.value,
        character,
    });
}

const prevMessageCountForAutoRead = ref(props.chatMessages.length);
watch(
    () => props.chatMessages.length,
    (len) => {
        if (
            ttsPlayer &&
            ttsEnabled.value &&
            autoReadAloud.value &&
            len > prevMessageCountForAutoRead.value
        ) {
            const last = props.chatMessages[props.chatMessages.length - 1];
            if (last?.role === "assistant" && last?.content?.trim()) {
                nextTick(() => {
                    playMessage(last);
                });
            }
        }
        prevMessageCountForAutoRead.value = len;
    }
);
</script>

<template>
    <div class="flex flex-col sm:flex-row gap-4">
        <!-- スレッド一覧（他AIサービスのように会話を分けて保持） -->
        <aside class="w-full sm:w-56 shrink-0 space-y-2">
            <Link
                :href="route('books.show', { book: bookId, tab: 'chat' })"
                class="flex items-center gap-2 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-sm font-medium text-amber-800 hover:bg-amber-100"
            >
                <span aria-hidden="true">+</span>
                新しいチャット
            </Link>
            <div v-if="chatThreads.length > 0" class="space-y-1">
                <Link
                    v-for="t in chatThreads"
                    :key="t.id"
                    :href="
                        route('books.show', {
                            book: bookId,
                            tab: 'chat',
                            thread: t.id,
                        })
                    "
                    class="block rounded-xl border px-3 py-2 text-sm truncate"
                    :class="
                        chatThread && chatThread.id === t.id
                            ? 'border-amber-400 bg-amber-50 text-stone-900'
                            : 'border-stone-200 text-stone-600 hover:bg-stone-50'
                    "
                >
                    {{ t.title }}
                </Link>
            </div>
            <!-- このスレッドのキャラ選択（スレッド選択時のみ表示） -->
            <div v-if="chatThread && selectableCharacters.length > 0" class="pt-3 border-t border-stone-200 space-y-2">
                <p class="text-xs font-medium text-stone-500">このスレッドのキャラ</p>
                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="opt in selectableCharacters"
                        :key="opt.value"
                        type="button"
                        class="flex items-center gap-1.5 rounded-full border px-2.5 py-1.5 text-xs transition"
                        :class="
                            chatThread.character === opt.value
                                ? 'border-amber-400 bg-amber-50 text-amber-900'
                                : 'border-stone-200 text-stone-600 hover:bg-stone-50'
                        "
                        :disabled="switchingCharacter"
                        :title="opt.shortDescription"
                        @click="setThreadCharacter(opt.value)"
                    >
                        <img
                            v-if="opt.iconUrl"
                            :src="opt.iconUrl"
                            :alt="opt.label"
                            class="h-6 w-6 rounded-full object-cover bg-stone-100"
                            @error="($event.target as HTMLImageElement).style.display = 'none'"
                        />
                        <span v-else class="h-6 w-6 rounded-full bg-stone-200 flex items-center justify-center text-[10px]">{{ opt.label.charAt(0) }}</span>
                        <span>{{ opt.label }}</span>
                    </button>
                </div>
            </div>
        </aside>

        <div class="min-w-0 flex-1 space-y-4">
            <!-- Flash -->
            <div
                v-if="flash.success"
                class="rounded-xl border p-3 text-sm text-green-700"
            >
                {{ flash.success }}
            </div>
            <div
                v-if="flash.error"
                class="rounded-xl border p-3 text-sm text-red-700"
            >
                {{ flash.error }}
            </div>

            <!-- Messages -->
            <div class="space-y-3">
                <div v-for="m in chatMessages" :key="m.id">
                    <!-- AI メッセージ（キャラアイコン・名前反映） -->
                    <div
                        v-if="m.role === 'assistant'"
                        class="flex gap-3 justify-start"
                    >
                        <div class="flex-shrink-0 relative">
                            <img
                                v-if="currentCharacterInfo.iconUrl"
                                :src="currentCharacterInfo.iconUrl"
                                :alt="currentCharacterInfo.label"
                                class="h-12 w-12 rounded-full object-cover bg-stone-100"
                                :class="{ 'opacity-0': assistantAvatarFailed }"
                                @error="assistantAvatarFailed = true"
                            />
                            <div
                                class="h-12 w-12 rounded-full bg-stone-200 flex items-center justify-center text-sm text-stone-600 absolute inset-0"
                                :class="currentCharacterInfo.iconUrl && !assistantAvatarFailed ? 'pointer-events-none invisible' : ''"
                            >
                                {{ currentCharacterInfo.label.charAt(0) }}
                            </div>
                        </div>
                        <div
                            class="max-w-[80%] rounded-2xl border border-stone-200 bg-gray-50 p-3"
                        >
                            <div class="mb-1 flex items-center justify-between gap-2">
                                <span class="text-xs text-gray-500">{{ currentCharacterInfo.label }}</span>
                                <button
                                    v-if="ttsPlayer && ttsEnabled && m.content?.trim()"
                                    type="button"
                                    class="inline-flex items-center gap-1 text-stone-400 hover:text-amber-600 transition min-w-[2.5rem] justify-end"
                                    :disabled="ttsPlayer.playingMessageId?.value === m.id || ttsPlayer.loadingMessageId?.value === m.id"
                                    :title="ttsPlayer.playingMessageId?.value === m.id ? '再生中' : '読み上げ'"
                                    @click="playMessage(m)"
                                >
                                    <span v-if="ttsPlayer.loadingMessageId?.value === m.id" class="inline-flex items-center gap-1 text-[10px] text-amber-600">
                                        <span class="inline-block h-3 w-3 animate-spin rounded-full border-2 border-stone-300 border-t-amber-500" />
                                        準備中
                                    </span>
                                    <span v-else-if="ttsPlayer.playingMessageId?.value === m.id" aria-hidden="true">🔊</span>
                                    <span v-else aria-hidden="true">🔊</span>
                                </button>
                            </div>
                            <pre
                                class="whitespace-pre-wrap text-sm leading-relaxed"
                            >{{ m.content }}</pre>
                        </div>
                    </div>

                    <!-- ユーザーメッセージ -->
                    <div v-else class="flex gap-3 justify-end">
                        <div
                            class="max-w-[80%] rounded-2xl border border-amber-200 bg-amber-50 p-3"
                        >
                            <div class="mb-1 text-xs text-gray-500">You</div>
                            <pre
                                class="whitespace-pre-wrap text-sm leading-relaxed"
                                >{{ m.content }}
                        </pre
                            >
                        </div>
                        <div class="flex-shrink-0">
                            <img
                                v-if="currentUserProfilePhotoUrl"
                                :src="currentUserProfilePhotoUrl"
                                alt="Your avatar"
                                class="h-12 w-12 rounded-full object-cover"
                            />
                            <div
                                v-else
                                class="h-12 w-12 rounded-full bg-stone-300 flex items-center justify-center text-sm text-stone-700"
                            >
                                {{ currentUserInitial }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- AI thinking（送信直後 or ポーリング中） -->
                <div
                    v-if="isWaitingForAi"
                    class="rounded-2xl border p-3 text-sm text-gray-500 italic flex items-center gap-2"
                >
                    <span
                        class="inline-block h-4 w-4 animate-spin rounded-full border-2 border-gray-300 border-t-amber-500"
                    />
                    {{
                        form.processing
                            ? "送信中…"
                            : "AIが応答を生成しています…"
                    }}
                </div>

                <div
                    v-if="chatMessages.length === 0 && !isWaitingForAi"
                    class="text-sm text-gray-600"
                >
                    まだ会話がありません。下から送信して開始できます。
                </div>

                <div ref="bottomRef"></div>
            </div>

            <!-- 読み上げ・モデル設定（送信エリア上） -->
            <div class="rounded-xl border border-stone-200 bg-stone-50/50 px-4 py-3 space-y-3">
                <!-- 読み上げ -->
                <div v-if="ttsPlayer" class="flex flex-wrap items-center gap-x-4 gap-y-2 text-xs">
                    <label class="flex items-center gap-1.5 shrink-0">
                        <input v-model="ttsEnabled" type="checkbox" class="rounded border-stone-300 text-amber-600" />
                        <span class="text-stone-600">読み上げ</span>
                    </label>
                    <template v-if="ttsEnabled">
                        <span class="text-stone-400">|</span>
                        <div class="flex items-center gap-1.5">
                            <span class="text-stone-500 shrink-0">モード</span>
                            <button
                                v-for="opt in (props.ttsConfig?.backendOptions ?? [])"
                                :key="opt.id"
                                type="button"
                                class="rounded px-2 py-1 text-[11px] transition"
                                :class="ttsPlayer.ttsBackend?.value === opt.id ? 'bg-amber-100 text-amber-800' : 'text-stone-500 hover:bg-stone-100'"
                                @click="ttsPlayer.ttsBackend.value = opt.id"
                            >
                                {{ opt.label }}
                            </button>
                        </div>
                        <span class="text-stone-400">|</span>
                        <div class="flex items-center gap-1.5">
                            <span class="text-stone-500 shrink-0">品質</span>
                            <button
                                v-for="q in ttsPlayer.qualityOptions"
                                :key="q.id"
                                type="button"
                                class="rounded px-2 py-1 text-[11px] transition"
                                :class="ttsPlayer.ttsModel?.value === q.model ? 'bg-amber-100 text-amber-800' : 'text-stone-500 hover:bg-stone-100'"
                                @click="ttsPlayer.ttsModel.value = q.model"
                            >
                                {{ q.label }}
                            </button>
                        </div>
                        <span class="text-stone-400">|</span>
                        <div class="flex items-center gap-1.5">
                            <span class="text-stone-500 shrink-0">速度</span>
                            <input
                                v-model.number="ttsPlaybackRateDisplay"
                                type="range"
                                min="0.5"
                                max="2"
                                step="0.1"
                                class="w-20 h-1.5 rounded accent-amber-500"
                            />
                            <span class="text-stone-500 w-6 text-[11px]">{{ ttsPlaybackRateDisplay.toFixed(1) }}</span>
                        </div>
                        <label class="flex items-center gap-1.5 shrink-0">
                            <input v-model="autoReadAloud" type="checkbox" class="rounded border-stone-300 text-amber-600" />
                            <span class="text-stone-500">自動で読む</span>
                        </label>
                    </template>
                </div>
                <!-- 利用モデル（スレッド選択時） -->
                <div v-if="chatThread && chatModelOptions.length > 0" class="flex flex-wrap items-center gap-x-3 gap-y-1.5 text-xs border-t border-stone-200 pt-2">
                    <span class="text-stone-500 shrink-0">利用モデル</span>
                    <button
                        v-for="opt in chatModelOptions"
                        :key="opt.id"
                        type="button"
                        class="rounded px-2 py-1 text-[11px] transition"
                        :class="(chatThread.model ?? '') === opt.id ? 'bg-amber-100 text-amber-800' : 'text-stone-500 hover:bg-stone-100'"
                        :disabled="switchingModel"
                        :title="opt.model"
                        @click="setThreadModel(opt.id)"
                    >
                        {{ opt.label }}
                    </button>
                    <button
                        type="button"
                        class="rounded px-2 py-1 text-[11px] transition"
                        :class="chatThread.model == null || chatThread.model === '' ? 'bg-amber-100 text-amber-800' : 'text-stone-500 hover:bg-stone-100'"
                        :disabled="switchingModel"
                        title="デフォルト"
                        @click="setThreadModel(null)"
                    >
                        デフォルト
                    </button>
                </div>
            </div>

            <!-- Composer -->
            <div class="rounded-2xl border p-4 space-y-3">
                <textarea
                    ref="textareaRef"
                    v-model="form.content"
                    class="w-full rounded-xl border p-3 text-sm min-h-[140px]"
                    placeholder="この本について質問してみよう（例：この作品の主題は？／この章の要点は？）"
                    :disabled="isWaitingForAi"
                    @keydown="onKeydown"
                />
                <InputError :message="form.errors.content" />

                <PrimaryButton
                    class="w-full h-10 justify-center"
                    :disabled="isWaitingForAi || !form.content.trim()"
                    @click="send"
                >
                    {{
                        form.processing
                            ? "送信中…"
                            : isPolling
                              ? "応答待ち…"
                              : "送信"
                    }}
                </PrimaryButton>
            </div>
        </div>
    </div>
</template>
