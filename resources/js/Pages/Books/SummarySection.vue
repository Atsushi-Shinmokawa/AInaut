<script setup lang="ts">
import { computed } from "vue";
import { useForm, usePage } from "@inertiajs/vue3";
import PrimaryButton from "@/Components/PrimaryButton.vue";

const props = defineProps<{
    bookId: string;
    latestSummary: any | null;
}>();

const page = usePage();
const flash = computed(() => (page.props as any).flash ?? {});

const form = useForm({
    // 今回は追加パラメータ無し（将来: tone/length など入れられる）
});

function generate() {
    form.post(route("books.summary.generate", { book: props.bookId }), {
        preserveScroll: true,
    });
}
</script>

<template>
    <div class="space-y-4">
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

        <div class="rounded-2xl border p-4 space-y-3">
            <PrimaryButton
                class="w-full h-10 justify-center"
                :disabled="form.processing"
                @click="generate"
            >
                {{ form.processing ? "生成中..." : "AI要約を生成" }}
            </PrimaryButton>

            <div v-if="latestSummary" class="space-y-2">
                <div class="text-xs text-gray-500">
                    最新要約（{{ latestSummary.model_name }}）
                </div>

                <pre class="whitespace-pre-wrap text-sm leading-relaxed">{{
                    latestSummary.content
                }}</pre>
            </div>

            <div v-else class="text-sm text-gray-600">
                まだ要約がありません。上のボタンで生成できます。
            </div>
        </div>
    </div>
</template>
