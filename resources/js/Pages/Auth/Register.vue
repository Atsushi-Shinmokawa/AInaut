<script setup lang="ts">
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => {
            form.reset('password', 'password_confirmation');
        },
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="新規登録" />

        <div class="mb-6 text-center">
            <h1 class="text-xl font-semibold text-stone-800 sm:text-2xl">
                新規登録
            </h1>
            <p class="mt-1 text-sm text-stone-500">
                読書ログを始めるアカウントを作成
            </p>
        </div>

        <form @submit.prevent="submit" class="space-y-5">
            <div>
                <InputLabel for="name" value="お名前" class="text-stone-700" />

                <TextInput
                    id="name"
                    type="text"
                    class="mt-1.5 block w-full rounded-lg border-stone-300 shadow-sm transition focus:border-amber-500 focus:ring-amber-500"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                    placeholder="山田 太郎"
                />

                <InputError class="mt-1.5" :message="form.errors.name" />
            </div>

            <div>
                <InputLabel for="email" value="メールアドレス" class="text-stone-700" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1.5 block w-full rounded-lg border-stone-300 shadow-sm transition focus:border-amber-500 focus:ring-amber-500"
                    v-model="form.email"
                    required
                    autocomplete="username"
                    placeholder="you@example.com"
                />

                <InputError class="mt-1.5" :message="form.errors.email" />
            </div>

            <div>
                <InputLabel for="password" value="パスワード" class="text-stone-700" />

                <TextInput
                    id="password"
                    type="password"
                    class="mt-1.5 block w-full rounded-lg border-stone-300 shadow-sm transition focus:border-amber-500 focus:ring-amber-500"
                    v-model="form.password"
                    required
                    autocomplete="new-password"
                    placeholder="8文字以上"
                />

                <InputError class="mt-1.5" :message="form.errors.password" />
            </div>

            <div>
                <InputLabel
                    for="password_confirmation"
                    value="パスワード（確認）"
                    class="text-stone-700"
                />

                <TextInput
                    id="password_confirmation"
                    type="password"
                    class="mt-1.5 block w-full rounded-lg border-stone-300 shadow-sm transition focus:border-amber-500 focus:ring-amber-500"
                    v-model="form.password_confirmation"
                    required
                    autocomplete="new-password"
                    placeholder="もう一度入力"
                />

                <InputError
                    class="mt-1.5"
                    :message="form.errors.password_confirmation"
                />
            </div>

            <div class="flex flex-col gap-4 pt-2 sm:flex-row sm:items-center sm:justify-end">
                <Link
                    :href="route('login')"
                    class="order-2 text-sm text-amber-700 underline decoration-amber-700/50 transition hover:decoration-amber-700 focus:outline-none focus:ring-2 focus:ring-amber-500/50 focus:ring-offset-2 focus:ring-offset-white sm:order-1 sm:me-4"
                >
                    すでにアカウントをお持ちの方はログイン
                </Link>
                <PrimaryButton
                    class="order-1 w-full sm:order-2 sm:w-auto sm:min-w-[140px]"
                    :class="{ 'opacity-75': form.processing }"
                    :disabled="form.processing"
                >
                    {{ form.processing ? '登録中…' : '登録する' }}
                </PrimaryButton>
            </div>
        </form>

        <p class="mt-6 border-t border-stone-200 pt-6 text-center text-sm text-stone-500">
            すでにアカウントをお持ちの方は
            <Link
                :href="route('login')"
                class="font-medium text-amber-700 underline decoration-amber-700/50 hover:decoration-amber-700"
            >
                ログイン
            </Link>
        </p>
    </GuestLayout>
</template>
