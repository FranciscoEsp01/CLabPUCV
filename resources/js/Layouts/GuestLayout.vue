<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div
        class="flex min-h-screen flex-col items-center bg-white pt-12 sm:justify-center sm:pt-0"
    >
        <div>
            <Link href="/">
                <ApplicationLogo class="h-12 w-12 fill-current text-black" />
            </Link>
        </div>

        <div
            class="mt-12 w-full bg-white px-6 py-4 sm:max-w-md"
        >
            <slot />
        </div>
    </div>
</template>
