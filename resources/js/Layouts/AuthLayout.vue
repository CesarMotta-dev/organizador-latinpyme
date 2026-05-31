<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="min-h-screen bg-red-600 px-4 py-8 sm:px-6 lg:px-8">
        <div class="mx-auto flex w-full max-w-6xl flex-col items-center gap-8">
            <div class="flex items-center justify-center rounded-full bg-white/10 p-4 shadow-lg ring-1 ring-white/20">
                <Link href="/" class="inline-flex items-center">
                    <ApplicationLogo class="h-14 w-14 fill-current text-white" />
                </Link>
            </div>

            <div class="w-full">
                <slot />
            </div>
        </div>
    </div>
</template>
