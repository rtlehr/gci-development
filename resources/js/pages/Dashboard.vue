<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PermissionBlock from '@/components/PermissionBlock.vue';
import PlaceholderPattern from '@/components/PlaceholderPattern.vue';
import { useAuth } from '@/composables/useAuth';
import { dashboard } from '@/routes';

const { username} = useAuth()

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Dashboard" />

    <div
        class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
    >
        <div class="grid auto-rows-min gap-4 md:grid-cols-3">
            <div class="grid auto-rows-min gap-4 md:grid-cols-3">

        <!-- Always visible -->
        <div class="relative aspect-video rounded-xl border p-4">
            <p>User: {{ username }}</p>
        </div>

        <!-- 🔒 Protected -->
        <PermissionBlock permission="view_admin" fallback="Admin only">
            <div class="relative aspect-video rounded-xl border">
                <PlaceholderPattern />
            </div>
        </PermissionBlock>

        <!-- Always visible -->
        <div class="relative aspect-video rounded-xl border">
            <PlaceholderPattern />
        </div>

    </div>
            <div
                class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
            >
                <PlaceholderPattern />
            </div>
            <div
                class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
            >
                <PlaceholderPattern />
            </div>
        </div>
        <div
            class="relative min-h-[100vh] flex-1 rounded-xl border border-sidebar-border/70 md:min-h-min dark:border-sidebar-border"
        >
            <PlaceholderPattern />
        </div>
    </div>
</template>
