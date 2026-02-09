<script setup lang="ts">
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps<{
    canResetPassword?: boolean;
    status?: string;
}>();

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => {
            form.reset('password');
        },
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="ログイン" />

        <div class="mb-6 text-center">
            <h1 class="text-xl font-semibold text-stone-800 sm:text-2xl">
                ログイン
            </h1>
            <p class="mt-1 text-sm text-stone-500">
                アカウントにサインインしてください
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

            <div>
                <InputLabel for="password" value="パスワード" class="text-stone-700" />

                <TextInput
                    id="password"
                    type="password"
                    class="mt-1.5 block w-full rounded-lg border-stone-300 shadow-sm transition focus:border-amber-500 focus:ring-amber-500"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                    placeholder="••••••••"
                />

                <InputError class="mt-1.5" :message="form.errors.password" />
            </div>

            <div class="flex items-center">
                <label class="flex cursor-pointer items-center gap-2">
                    <Checkbox name="remember" v-model:checked="form.remember" />
                    <span class="text-sm text-stone-600">ログイン状態を保持する</span>
                </label>
            </div>

            <div class="flex flex-col gap-4 pt-2 sm:flex-row sm:items-center sm:justify-between">
                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="text-sm text-amber-700 underline decoration-amber-700/50 transition hover:decoration-amber-700 focus:outline-none focus:ring-2 focus:ring-amber-500/50 focus:ring-offset-2 focus:ring-offset-white"
                >
                    パスワードをお忘れですか？
                </Link>
                <PrimaryButton
                    class="w-full sm:ms-auto sm:w-auto sm:min-w-[120px]"
                    :class="{ 'opacity-75': form.processing }"
                    :disabled="form.processing"
                >
                    {{ form.processing ? '送信中…' : 'ログイン' }}
                </PrimaryButton>
            </div>
        </form>

        <p class="mt-6 border-t border-stone-200 pt-6 text-center text-sm text-stone-500">
            アカウントをお持ちでない方は
            <Link
                :href="route('register')"
                class="font-medium text-amber-700 underline decoration-amber-700/50 hover:decoration-amber-700"
            >
                新規登録
            </Link>
        </p>
    </GuestLayout>
</template>
