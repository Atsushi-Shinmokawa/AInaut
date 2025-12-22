<script setup lang="ts">
import { computed } from "vue";
import { useForm, usePage } from "@inertiajs/vue3";
import InputError from "@/Components/InputError.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";

const page = usePage();
const flash = computed(() => (page.props as any).flash ?? {});

const props = defineProps<{
    bookId: string;
    document: any | null;
    chunksPreview: any[];
}>();

const uploadForm = useForm<{ txt: File | null }>({
    txt: null,
});

const aozoraForm = useForm<{ aozora_url: string }>({
    aozora_url: "",
});

function submitUpload() {
    uploadForm.post(route("books.document.upload", { book: props.bookId }), {
        forceFormData: true,
        preserveScroll: true,
    });
}

function submitAozora() {
    aozoraForm.post(
        route("books.document.aozora_fetch", { book: props.bookId }),
        {
            preserveScroll: true,
        }
    );
}
</script>

<template>
    <div class="space-y-6">
        <!-- フラッシュ -->
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

        <!-- 本文ありステータス -->
        <div class="rounded-2xl border p-4">
            <div class="flex items-center gap-3">
                <div
                    class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold"
                    :class="
                        document
                            ? 'bg-green-100 text-green-800'
                            : 'bg-gray-100 text-gray-700'
                    "
                >
                    {{ document ? "本文あり" : "本文なし" }}
                </div>

                <div v-if="document" class="text-xs text-gray-600">
                    source: {{ document.source }} / length:
                    {{ document.text_length }}
                </div>
            </div>

            <div
                v-if="document?.source_url"
                class="mt-2 text-xs break-all text-gray-600"
            >
                URL: {{ document.source_url }}
            </div>
        </div>

        <!-- txtアップロード -->
        <div class="rounded-2xl border p-4 space-y-3">
            <div class="font-semibold">txtアップロード</div>

            <input
                type="file"
                accept=".txt,text/plain"
                @change="(e:any) => (uploadForm.txt = e.target.files?.[0] ?? null)"
                class="block w-full text-sm"
            />
            <InputError :message="uploadForm.errors.txt" />

            <PrimaryButton
                class="w-full h-10 justify-center"
                :disabled="uploadForm.processing"
                @click="submitUpload"
            >
                アップロードして取り込む
            </PrimaryButton>
        </div>

        <!-- 青空文庫URL -->
        <div class="rounded-2xl border p-4 space-y-3">
            <div class="font-semibold">青空文庫URL貼り付け → サーバfetch</div>

            <input
                v-model="aozoraForm.aozora_url"
                type="url"
                placeholder="https://www.aozora.gr.jp/..."
                class="w-full rounded-xl border p-3 text-sm"
            />
            <InputError :message="aozoraForm.errors.aozora_url" />

            <PrimaryButton
                class="w-full h-10 justify-center"
                :disabled="aozoraForm.processing"
                @click="submitAozora"
            >
                取得して取り込む
            </PrimaryButton>

            <p class="text-xs text-gray-600">
                ※ v1は「aozora.gr.jp
                のURLのみ許可」。作品ページからtxtリンクが見つからない場合は、txtファイルURLを直接貼ってください。
            </p>
        </div>

        <!-- チャンクプレビュー -->
        <div v-if="document" class="rounded-2xl border p-4 space-y-3">
            <div class="font-semibold">チャンク（先頭5件プレビュー）</div>

            <div
                v-if="chunksPreview.length === 0"
                class="text-sm text-gray-600"
            >
                チャンクがまだありません。
            </div>

            <div
                v-for="c in chunksPreview"
                :key="c.id"
                class="rounded-xl border p-3"
            >
                <div class="text-xs text-gray-500 mb-2">
                    #{{ c.chunk_index }} / {{ c.char_length }} chars
                </div>
                <pre class="whitespace-pre-wrap text-sm leading-relaxed">{{
                    c.content
                }}</pre>
            </div>
        </div>
    </div>
</template>
