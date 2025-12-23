<script setup lang="ts">
import { computed, ref, watch } from "vue";
import { useForm, usePage } from "@inertiajs/vue3";
import InputError from "@/Components/InputError.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";

const props = defineProps<{
    bookId: string;
    chatMessages: any[];
}>();

const page = usePage();
const flash = computed(() => (page.props as any).flash ?? {});

const form = useForm<{ content: string }>({
    content: "",
});

function send() {
    form.post(route("books.chat.send", { book: props.bookId }), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset("content");
        },
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

        <!-- Messages -->
        <div class="space-y-3">
            <div
                v-for="m in chatMessages"
                :key="m.id"
                class="rounded-2xl border p-3"
                :class="m.role === 'assistant' ? 'bg-gray-50' : ''"
            >
                <div class="text-xs text-gray-500 mb-2">
                    {{ m.role }}
                </div>
                <pre class="whitespace-pre-wrap text-sm leading-relaxed">{{
                    m.content
                }}</pre>
            </div>

            <div v-if="chatMessages.length === 0" class="text-sm text-gray-600">
                まだ会話がありません。下から送信して開始できます。
            </div>
        </div>

        <!-- Composer -->
        <div class="rounded-2xl border p-4 space-y-3">
            <textarea
                v-model="form.content"
                class="w-full rounded-xl border p-3 text-sm min-h-[140px]"
                placeholder="この本について質問してみよう（例：この作品の主題は？／この章の要点は？）"
            />
            <InputError :message="form.errors.content" />

            <PrimaryButton
                class="w-full h-10 justify-center"
                :disabled="form.processing"
                @click="send"
            >
                {{ form.processing ? "送信中..." : "送信" }}
            </PrimaryButton>
        </div>
    </div>
</template>
