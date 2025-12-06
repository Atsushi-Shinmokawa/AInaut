<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head } from "@inertiajs/vue3";

const props = defineProps({
    items: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <Head title="マイ本棚" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                マイ本棚
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <div
                        v-if="items.length === 0"
                        class="text-gray-500 text-center"
                    >
                        まだ本棚に本が登録されていません。
                    </div>

                    <ul v-else class="space-y-4">
                        <li
                            v-for="item in items"
                            :key="item.id"
                            class="flex gap-4 border-b pb-4 last:border-b-0"
                        >
                            <img
                                v-if="item.book.cover_url"
                                :src="item.book.cover_url"
                                class="w-16 h-auto rounded shadow"
                                alt=""
                            />
                            <div class="flex-1">
                                <div class="font-bold">
                                    {{ item.book.title }}
                                </div>
                                <div class="text-sm text-gray-600">
                                    {{ item.book.author || "著者不明" }}
                                </div>
                                <div class="text-xs text-gray-400 mt-1">
                                    ステータス: {{ item.status }} / 追加日:
                                    {{ item.created_at }}
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
