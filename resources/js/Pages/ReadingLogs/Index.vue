<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, router } from "@inertiajs/vue3";

type ReadingLog = {
    id: string;
    status: string;
    book: {
        id: string;
        title: string;
        author?: string;
    };
};

const props = defineProps<{
    readingLogs: ReadingLog[];
    statuses: string[];
}>();

const statusOptions = [
    { value: "want_to_read", label: "読みたい" },
    { value: "reading", label: "読書中" },
    { value: "completed", label: "読了" },
];

const updateStatus = (logId: string, status: string) => {
    router.put(
        route("reading-logs.update", logId),
        { status }, // ← ここで data を渡す
        { preserveScroll: true }
    );
};

const deleteLog = (logId: string) => {
    if (!confirm("本棚から削除してよろしいですか？")) return;

    router.delete(route("reading-logs.destroy", logId), {
        preserveScroll: true,
    });
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
                            <th class="px-4 py-2" />
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-for="log in readingLogs" :key="log.id">
                            <td class="px-4 py-2 text-sm text-gray-900">
                                {{ log.book.title }}
                            </td>
                            <td class="px-4 py-2 text-sm text-gray-500">
                                {{ log.book.author }}
                            </td>
                            <td class="px-4 py-2 text-sm">
                                <select
                                    class="rounded border-gray-300 text-sm"
                                    :value="log.status"
                                    @change="(e) => updateStatus(log.id, (e.target as HTMLSelectElement).value)"
                                >
                                    <option
                                        v-for="opt in statusOptions"
                                        :key="opt.value"
                                        :value="opt.value"
                                    >
                                        {{ opt.label }}
                                    </option>
                                </select>

                                <button
                                    class="rounded bg-red-100 px-3 py-1 text-xs text-red-700"
                                    @click="deleteLog(log.id)"
                                >
                                    削除
                                </button>
                            </td>
                        </tr>
                        <tr v-if="readingLogs.length === 0">
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
    </AuthenticatedLayout>
</template>
