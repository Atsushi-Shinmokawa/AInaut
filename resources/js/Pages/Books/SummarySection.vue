<script setup lang="ts">
import { computed, ref, watch, nextTick } from "vue";
import { useForm, usePage } from "@inertiajs/vue3";
import PrimaryButton from "@/Components/PrimaryButton.vue";

const props = defineProps<{
    bookId: string;
    latestSummary: any | null;
}>();

const page = usePage();
const flash = computed(() => (page.props as any).flash ?? {});
const bottomRef = ref<HTMLElement | null>(null);

const form = useForm({});

function generate() {
    form.post(route("books.summary.generate", { book: props.bookId }), {
        preserveScroll: true,
    });
}

// 要約が更新されたら下へスクロール
watch(
    () => props.latestSummary,
    async () => {
        await nextTick();
        bottomRef.value?.scrollIntoView({ behavior: "smooth" });
    }
);
</script>

<template>
    <div class="space-y-4">
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

        <div class="rounded-2xl border p-4 space-y-4">
            <!-- Generate Button -->
            <PrimaryButton
                class="w-full h-10 justify-center"
                :disabled="form.processing"
                @click="generate"
            >
                {{ form.processing ? "AIが要約しています..." : "AI要約を生成" }}
            </PrimaryButton>

            <!-- Generating -->
            <div
                v-if="form.processing"
                class="rounded-xl border p-3 text-sm text-gray-500 italic"
            >
                本文・ハイライトを読み取り、要点を整理しています…
            </div>

            <!-- Summary -->
            <div v-else-if="latestSummary" class="space-y-3">
                <div
                    class="flex items-center justify-between text-xs text-gray-500"
                >
                    <span> 最新要約（{{ latestSummary.model_name }}） </span>
                    <span
                        class="rounded-full bg-green-100 px-2 py-0.5 text-green-800"
                    >
                        最新
                    </span>
                </div>

                <pre class="whitespace-pre-wrap text-sm leading-relaxed">
    {{ latestSummary.content }}
                    </pre
                >

                <div class="text-xs text-gray-500">
                    ※ この要約をもとに、チャットで質問できます
                </div>
            </div>

            <!-- Empty -->
            <div v-else class="text-sm text-gray-600">
                まだ要約がありません。上のボタンで生成できます。
            </div>

            <div ref="bottomRef"></div>
        </div>
    </div>
</template>
