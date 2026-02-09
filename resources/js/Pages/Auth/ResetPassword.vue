<script setup lang="ts">
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    email: string;
    token: string;
}>();

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('password.store'), {
        onFinish: () => {
            form.reset('password', 'password_confirmation');
        },
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="新しいパスワードを設定" />

        <div class="mb-6 text-center">
            <h1 class="text-xl font-semibold text-stone-800 sm:text-2xl">
                新しいパスワードを設定
            </h1>
            <p class="mt-1 text-sm text-stone-500">
                メールでお送りしたリンクからアクセスしました。新しいパスワードを入力してください。
            </p>
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
                />

                <InputError class="mt-1.5" :message="form.errors.email" />
            </div>

            <div>
                <InputLabel for="password" value="新しいパスワード" class="text-stone-700" />

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

            <div class="pt-2">
                <PrimaryButton
                    class="w-full sm:w-auto sm:min-w-[140px]"
                    :class="{ 'opacity-75': form.processing }"
                    :disabled="form.processing"
                >
                    {{ form.processing ? '設定中…' : 'パスワードを変更' }}
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>
