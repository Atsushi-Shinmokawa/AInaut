<script setup lang="ts">
import { computed } from "vue";
import { usePage, Link } from "@inertiajs/vue3";
import HighlightsSection from "@/Pages/Books/HighlightsSection.vue";
import DocumentSection from "@/Pages/Books/DocumentSection.vue";

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
}>();
</script>

<template>
    <div>
        <!-- タブ -->
        <div class="flex gap-4 border-b mb-6">
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
                :href="route('books.show', { book: book.id, tab: 'chat' })"
                :class="tab === 'chat' ? 'font-bold' : ''"
            >
                💬 チャット
            </Link>

            <Link
                :href="route('books.show', { book: book.id, tab: 'summary' })"
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
            <!-- ChatSection を置く -->
            <div>チャット（準備中）</div>
        </div>

        <div v-else-if="tab === 'summary'">
            <!-- SummarySection を置く -->
            <div>要約（準備中）</div>
        </div>

        <div v-else>
            <!-- overview -->
            <!-- 既存の概要表示 -->
        </div>
    </div>
</template>
