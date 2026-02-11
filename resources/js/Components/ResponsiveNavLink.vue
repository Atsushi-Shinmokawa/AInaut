<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps<{
    href: string;
    active?: boolean;
}>();

const classes = computed(() =>
    props.active
        ? 'block w-full ps-3 pe-4 py-2 border-l-4 border-amber-500 text-start text-base font-medium text-amber-800 bg-amber-50/80 focus:outline-none transition'
        : 'block w-full ps-3 pe-4 py-2 border-l-4 border-transparent text-start text-base font-medium text-stone-600 hover:bg-stone-100 hover:text-stone-900 focus:outline-none transition',
);
</script>

<template>
    <Link :href="href" :class="classes">
        <slot />
    </Link>
</template>
