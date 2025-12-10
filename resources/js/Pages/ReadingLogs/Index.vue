<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Modal from "@/Components/Modal.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import { Head, router, useForm } from "@inertiajs/vue3";
import { ref } from "vue";

// 🔹 読書メモの型
type ReadingNote = {
    id: string;
    content: string;
    page: number | null;
    created_at: string;
};

// 🔹 読書ログ（メモ込み）の型
type ReadingLog = {
    id: string;
    status: string;
    added_at: string; // ← created_at をフォーマットしたもの
    book: {
        id: string;
        title: string;
        author: string | null;
    };
    notes: ReadingNote[]; // ← メモ一覧
};

const props = defineProps<{
    readingLogs: ReadingLog[];
    statuses: string[];
}>();

// 🔹 ステータス選択肢（表示用）
const statusOptions = [
    { value: "want_to_read", label: "読みたい" },
    { value: "reading", label: "読書中" },
    { value: "completed", label: "読了" },
];

// 🔹 ステータス更新
const updateStatus = (logId: string, status: string) => {
    router.put(
        route("reading-logs.update", logId),
        { status },
        { preserveScroll: true }
    );
};

// 🔹 読書ログ削除
const deleteLog = (logId: string) => {
    if (!confirm("本棚から削除してよろしいですか？")) return;

    router.delete(route("reading-logs.destroy", logId), {
        preserveScroll: true,
    });
};

// ==== ここからメモ用の状態・処理 ====

// 選択中の読書ログ
const selectedLog = ref<ReadingLog | null>(null);
const showNoteModal = ref(false);

const openNoteModal = (log: ReadingLog) => {
    selectedLog.value = log;
    showNoteModal.value = true;
};

const closeNoteModal = () => {
    showNoteModal.value = false;
    selectedLog.value = null;
    form.reset();
};

// メモ追加用フォーム
const form = useForm<{
    content: string;
    page_number: number | null;
}>({
    content: "",
    page_number: null,
});

// メモ追加
const submitNote = () => {
    if (!selectedLog.value) return;

    form.post(route("reading-notes.store", selectedLog.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
        },
    });
};

// メモ削除
const deleteNote = (logId: string, noteId: string) => {
    if (!confirm("このメモを削除しますか？")) return;

    router.delete(
        route("reading-notes.destroy", {
            readingLog: logId,
            readingNote: noteId,
        }),
        {
            preserveScroll: true,
        }
    );
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="マイ本棚" />

        <template #header>
            <h2 class="text-xl font-semibold text-gray-800">マイ本棚</h2>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-5xl">
                <table
                    class="min-w-full divide-y divide-gray-200 bg-white shadow"
                >
                    <thead class="bg-gray-50">
                        <tr>
                            <th
                                class="px-4 py-2 text-left text-sm font-medium text-gray-500"
                            >
                                タイトル
                            </th>
                            <th
                                class="px-4 py-2 text-left text-sm font-medium text-gray-500"
                            >
                                著者
                            </th>
                            <th
                                class="px-4 py-2 text-left text-sm font-medium text-gray-500"
                            >
                                ステータス
                            </th>
                            <th
                                class="px-4 py-2 text-right text-sm font-medium text-gray-500"
                            >
                                操作
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-200">
                        <!-- 🔹 読書ログ1件ごとの行 -->
                        <tr v-for="log in props.readingLogs" :key="log.id">
                            <td class="px-4 py-2 text-sm text-gray-900">
                                <div class="font-medium">
                                    {{ log.book.title }}
                                </div>
                                <div class="text-xs text-gray-400">
                                    追加日: {{ log.added_at }}
                                </div>
                            </td>

                            <td class="px-4 py-2 text-sm text-gray-500">
                                {{ log.book.author || "（不明）" }}
                            </td>

                            <td class="px-4 py-2 text-sm">
                                <select
                                    class="rounded border-gray-300 text-sm"
                                    :value="log.status"
                                    @change="
                                            (e) =>
                                                updateStatus(
                                                    log.id,
                                                    (e.target as HTMLSelectElement)
                                                        .value,
                                                )
                                        "
                                >
                                    <option
                                        v-for="opt in statusOptions"
                                        :key="opt.value"
                                        :value="opt.value"
                                    >
                                        {{ opt.label }}
                                    </option>
                                </select>
                            </td>

                            <td class="px-4 py-2 text-right text-sm">
                                <div
                                    class="flex items-center justify-end gap-2"
                                >
                                    <button
                                        class="rounded bg-red-100 px-3 py-1 text-xs text-red-700"
                                        @click="deleteLog(log.id)"
                                    >
                                        削除
                                    </button>

                                    <button
                                        type="button"
                                        class="text-xs text-blue-600 underline"
                                        @click="openNoteModal(log)"
                                    >
                                        メモ
                                        <span v-if="log.notes.length">
                                            ({{ log.notes.length }})
                                        </span>
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <!-- 🔹 空のとき -->
                        <tr v-if="props.readingLogs.length === 0">
                            <td
                                class="px-4 py-6 text-center text-sm text-gray-500"
                                colspan="4"
                            >
                                まだ本棚に本がありません。
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 🔹 メモ用モーダル -->
        <Modal :show="showNoteModal" @close="closeNoteModal">
            <template #title>
                {{ selectedLog ? `${selectedLog.book.title} のメモ` : "メモ" }}
            </template>

            <div class="space-y-4">
                <!-- 既存メモ一覧 -->
                <div v-if="selectedLog">
                    <div
                        v-if="selectedLog.notes.length === 0"
                        class="text-sm text-gray-500"
                    >
                        まだメモがありません。
                    </div>

                    <ul v-else class="space-y-3 max-h-64 overflow-y-auto">
                        <li
                            v-for="note in selectedLog.notes"
                            :key="note.id"
                            class="rounded border border-gray-200 p-2"
                        >
                            <div class="mb-1 flex items-center justify-between">
                                <div class="text-xs text-gray-500">
                                    <span v-if="note.page">
                                        p.{{ note.page }} /
                                    </span>
                                    {{ note.created_at }}
                                </div>

                                <button
                                    type="button"
                                    class="text-xs text-red-600 underline"
                                    @click="deleteNote(selectedLog.id, note.id)"
                                >
                                    削除
                                </button>
                            </div>

                            <div
                                class="text-sm whitespace-pre-line text-gray-800"
                            >
                                {{ note.content }}
                            </div>
                        </li>
                    </ul>
                </div>

                <!-- メモ追加フォーム -->
                <form @submit.prevent="submitNote" class="space-y-2">
                    <div class="flex gap-2">
                        <input
                            v-model.number="form.page_number"
                            type="number"
                            min="1"
                            placeholder="ページ（任意）"
                            class="w-32 rounded border-gray-300 px-2 py-1 text-xs"
                        />

                        <div
                            v-if="form.errors.page_number"
                            class="text-xs text-red-600"
                        >
                            {{ form.errors.page_number }}
                        </div>
                    </div>

                    <div>
                        <textarea
                            v-model="form.content"
                            rows="3"
                            class="w-full rounded border-gray-300 px-2 py-1 text-sm"
                            placeholder="メモ内容"
                        ></textarea>
                        <div
                            v-if="form.errors.content"
                            class="text-xs text-red-600"
                        >
                            {{ form.errors.content }}
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <PrimaryButton
                            type="submit"
                            class="mt-1"
                            :disabled="form.processing"
                        >
                            メモを追加
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
