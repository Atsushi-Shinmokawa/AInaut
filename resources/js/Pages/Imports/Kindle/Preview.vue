<script setup lang="ts">
import { computed } from "vue";
import { Head, Link, useForm } from "@inertiajs/vue3";

type Item = {
    source: string;
    title_raw: string | null;
    location: string | null;
    page: string | null;
    highlighted_at: string | null;
    content: string;
    content_hash: string;
};

const props = defineProps<{
    raw_text: string;
    items: Item[];
    count: number;
}>();

type CommitForm = {
    items: Item[];
};

const form = useForm<CommitForm>({
    items: props.items ?? [],
});

const previewCount = computed(() => props.items?.length ?? 0);
const hasMoreThanPreview = computed(() => props.count > previewCount.value);

function commit() {
    form.post(route("imports.kindle.commit"), {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="取り込みプレビュー" />

    <div class="mx-auto w-full max-w-3xl px-4 py-6 sm:py-10">
        <div class="mb-4">
            <h1 class="text-xl font-semibold sm:text-2xl">
                取り込みプレビュー
            </h1>
            <p class="mt-2 text-sm text-gray-600">
                抽出件数：
                <span class="font-semibold text-gray-900">{{ count }}</span>
                件（プレビュー表示：
                <span class="font-semibold text-gray-900">{{
                    previewCount
                }}</span>
                件）
            </p>

            <div
                v-if="hasMoreThanPreview"
                class="mt-3 rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800"
            >
                件数が多いため、プレビューは先頭
                {{
                    previewCount
                }}
                件のみ表示しています（保存はこの表示分が対象です）。
                v1ではまずここまで。必要なら「全件保存」対応に拡張できます。
            </div>
        </div>

        <div
            class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm sm:p-6"
        >
            <div
                class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="text-sm text-gray-700">
                    内容をざっと確認して、問題なければ保存してください。
                </div>

                <div class="flex gap-2">
                    <Link
                        :href="route('imports.kindle.create')"
                        class="inline-flex h-10 items-center justify-center rounded-xl border border-gray-300 bg-white px-4 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50"
                    >
                        戻る
                    </Link>

                    <button
                        type="button"
                        class="inline-flex h-10 items-center justify-center rounded-xl bg-indigo-600 px-4 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:cursor-not-allowed disabled:opacity-50"
                        :disabled="form.processing || previewCount === 0"
                        @click="commit"
                    >
                        <span v-if="!form.processing">保存する</span>
                        <span v-else>保存中...</span>
                    </button>
                </div>
            </div>

            <div
                v-if="previewCount === 0"
                class="mt-6 rounded-xl border border-gray-200 bg-gray-50 p-4 text-sm text-gray-700"
            >
                抽出できるハイライトが見つかりませんでした。入力テキストの形式が違う可能性があります。
                <div class="mt-2">
                    <Link
                        :href="route('imports.kindle.create')"
                        class="font-semibold text-indigo-600 hover:underline"
                    >
                        取り込み画面に戻る
                    </Link>
                </div>
            </div>

            <div v-else class="mt-6 space-y-3">
                <div
                    v-for="(it, idx) in props.items"
                    :key="it.content_hash + '-' + idx"
                    class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm"
                >
                    <div class="flex flex-col gap-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span
                                class="rounded-full bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-700"
                            >
                                {{ it.source }}
                            </span>

                            <span
                                v-if="it.title_raw"
                                class="text-xs text-gray-600"
                            >
                                📘 {{ it.title_raw }}
                            </span>
                        </div>

                        <div class="text-xs text-gray-500">
                            <span v-if="it.page">p.{{ it.page }}</span>
                            <span v-if="it.page && it.location"> / </span>
                            <span v-if="it.location"
                                >loc {{ it.location }}</span
                            >
                            <span
                                v-if="
                                    (it.page || it.location) &&
                                    it.highlighted_at
                                "
                            >
                                /
                            </span>
                            <span v-if="it.highlighted_at">{{
                                it.highlighted_at
                            }}</span>
                        </div>
                    </div>

                    <div
                        class="mt-3 whitespace-pre-wrap text-sm leading-6 text-gray-900"
                    >
                        {{ it.content }}
                    </div>

                    <div class="mt-3 text-xs text-gray-400">
                        hash: {{ it.content_hash.slice(0, 10) }}...
                    </div>
                </div>
            </div>

            <p v-if="form.hasErrors" class="mt-4 text-sm text-red-600">
                保存に失敗しました。入力内容を見直してください。
            </p>
        </div>

        <!-- スマホで押しやすい“下部固定っぽい”保存バー -->
        <div class="sticky bottom-0 mt-6 pb-4">
            <div
                class="rounded-2xl border border-gray-200 bg-white/90 p-3 shadow-sm backdrop-blur"
            >
                <button
                    type="button"
                    class="inline-flex h-12 w-full items-center justify-center rounded-xl bg-indigo-600 px-4 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:cursor-not-allowed disabled:opacity-50"
                    :disabled="form.processing || previewCount === 0"
                    @click="commit"
                >
                    <span v-if="!form.processing"
                        >この内容で保存する（{{ previewCount }}件）</span
                    >
                    <span v-else>保存中...</span>
                </button>
            </div>
        </div>
    </div>
</template>
