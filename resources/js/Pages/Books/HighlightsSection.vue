<script setup lang="ts">
import { router } from "@inertiajs/vue3";

const props = defineProps<{
    highlights: any[];
    orphans: any[];
    bookId: string;
}>();

const deleteHighlight = (id: string) => {
    if (!confirm("このハイライトを削除しますか？")) return;
    router.delete(route("highlights.destroy", id));
};

const attachHighlight = (id: string) => {
    router.post(route("highlights.attach", id), {
        book_id: props.bookId,
    });
};
</script>

<template>
    <div class="space-y-6">
        <!-- 紐づき済み -->
        <section>
            <h3 class="font-bold mb-2">この本のハイライト</h3>

            <p v-if="highlights.length === 0" class="text-gray-500">
                まだハイライトがありません
            </p>

            <div
                v-for="h in highlights"
                :key="h.id"
                class="border rounded p-4 space-y-2"
            >
                <p class="whitespace-pre-wrap">{{ h.content }}</p>

                <div class="text-right">
                    <button
                        class="text-sm text-red-600"
                        @click="deleteHighlight(h.id)"
                    >
                        削除
                    </button>
                </div>
            </div>
        </section>

        <!-- 未紐付け救済 -->
        <section v-if="orphans.length">
            <h3 class="font-bold mb-2 text-orange-600">
                紐付け候補（未紐付け）
            </h3>

            <div
                v-for="h in orphans"
                :key="h.id"
                class="border rounded p-4 bg-orange-50"
            >
                <p class="whitespace-pre-wrap">{{ h.content }}</p>

                <button
                    class="mt-2 text-sm text-indigo-600"
                    @click="attachHighlight(h.id)"
                >
                    この本に紐付ける
                </button>
            </div>
        </section>
    </div>
</template>
