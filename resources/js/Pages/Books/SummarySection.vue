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

const form = useForm({});

function generate() {
    form.post(route("books.summary.generate", { book: props.bookId }));
}
</script>

<template>
    <div class="space-y-4">
        <PrimaryButton :disabled="form.processing" @click="generate">
            {{ form.processing ? "生成中..." : "要約を生成" }}
        </PrimaryButton>

        <div v-if="latestSummary" class="prose max-w-none">
            <h3 class="text-lg font-bold">最新の要約</h3>
            <pre class="whitespace-pre-wrap">{{ latestSummary.content }}</pre>
        </div>
        <div v-else class="text-gray-500">まだ要約はありません。</div>

        <div v-if="flash?.error" class="text-red-600">{{ flash.error }}</div>
        <div v-if="flash?.success" class="text-green-600">
            {{ flash.success }}
        </div>
    </div>
</template>
