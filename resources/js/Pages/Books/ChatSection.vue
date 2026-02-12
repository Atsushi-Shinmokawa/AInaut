<script setup lang="ts">
import { computed, ref, watch, nextTick, onUnmounted } from "vue";
import { useForm, usePage, router } from "@inertiajs/vue3";
import { Link } from "@inertiajs/vue3";
import InputError from "@/Components/InputError.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";

const props = withDefaults(
    defineProps<{
        bookId: string;
        chatThreads?: { id: string; title: string; updated_at: string }[];
        chatThread?: { id: string } | null;
        chatMessages: any[];
    }>(),
    { chatThreads: () => [], chatThread: null }
);

const page = usePage();
const flash = computed(() => (page.props as any).flash ?? {});
const authUser = computed(() => (page.props as any).auth?.user ?? null);
const currentUserProfilePhotoUrl = computed<string | null>(
    () => authUser.value?.profile_photo_url ?? null
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
                    ? route("books.chat.status", { book: props.bookId, thread: props.chatThread.id })
                    : route("books.chat.status", { book: props.bookId });
            const res = await fetch(statusUrl, {
                headers: { Accept: "application/json", "X-Requested-With": "XMLHttpRequest" },
            });
            const data = (await res.json()) as { messageCount: number; lastMessageRole: string | null };
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
    if (e.shiftKey) return;   // Shift+Enterは改行
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
    }
);

const isWaitingForAi = computed(() => form.processing || isPolling.value);
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
                    :href="route('books.show', { book: bookId, tab: 'chat', thread: t.id })"
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
                <!-- AI メッセージ -->
                <div
                    v-if="m.role === 'assistant'"
                    class="flex gap-3 justify-start"
                >
                    <!-- （将来のキャラアイコン用の枠。いまは丸背景のみ） -->
                    <div class="flex-shrink-0">
                        <div
                            class="h-12 w-12 rounded-full bg-stone-200 flex items-center justify-center text-sm text-stone-600"
                        >
                            AI
                        </div>
                    </div>
                    <div
                        class="max-w-[80%] rounded-2xl border border-stone-200 bg-gray-50 p-3"
                    >
                        <div class="mb-1 text-xs text-gray-500">AI</div>
                        <pre
                            class="whitespace-pre-wrap text-sm leading-relaxed"
                        >
{{ m.content }}
                        </pre>
                    </div>
                </div>

                <!-- ユーザーメッセージ -->
                <div
                    v-else
                    class="flex gap-3 justify-end"
                >
                    <div
                        class="max-w-[80%] rounded-2xl border border-amber-200 bg-amber-50 p-3"
                    >
                        <div class="mb-1 text-xs text-gray-500">You</div>
                        <pre
                            class="whitespace-pre-wrap text-sm leading-relaxed"
                        >
{{ m.content }}
                        </pre>
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
                <span class="inline-block h-4 w-4 animate-spin rounded-full border-2 border-gray-300 border-t-amber-500" />
                {{ form.processing ? "送信中…" : "AIが応答を生成しています…" }}
            </div>

            <div
                v-if="chatMessages.length === 0 && !isWaitingForAi"
                class="text-sm text-gray-600"
            >
                まだ会話がありません。下から送信して開始できます。
            </div>

            <div ref="bottomRef"></div>
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
                {{ form.processing ? "送信中…" : isPolling ? "応答待ち…" : "送信" }}
            </PrimaryButton>
        </div>
        </div>
    </div>
</template>
