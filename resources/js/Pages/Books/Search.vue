<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, useForm, router } from "@inertiajs/vue3";

// Propsでバックエンドからのデータを受け取る
// books: 検索結果の配列 (BookDto構造)
const props = defineProps({
    books: {
        type: Array,
        default: () => [],
    },
    // 検索ワードを保持して画面に再表示するため
    filters: {
        type: Object,
        default: () => ({ q: "" }),
    },

    hasSearched: {
        type: Boolean,
        default: false,
    },
});

// 検索用フォーム（GETリクエスト）
// InertiaのuseFormを使うと、loading状態の管理が楽になります
const searchForm = useForm({
    q: props.filters.q || "",
});

const submitSearch = () => {
    // getリクエストで検索。preserveState: true にすることで
    // 検索中もフォーカスが外れたりしないようにする
    searchForm.get(route("books.search"), {
        preserveState: true,
        preserveScroll: true,
    });
};

// 書籍登録処理
const storeBook = (book) => {
    if (!confirm(`「${book.title}」を本棚に追加しますか？`)) return;

    // POSTリクエストでバックエンドの store メソッドを叩く
    router.post(
        route("books.store"),
        { isbn: book.isbn },
        {
            onSuccess: () => {
                // 成功時の処理（必要ならトースト通知などを出す場所）
                // 今回は簡易的にアラートか、InertiaがFlashメッセージを処理してくれます
                console.log("Saved successfully");
            },
            onError: (errors) => {
                console.error(errors);
                alert("保存に失敗しました。");
            },
        }
    );
};
</script>

<template>
    <Head title="書籍検索" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                書籍を探す
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div
                    class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6"
                >
                    <form
                        @submit.prevent="submitSearch"
                        class="flex gap-4 mb-8"
                    >
                        <input
                            type="text"
                            v-model="searchForm.q"
                            placeholder="ISBN または 書籍名を入力..."
                            class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                        <button
                            type="submit"
                            :disabled="searchForm.processing"
                            class="bg-indigo-600 text-white px-6 py-2 rounded-md hover:bg-indigo-700 disabled:opacity-50 transition"
                        >
                            <span v-if="searchForm.processing">検索中...</span>
                            <span v-else>検索</span>
                        </button>
                    </form>

                    <div
                        v-if="props.books.length > 0"
                        class="grid grid-cols-1 md:grid-cols-2 gap-6"
                    >
                        <div
                            v-for="book in props.books"
                            :key="book.isbn"
                            class="border rounded-lg p-4 flex gap-4 hover:bg-gray-50 transition"
                        >
                            <div class="w-24 flex-shrink-0">
                                <img
                                    :src="
                                        book.thumbnail ??
                                        'https://placehold.co/100x150?text=No+Image'
                                    "
                                    alt="cover"
                                    class="w-full h-auto shadow-sm"
                                />
                            </div>

                            <div class="flex-1 flex flex-col justify-between">
                                <div>
                                    <h3 class="font-bold text-lg mb-1">
                                        {{ book.title }}
                                    </h3>
                                    <p class="text-sm text-gray-600 mb-2">
                                        {{ book.authors.join(", ") }}
                                    </p>
                                    <p class="text-xs text-gray-400">
                                        ISBN: {{ book.isbn }}
                                    </p>
                                </div>

                                <div class="mt-4 text-right">
                                    <button
                                        @click="storeBook(book)"
                                        class="text-sm bg-gray-800 text-white px-4 py-2 rounded hover:bg-gray-700 transition"
                                    >
                                        本棚に追加
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div
                        v-else-if="props.hasSearched && !searchForm.processing"
                        class="text-center text-gray-500 mt-10"
                    >
                        該当する書籍が見つかりませんでした。
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
