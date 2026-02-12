<script setup lang="ts">
import { computed } from 'vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';

defineProps<{
    mustVerifyEmail?: Boolean;
    status?: String;
}>();

const user = usePage().props.auth.user as any;

const form = useForm<{
    _method: string;
    name: string;
    email: string;
    profile_photo: File | null;
}>({
    _method: 'patch',
    name: user.name,
    email: user.email,
    profile_photo: null,
});

const profilePhotoUrl = computed<string | null>(() => {
    if (form.profile_photo instanceof File) {
        return URL.createObjectURL(form.profile_photo);
    }
    return user.profile_photo_url ?? null;
});

function onProfilePhotoChange(e: Event) {
    const target = e.target as HTMLInputElement;
    const file = target.files?.[0] ?? null;
    form.profile_photo = file;
}

function submit() {
    form.post(route('profile.update'), {
        preserveScroll: true,
        forceFormData: true,
    });
}
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900">
                Profile Information
            </h2>

            <p class="mt-1 text-sm text-gray-600">
                Update your account's profile information and email address.
            </p>
        </header>

        <form @submit.prevent="submit" class="mt-6 space-y-6">
            <div>
                <InputLabel for="name" value="Name" />

                <TextInput
                    id="name"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                />

                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <!-- Profile Photo -->
            <div>
                <InputLabel for="profile_photo" value="Profile Photo" />

                <div class="mt-2 flex items-center gap-4">
                    <div class="h-16 w-16 overflow-hidden rounded-full bg-gray-200 flex items-center justify-center text-sm text-gray-600">
                        <img
                            v-if="profilePhotoUrl"
                            :src="profilePhotoUrl"
                            alt="Profile"
                            class="h-16 w-16 object-cover"
                        />
                        <span v-else>
                            {{ (user.name || '').charAt(0) || '?' }}
                        </span>
                    </div>

                    <div>
                        <input
                            id="profile_photo"
                            type="file"
                            accept="image/*"
                            class="block w-full text-sm text-gray-600"
                            @change="onProfilePhotoChange"
                        />
                        <InputError class="mt-2" :message="form.errors.profile_photo" />
                    </div>
                </div>
            </div>

            <div>
                <InputLabel for="email" value="Email" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div v-if="mustVerifyEmail && user.email_verified_at === null">
                <p class="mt-2 text-sm text-gray-800">
                    Your email address is unverified.
                    <Link
                        :href="route('verification.send')"
                        method="post"
                        as="button"
                        class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                    >
                        Click here to re-send the verification email.
                    </Link>
                </p>

                <div
                    v-show="status === 'verification-link-sent'"
                    class="mt-2 text-sm font-medium text-green-600"
                >
                    A new verification link has been sent to your email address.
                </div>
            </div>

            <div class="flex items-center gap-4">
                <PrimaryButton :disabled="form.processing">Save</PrimaryButton>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p
                        v-if="form.recentlySuccessful"
                        class="text-sm text-gray-600"
                    >
                        Saved.
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>
