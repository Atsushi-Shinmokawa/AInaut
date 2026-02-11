<script setup lang="ts">
import { computed } from "vue";
import { usePage, Link } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import HighlightsSection from "@/Pages/Books/HighlightsSection.vue";
import DocumentSection from "@/Pages/Books/DocumentSection.vue";
import ChatSection from "@/Pages/Books/ChatSection.vue";
import SummarySection from "@/Pages/Books/SummarySection.vue";

const page = usePage();

const tab = computed(() => {
    return new URLSearchParams(window.location.search).get("tab") ?? "overview";
});

const props = defineProps<{
    book: { id: string; title: string };
    highlights: any[];
    orphanHighlights: any[];
    document: any | null;
    chunksPreview: any[];
    chatThreads: { id: string; title: string; updated_at: string }[];
    chatThread: { id: string } | null;
    chatMessages: any[];
    latestSummary: any | null;
}>();
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <Link
                    :href="route('dashboard')"
                    class="text-stone-500 hover:text-stone-700 transition"
                    title="ホームへ"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                </Link>
                <h1 class="text-xl font-semibold leading-tight text-stone-800 truncate max-w-[min(50vw,24rem)]" :title="book.title">
                    {{ book.title }}
                </h1>
            </div>
        </template>

        <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
            <!-- タブ -->
            <div class="flex gap-4 border-b border-stone-200 mb-6 pb-2">
                <Link
                    :href="route('books.show', book.id)"
                    :class="tab === 'overview' ? 'font-bold' : ''"
                >
                    概要
                </Link>
                <Link
                    :href="
                        route('books.show', { book: book.id, tab: 'highlights' })
                    "
                    :class="tab === 'highlights' ? 'font-bold' : ''"
                >
                    Highlights
                </Link>
                <Link
                    :href="route('books.show', { book: book.id, tab: 'document' })"
                    class="flex items-center gap-2"
                >
                    本文
                    <span
                        class="inline-flex items-center rounded-full px-2 py-0.5 text-xs"
                        :class="
                            document
                                ? 'bg-green-100 text-green-800'
                                : 'bg-gray-100 text-gray-700'
                        "
                    >
                        {{ document ? "あり" : "なし" }}
                    </span>
                </Link>
                <Link
                    :href="
                        route('books.show', {
                            book: props.book.id,
                            tab: 'chat',
                            ...(props.chatThread ? { thread: props.chatThread.id } : {}),
                        })
                    "
                    :class="tab === 'chat' ? 'font-bold' : ''"
                >
                    💬 チャット
                </Link>
                <Link
                    :href="
                        route('books.show', { book: props.book.id, tab: 'summary' })
                    "
                    :class="tab === 'summary' ? 'font-bold' : ''"
                >
                    🧠 要約
                </Link>
            </div>

            <!-- 中身 -->
            <div v-if="tab === 'highlights'">
                <HighlightsSection
                    :highlights="highlights"
                    :orphans="orphanHighlights"
                    :book-id="book.id"
                />
            </div>
            <div v-else-if="tab === 'document'">
                <DocumentSection
                    :document="document"
                    :chunks-preview="chunksPreview"
                    :book-id="book.id"
                />
            </div>
            <div v-else-if="tab === 'chat'">
                <ChatSection
                    :book-id="props.book.id"
                    :chat-threads="props.chatThreads"
                    :chat-thread="props.chatThread"
                    :chat-messages="props.chatMessages"
                />
            </div>
            <div v-else-if="tab === 'summary'">
                <SummarySection
                    :book-id="props.book.id"
                    :latest-summary="latestSummary"
                />
                <div>要約（準備中）</div>
            </div>
            <div v-else>
                <!-- overview -->
                <!-- 既存の概要表示 -->
            </div>
        </div>
    </AuthenticatedLayout>
</template>
