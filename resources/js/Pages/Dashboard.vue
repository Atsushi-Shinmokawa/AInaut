<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { usePage } from '@inertiajs/vue3';

interface BookSummary {
    id: string;
    title: string;
    cover_url: string | null;
    author_name: string | null;
}

interface ReadingLogItem {
    id: string;
    status: string;
    status_label: string;
    updated_at: string;
    book: BookSummary;
}

interface RecentNote {
    id: string;
    content: string;
    page_number: number | null;
    created_at: string;
    book_title: string;
    book_id: string | null;
    reading_log_id: string | null;
}

interface RecentHighlight {
    id: string;
    content: string;
    page: string | null;
    created_at: string | null;
    book_title: string;
    book_id: string | null;
}

defineProps<{
    stats: { completed: number; reading: number; want_to_read: number };
    recent_reading_logs: ReadingLogItem[];
    recent_notes: RecentNote[];
    recent_highlights: RecentHighlight[];
}>();

const page = usePage();
const userName = page.props.auth?.user?.name ?? 'ゲスト';
</script>

<template>
    <Head title="ダッシュボード" />

    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-xl font-semibold leading-tight text-stone-800">
                ダッシュボード
            </h1>
        </template>

        <div class="min-h-[60vh] space-y-8 py-6 sm:py-8">
            <!-- ウェルカム -->
            <section>
                <p class="text-stone-600">
                    <span class="font-medium text-stone-800">{{ userName }}</span>
                    さん、今日も読書を楽しみましょう。
                </p>
            </section>

            <!-- 読書統計カード -->
            <section>
                <h2 class="mb-4 text-sm font-medium uppercase tracking-wide text-stone-500">
                    読書の記録
                </h2>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div
                        class="flex items-center gap-4 rounded-2xl border border-stone-200/80 bg-white p-5 shadow-sm transition hover:shadow-md"
                    >
                        <div
                            class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600"
                        >
                            <svg
                                class="h-6 w-6"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M5 13l4 4L19 7"
                                />
                            </svg>
                        </div>
                        <div>
                            <p class="text-2xl font-bold tabular-nums text-stone-900">
                                {{ stats.completed }}
                            </p>
                            <p class="text-sm text-stone-500">読了</p>
                        </div>
                    </div>
                    <div
                        class="flex items-center gap-4 rounded-2xl border border-stone-200/80 bg-white p-5 shadow-sm transition hover:shadow-md"
                    >
                        <div
                            class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-600"
                        >
                            <svg
                                class="h-6 w-6"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"
                                />
                            </svg>
                        </div>
                        <div>
                            <p class="text-2xl font-bold tabular-nums text-stone-900">
                                {{ stats.reading }}
                            </p>
                            <p class="text-sm text-stone-500">読書中</p>
                        </div>
                    </div>
                    <div
                        class="flex items-center gap-4 rounded-2xl border border-stone-200/80 bg-white p-5 shadow-sm transition hover:shadow-md"
                    >
                        <div
                            class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-sky-100 text-sky-600"
                        >
                            <svg
                                class="h-6 w-6"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"
                                />
                            </svg>
                        </div>
                        <div>
                            <p class="text-2xl font-bold tabular-nums text-stone-900">
                                {{ stats.want_to_read }}
                            </p>
                            <p class="text-sm text-stone-500">読みたい</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- クイックアクション -->
            <section>
                <h2 class="mb-4 text-sm font-medium uppercase tracking-wide text-stone-500">
                    すぐできること
                </h2>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <Link
                        :href="route('books.search')"
                        class="group flex items-center gap-4 rounded-2xl border border-stone-200/80 bg-white p-5 shadow-sm transition hover:border-amber-200 hover:shadow-md"
                    >
                        <div
                            class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-600 transition group-hover:bg-amber-200"
                        >
                            <svg
                                class="h-6 w-6"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                                />
                            </svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="font-medium text-stone-900 group-hover:text-amber-800">
                                本を探す
                            </p>
                            <p class="text-sm text-stone-500">
                                ISBN・キーワードで書籍を検索して本棚に追加
                            </p>
                        </div>
                        <svg
                            class="h-5 w-5 shrink-0 text-stone-400 group-hover:text-amber-500"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 5l7 7-7 7"
                            />
                        </svg>
                    </Link>
                    <Link
                        :href="route('reading-logs.index')"
                        class="group flex items-center gap-4 rounded-2xl border border-stone-200/80 bg-white p-5 shadow-sm transition hover:border-amber-200 hover:shadow-md"
                    >
                        <div
                            class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-stone-100 text-stone-600 transition group-hover:bg-stone-200"
                        >
                            <svg
                                class="h-6 w-6"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"
                                />
                            </svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="font-medium text-stone-900 group-hover:text-stone-800">
                                マイ本棚
                            </p>
                            <p class="text-sm text-stone-500">
                                読書ログ・メモ・ハイライトを一覧で管理
                            </p>
                        </div>
                        <svg
                            class="h-5 w-5 shrink-0 text-stone-400 group-hover:text-stone-500"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 5l7 7-7 7"
                            />
                        </svg>
                    </Link>
                    <Link
                        :href="route('imports.kindle.create')"
                        class="group flex items-center gap-4 rounded-2xl border border-stone-200/80 bg-white p-5 shadow-sm transition hover:border-amber-200 hover:shadow-md"
                    >
                        <div
                            class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600 transition group-hover:bg-emerald-200"
                        >
                            <svg
                                class="h-6 w-6"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"
                                />
                            </svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="font-medium text-stone-900 group-hover:text-emerald-800">
                                Kindleハイライト
                            </p>
                            <p class="text-sm text-stone-500">
                                Kindleのメモをインポートして一元管理
                            </p>
                        </div>
                        <svg
                            class="h-5 w-5 shrink-0 text-stone-400 group-hover:text-emerald-500"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 5l7 7-7 7"
                            />
                        </svg>
                    </Link>
                </div>
            </section>

            <!-- 最近読んだ本 -->
            <section>
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-sm font-medium uppercase tracking-wide text-stone-500">
                        最近の読書
                    </h2>
                    <Link
                        v-if="recent_reading_logs.length > 0"
                        :href="route('reading-logs.index')"
                        class="text-sm font-medium text-amber-600 hover:text-amber-700"
                    >
                        すべて見る
                    </Link>
                </div>
                <div
                    v-if="recent_reading_logs.length > 0"
                    class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6"
                >
                    <Link
                        v-for="item in recent_reading_logs"
                        :key="item.id"
                        :href="route('books.show', { book: item.book.id })"
                        class="group block"
                    >
                        <div
                            class="aspect-[2/3] overflow-hidden rounded-xl bg-stone-200 shadow-sm transition group-hover:shadow-md"
                        >
                            <img
                                v-if="item.book.cover_url"
                                :src="item.book.cover_url"
                                :alt="item.book.title"
                                class="h-full w-full object-cover transition group-hover:scale-105"
                            />
                            <div
                                v-else
                                class="flex h-full w-full items-center justify-center text-stone-400"
                            >
                                <svg
                                    class="h-12 w-12"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="1.5"
                                        d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"
                                    />
                                </svg>
                            </div>
                        </div>
                        <p
                            class="mt-2 line-clamp-2 text-sm font-medium text-stone-800 group-hover:text-amber-700"
                        >
                            {{ item.book.title }}
                        </p>
                        <p class="mt-0.5 text-xs text-stone-500">
                            {{ item.status_label }} · {{ item.updated_at }}
                        </p>
                    </Link>
                </div>
                <div
                    v-else
                    class="rounded-2xl border border-dashed border-stone-200 bg-stone-50/50 px-6 py-12 text-center"
                >
                    <p class="text-stone-500">まだ読書ログがありません</p>
                    <Link
                        :href="route('books.search')"
                        class="mt-3 inline-block text-sm font-medium text-amber-600 hover:text-amber-700"
                    >
                        本を探して追加する →
                    </Link>
                </div>
            </section>

            <!-- 最近のメモ・ハイライト（2カラム） -->
            <section class="grid grid-cols-1 gap-8 lg:grid-cols-2">
                <!-- 最近のメモ -->
                <div>
                    <h2 class="mb-4 text-sm font-medium uppercase tracking-wide text-stone-500">
                        最近のメモ
                    </h2>
                    <div
                        v-if="recent_notes.length > 0"
                        class="space-y-3 rounded-2xl border border-stone-200/80 bg-white p-4 shadow-sm"
                    >
                        <Link
                            v-for="note in recent_notes"
                            :key="note.id"
                            :href="route('reading-logs.index')"
                            class="block rounded-xl border border-transparent p-3 transition hover:border-stone-200 hover:bg-stone-50/80"
                        >
                            <p class="line-clamp-2 text-sm text-stone-800">
                                {{ note.content }}
                            </p>
                            <p class="mt-2 text-xs text-stone-500">
                                {{ note.book_title }}
                                <span v-if="note.page_number"> · p.{{ note.page_number }}</span>
                                · {{ note.created_at }}
                            </p>
                        </Link>
                    </div>
                    <div
                        v-else
                        class="rounded-2xl border border-dashed border-stone-200 bg-stone-50/50 px-4 py-8 text-center"
                    >
                        <p class="text-sm text-stone-500">まだメモがありません</p>
                    </div>
                </div>

                <!-- 最近のハイライト -->
                <div>
                    <h2 class="mb-4 text-sm font-medium uppercase tracking-wide text-stone-500">
                        最近のハイライト
                    </h2>
                    <div
                        v-if="recent_highlights.length > 0"
                        class="space-y-3 rounded-2xl border border-stone-200/80 bg-white p-4 shadow-sm"
                    >
                        <Link
                            v-for="hl in recent_highlights"
                            :key="hl.id"
                            :href="
                                hl.book_id
                                    ? route('books.show', { book: hl.book_id })
                                    : route('reading-logs.index')
                            "
                            class="block rounded-xl border border-transparent p-3 transition hover:border-amber-100 hover:bg-amber-50/50"
                        >
                            <p class="line-clamp-2 text-sm text-stone-800">
                                {{ hl.content }}
                            </p>
                            <p class="mt-2 text-xs text-stone-500">
                                {{ hl.book_title }}
                                <span v-if="hl.page"> · {{ hl.page }}</span>
                                <span v-if="hl.created_at"> · {{ hl.created_at }}</span>
                            </p>
                        </Link>
                    </div>
                    <div
                        v-else
                        class="rounded-2xl border border-dashed border-stone-200 bg-stone-50/50 px-4 py-8 text-center"
                    >
                        <p class="text-sm text-stone-500">まだハイライトがありません</p>
                        <Link
                            :href="route('imports.kindle.create')"
                            class="mt-2 inline-block text-sm font-medium text-amber-600 hover:text-amber-700"
                        >
                            Kindleから取り込む →
                        </Link>
                    </div>
                </div>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
