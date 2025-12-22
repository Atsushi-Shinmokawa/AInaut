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
        </div>

        <!-- 中身 -->
        <div v-if="tab === 'highlights'">
            <HighlightsSection
                :highlights="highlights"
                :orphans="orphanHighlights"
                :book-id="book.id"
            />
        </div>

        <div v-else>
            <!-- 既存の概要表示 -->
        </div>
    </div>
</template>
