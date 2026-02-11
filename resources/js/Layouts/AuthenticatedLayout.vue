<script setup lang="ts">
import { ref } from "vue";
import ApplicationLogo from "@/Components/ApplicationLogo.vue";
import Dropdown from "@/Components/Dropdown.vue";
import DropdownLink from "@/Components/DropdownLink.vue";
import NavLink from "@/Components/NavLink.vue";
import ResponsiveNavLink from "@/Components/ResponsiveNavLink.vue";
import { Link } from "@inertiajs/vue3";
import { usePage } from "@inertiajs/vue3";
import type { PageProps as InertiaPageProps } from "@inertiajs/core";

type FlashMessages = {
    success?: string;
    error?: string;
};

type PageProps = InertiaPageProps & {
    flash?: FlashMessages;
};

const page = usePage<PageProps>();

const showingNavigationDropdown = ref(false);
</script>

<template>
    <div>
        <div class="min-h-screen bg-stone-50">
            <nav class="border-b border-stone-200 bg-white shadow-sm">
                <!-- Primary Navigation Menu -->
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="flex h-16 justify-between">
                        <div class="flex">
                            <!-- Logo（クリックでホーム＝ダッシュボード） -->
                            <div class="flex shrink-0 items-center">
                                <Link
                                    :href="route('dashboard')"
                                    class="flex items-center gap-2 rounded-md transition hover:opacity-90"
                                >
                                    <ApplicationLogo
                                        class="block h-9 w-auto fill-current text-stone-800"
                                    />
                                    <span class="hidden text-sm font-semibold text-stone-700 sm:inline">AInaut</span>
                                </Link>
                            </div>
                            <!-- メインナビ：ホームを先頭に、読書・追加の流れでグループ化 -->
                            <div class="hidden items-center gap-2 sm:ml-10 sm:flex">
                                <NavLink
                                    :href="route('dashboard')"
                                    :active="route().current('dashboard')"
                                >
                                    ホーム
                                </NavLink>
                                <span class="h-4 w-px bg-stone-200" aria-hidden="true" />
                                <NavLink
                                    :href="route('reading-logs.index')"
                                    :active="route().current('reading-logs.index')"
                                >
                                    マイ本棚
                                </NavLink>
                                <NavLink
                                    :href="route('books.search')"
                                    :active="route().current('books.search')"
                                >
                                    本を探す
                                </NavLink>
                                <span class="h-4 w-px bg-stone-200" aria-hidden="true" />
                                <Link
                                    :href="route('imports.kindle.create')"
                                    class="rounded-lg bg-amber-600 px-3 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-amber-700"
                                >
                                    Kindle取り込み
                                </Link>
                            </div>
                        </div>

                        <div class="hidden sm:ms-6 sm:flex sm:items-center">
                            <!-- Settings Dropdown -->
                            <div class="relative ms-3">
                                <Dropdown align="right" width="48">
                                    <template #trigger>
                                        <span class="inline-flex rounded-md">
                                            <button
                                                type="button"
                                                class="inline-flex items-center rounded-lg px-3 py-2 text-sm font-medium text-stone-600 transition hover:bg-stone-100 hover:text-stone-900 focus:outline-none focus:ring-2 focus:ring-amber-500/30"
                                            >
                                                {{ $page.props.auth.user.name }}

                                                <svg
                                                    class="-me-0.5 ms-2 h-4 w-4"
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 20 20"
                                                    fill="currentColor"
                                                >
                                                    <path
                                                        fill-rule="evenodd"
                                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                        clip-rule="evenodd"
                                                    />
                                                </svg>
                                            </button>
                                        </span>
                                    </template>

                                    <template #content>
                                        <DropdownLink
                                            :href="route('profile.edit')"
                                        >
                                            プロフィール
                                        </DropdownLink>
                                        <DropdownLink
                                            :href="route('logout')"
                                            method="post"
                                            as="button"
                                        >
                                            ログアウト
                                        </DropdownLink>
                                    </template>
                                </Dropdown>
                            </div>
                        </div>

                        <!-- Hamburger -->
                        <div class="-me-2 flex items-center sm:hidden">
                            <button
                                @click="
                                    showingNavigationDropdown =
                                        !showingNavigationDropdown
                                "
                                class="inline-flex items-center justify-center rounded-md p-2 text-gray-400 transition duration-150 ease-in-out hover:bg-gray-100 hover:text-gray-500 focus:bg-gray-100 focus:text-gray-500 focus:outline-none"
                            >
                                <svg
                                    class="h-6 w-6"
                                    stroke="currentColor"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        :class="{
                                            hidden: showingNavigationDropdown,
                                            'inline-flex':
                                                !showingNavigationDropdown,
                                        }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M4 6h16M4 12h16M4 18h16"
                                    />
                                    <path
                                        :class="{
                                            hidden: !showingNavigationDropdown,
                                            'inline-flex':
                                                showingNavigationDropdown,
                                        }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Responsive Navigation Menu -->
                <div
                    :class="{
                        block: showingNavigationDropdown,
                        hidden: !showingNavigationDropdown,
                    }"
                    class="sm:hidden"
                >
                    <div class="space-y-1 pb-3 pt-2">
                        <ResponsiveNavLink
                            :href="route('dashboard')"
                            :active="route().current('dashboard')"
                        >
                            ホーム
                        </ResponsiveNavLink>
                        <ResponsiveNavLink
                            :href="route('reading-logs.index')"
                            :active="route().current('reading-logs.index')"
                        >
                            マイ本棚
                        </ResponsiveNavLink>
                        <ResponsiveNavLink
                            :href="route('books.search')"
                            :active="route().current('books.search')"
                        >
                            本を探す
                        </ResponsiveNavLink>
                        <ResponsiveNavLink
                            :href="route('imports.kindle.create')"
                            :active="route().current('imports.kindle.create')"
                        >
                            Kindle取り込み
                        </ResponsiveNavLink>
                    </div>

                    <!-- Responsive Settings Options -->
                    <div class="border-t border-gray-200 pb-1 pt-4">
                        <div class="px-4">
                            <div class="text-base font-medium text-gray-800">
                                {{ $page.props.auth.user.name }}
                            </div>
                            <div class="text-sm font-medium text-gray-500">
                                {{ $page.props.auth.user.email }}
                            </div>
                        </div>

                        <div class="mt-3 space-y-1">
                            <ResponsiveNavLink :href="route('profile.edit')">
                                プロフィール
                            </ResponsiveNavLink>
                            <ResponsiveNavLink
                                :href="route('logout')"
                                method="post"
                                as="button"
                            >
                                ログアウト
                            </ResponsiveNavLink>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Flash Messages -->
            <div
                v-if="page.props.flash?.success"
                class="bg-green-100 text-green-800 px-4 py-2 text-center"
            >
                {{ page.props.flash.success }}
            </div>
            <div
                v-if="page.props.flash?.error"
                class="bg-red-100 text-red-800 px-4 py-2 text-center"
            >
                {{ page.props.flash.error }}
            </div>

            <!-- Page Heading -->
            <header class="border-b border-stone-200 bg-white shadow-sm" v-if="$slots.header">
                <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <!-- Page Content -->
            <main>
                <slot />
            </main>
        </div>
    </div>
</template>
