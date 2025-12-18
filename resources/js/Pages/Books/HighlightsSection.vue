<script setup lang="ts">
import { computed, ref } from "vue";
import { router, Link } from "@inertiajs/vue3";

type Highlight = {
    id: string;
    content: string;
    page: number | null;
    location: number | null;
    created_at: string;
    title_raw?: string | null;
};

const props = defineProps<{
    highlights: Highlight[];
    orphans: Highlight[];
    bookId: string;
}>();

// ---- UI: 折りたたみ ----
const expanded = ref<Record<string, boolean>>({});
const PREVIEW_LEN = 220;

const isLong = (text: string) => text.length > PREVIEW_LEN;
const preview = (text: string) =>
    isLong(text) ? text.slice(0, PREVIEW_LEN) + "…" : text;

const toggle = (id: string) => {
    expanded.value[id] = !expanded.value[id];
};

// ---- actions ----
const deleteHighlight = (id: string) => {
    if (!confirm("このハイライトを削除しますか？")) return;
    router.delete(route("highlights.destroy", id), { preserveScroll: true });
};

const attachHighlight = (id: string) => {
    router.post(
        route("highlights.attach", id),
        { book_id: props.bookId },
        { preserveScroll: true }
    );
};

const hasHighlights = computed(() => props.highlights.length > 0);
</script>

<template>
    <div class="space-y-8">
        <!-- ✅ 紐づき済み -->
        <section>
            <div class="flex items-center justify-between">
                <h3 class="font-bold">この本のハイライト</h3>

                <!-- ✅ 取り込み導線（最小） -->
                <Link
                    :href="route('imports.kindle.create')"
                    class="text-sm text-indigo-600 hover:underline"
                >
                    Kindleハイライトを取り込む →
                </Link>
            </div>

            <!-- ✅ 空状態をAInaut寄りに -->
            <div
                v-if="!hasHighlights"
                class="mt-3 rounded border border-dashed p-4 text-gray-600"
            >
                <p class="font-medium">
                    まだ、この本の「残したい一節」がありません。
                </p>
                <p class="text-sm mt-1">
                    Kindleのハイライトを取り込むと、ここに「あなたが刺さった引用」が保存されます。
                </p>

                <div class="mt-3">
                    <Link
                        :href="route('imports.kindle.create')"
                        class="inline-flex items-center rounded bg-indigo-600 px-3 py-2 text-sm text-white hover:opacity-90"
                    >
                        取り込み画面へ
                    </Link>
                </div>
            </div>

            <!-- ✅ 一覧（カード） -->
            <div v-else class="mt-4 space-y-3">
                <div
                    v-for="h in highlights"
                    :key="h.id"
                    class="rounded border bg-white p-4 space-y-2"
                >
                    <div
                        class="text-xs text-gray-500 flex flex-wrap gap-x-3 gap-y-1"
                    >
                        <span v-if="h.page">p.{{ h.page }}</span>
                        <span v-if="h.location">loc.{{ h.location }}</span>
                        <span>{{ h.created_at }}</span>
                    </div>

                    <p class="whitespace-pre-wrap">
                        {{ expanded[h.id] ? h.content : preview(h.content) }}
                    </p>

                    <div class="flex items-center justify-between">
                        <button
                            v-if="isLong(h.content)"
                            type="button"
                            class="text-sm text-blue-600 hover:underline"
                            @click="toggle(h.id)"
                        >
                            {{ expanded[h.id] ? "折りたたむ" : "続きを読む" }}
                        </button>

                        <button
                            type="button"
                            class="text-sm text-red-600 hover:underline ml-auto"
                            @click="deleteHighlight(h.id)"
                        >
                            削除
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <!-- ✅ 未紐付け救済 -->
        <section v-if="orphans.length">
            <h3 class="font-bold text-orange-700">紐付け候補（未紐付け）</h3>
            <p class="text-sm text-gray-600 mt-1">
                取り込み時の「書名」が近いハイライトです。該当すれば、この本に紐付けられます。
            </p>

            <div class="mt-3 space-y-3">
                <div
                    v-for="h in orphans"
                    :key="h.id"
                    class="rounded border bg-orange-50 p-4 space-y-2"
                >
                    <!-- ✅ 判断材料 -->
                    <div v-if="h.title_raw" class="text-xs text-orange-800">
                        取り込み時の書名：<span class="font-medium">{{
                            h.title_raw
                        }}</span>
                    </div>

                    <p class="whitespace-pre-wrap">
                        {{ expanded[h.id] ? h.content : preview(h.content) }}
                    </p>

                    <div class="flex items-center justify-between">
                        <button
                            v-if="isLong(h.content)"
                            type="button"
                            class="text-sm text-blue-600 hover:underline"
                            @click="toggle(h.id)"
                        >
                            {{ expanded[h.id] ? "折りたたむ" : "続きを読む" }}
                        </button>

                        <button
                            type="button"
                            class="text-sm text-indigo-700 hover:underline ml-auto"
                            @click="attachHighlight(h.id)"
                        >
                            この本に紐付ける
                        </button>
                    </div>
                </div>
            </div>
        </section>
    </div>
</template>
