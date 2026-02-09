<script setup lang="ts">
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps<{
    status?: string;
}>();

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <GuestLayout>
        <Head title="パスワード再設定" />

        <div class="mb-6 text-center">
            <h1 class="text-xl font-semibold text-stone-800 sm:text-2xl">
                パスワードをお忘れですか？
            </h1>
            <p class="mt-2 text-sm text-stone-500">
                登録したメールアドレスを入力してください。パスワード再設定用のリンクをお送りします。
            </p>
        </div>

        <div
            v-if="status"
            class="mb-4 rounded-lg bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700"
        >
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="space-y-5">
            <div>
                <InputLabel for="email" value="メールアドレス" class="text-stone-700" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1.5 block w-full rounded-lg border-stone-300 shadow-sm transition focus:border-amber-500 focus:ring-amber-500"
                    v-model="form.email"
                    required
                    autofocus
                    autocomplete="username"
                    placeholder="you@example.com"
                />

                <InputError class="mt-1.5" :message="form.errors.email" />
            </div>

            <div class="flex flex-col gap-4 pt-2 sm:flex-row sm:items-center sm:justify-between">
                <Link
                    :href="route('login')"
                    class="text-sm text-amber-700 underline decoration-amber-700/50 transition hover:decoration-amber-700 focus:outline-none focus:ring-2 focus:ring-amber-500/50 focus:ring-offset-2 focus:ring-offset-white"
                >
                    ログインに戻る
                </Link>
                <PrimaryButton
                    class="w-full sm:ms-auto sm:w-auto sm:min-w-[180px]"
                    :class="{ 'opacity-75': form.processing }"
                    :disabled="form.processing"
                >
                    {{ form.processing ? '送信中…' : '再設定リンクを送信' }}
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>
