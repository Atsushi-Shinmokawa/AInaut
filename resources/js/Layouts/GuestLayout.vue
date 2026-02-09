<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div
        class="relative flex min-h-screen flex-col items-center justify-center overflow-hidden bg-gradient-to-br from-amber-50 via-stone-50 to-rose-50 px-4 py-12 sm:px-6"
    >
        <!-- 背景のふわふわアニメーション -->
        <div class="absolute inset-0 overflow-hidden" aria-hidden="true">
            <div
                class="absolute -top-40 -right-40 h-80 w-80 rounded-full bg-amber-200/60 mix-blend-multiply blur-3xl animate-blob"
            />
            <div
                class="absolute -bottom-40 -left-40 h-80 w-80 rounded-full bg-rose-200/60 mix-blend-multiply blur-3xl animate-blob-delay-2"
            />
            <div
                class="absolute left-1/2 top-1/2 h-72 w-72 -translate-x-1/2 -translate-y-1/2 rounded-full bg-stone-200/50 mix-blend-multiply blur-3xl animate-blob-delay-4"
            />
        </div>

        <!-- トップのロゴリンク（認証画面ではホームへ） -->
        <div class="relative z-10 mb-2">
            <Link
                href="/"
                class="inline-flex items-center gap-2 rounded-lg transition-opacity hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-amber-500/50"
            >
                <span
                    class="text-2xl font-bold tracking-tight text-stone-800 sm:text-3xl"
                    >AInaut</span
                >
            </Link>
        </div>

        <!-- カードスロット -->
        <div
            class="relative z-10 mt-4 w-full max-w-md animate-fade-in overflow-hidden rounded-2xl bg-white/85 px-6 py-8 shadow-xl shadow-stone-200/50 backdrop-blur-sm sm:px-8 sm:py-10"
        >
            <slot />
        </div>

        <!-- フッターキャッチコピー -->
        <p
            class="relative z-10 mt-6 max-w-sm text-center text-sm text-stone-500"
        >
            読書体験を深める、AIと対話する読書ログ
        </p>
    </div>
</template>
