<script setup lang="ts">
import { computed } from "vue";
import { Head, useForm } from "@inertiajs/vue3";

type Form = {
    raw_text: string;
};

const form = useForm<Form>({
    raw_text: "",
});

const canSubmit = computed(() => form.raw_text.trim().length >= 20);

function submit() {
    form.post(route("imports.kindle.preview"), {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Kindleハイライト取り込み" />

    <div class="mx-auto w-full max-w-3xl px-4 py-6 sm:py-10">
        <div class="mb-6">
            <h1 class="text-xl font-semibold sm:text-2xl">
                Kindleハイライト取り込み
            </h1>
            <p class="mt-2 text-sm text-gray-600">
                Kindleのハイライト（メール共有 / My
                Clippings.txt）を貼り付けて、プレビューで確認してから保存します。
            </p>
        </div>

        <div
            class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm sm:p-6"
        >
            <label class="block text-sm font-medium text-gray-700">
                取り込みテキスト
            </label>

            <textarea
                v-model="form.raw_text"
                class="mt-2 w-full rounded-xl border border-gray-300 bg-white px-3 py-3 text-sm leading-6 text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                :class="{
                    'border-red-400 focus:border-red-500 focus:ring-red-500':
                        form.errors.raw_text,
                }"
                placeholder="ここに Kindle のハイライト本文を貼り付けてください"
                rows="12"
                style="min-height: 260px"
            />

            <p v-if="form.errors.raw_text" class="mt-2 text-sm text-red-600">
                {{ form.errors.raw_text }}
            </p>

            <div
                class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="text-xs text-gray-500">
                    目安：20文字以上。長文でもOK（v1ではプレビュー最大200件まで表示）。
                </div>

                <button
                    type="button"
                    class="inline-flex h-11 w-full items-center justify-center rounded-xl bg-indigo-600 px-4 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:cursor-not-allowed disabled:opacity-50 sm:w-auto"
                    :disabled="!canSubmit || form.processing"
                    @click="submit"
                >
                    <span v-if="!form.processing">プレビューへ</span>
                    <span v-else>解析中...</span>
                </button>
            </div>

            <div class="mt-6 rounded-xl bg-gray-50 p-4">
                <h2 class="text-sm font-semibold text-gray-800">貼り付け例</h2>

                <div class="mt-3 space-y-3 text-xs text-gray-700">
                    <div class="rounded-lg border border-gray-200 bg-white p-3">
                        <div class="mb-1 font-semibold text-gray-800">
                            ✅ My Clippings.txt 形式（例）
                        </div>
                        <pre
                            class="whitespace-pre-wrap leading-5 text-gray-700"
                        >
    書名（著者）
    - ハイライト 位置No. 123-124 | 作成日: 2025年12月13日
    
    ここにハイライト本文
    
    ==========
                </pre
                        >
                    </div>

                    <div class="rounded-lg border border-gray-200 bg-white p-3">
                        <div class="mb-1 font-semibold text-gray-800">
                            ✅ メール共有テキスト（例）
                        </div>
                        <pre
                            class="whitespace-pre-wrap leading-5 text-gray-700"
                        >
    （書名）
    ここにハイライト本文
    
    ここに次のハイライト本文
                </pre
                        >
                    </div>

                    <p class="text-gray-500">
                        ※
                        v1は「まず動く」が優先です。実際のメール本文サンプルが1つあると、パース精度を一気に上げられます。
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
