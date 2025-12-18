<script setup lang="ts">
import { computed } from "vue";
import { usePage, Link } from "@inertiajs/vue3";
import HighlightsSection from "@/Pages/Books/HighlightsSection.vue";

const page = usePage();

const tab = computed(() => {
    return new URLSearchParams(window.location.search).get("tab") ?? "overview";
});

const props = defineProps<{
    book: { id: string; title: string };
    highlights: any[];
    orphanHighlights: any[];
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
