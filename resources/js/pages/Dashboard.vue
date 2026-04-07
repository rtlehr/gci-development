<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PermissionBlock from '@/components/PermissionBlock.vue';
import PlaceholderPattern from '@/components/PlaceholderPattern.vue';
import { useAuth } from '@/composables/useAuth';
import { dashboard } from '@/routes';

const { username, role, permissions } = useAuth();

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

    <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
        <div class="grid auto-rows-min gap-4 md:grid-cols-3">
            <div
                class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
            >
                <p>User: {{ username }}</p>
                <p>Role: {{ role }}</p>
                <p>Permissions: {{ permissions.join(', ') }}</p>
            </div>

            <PermissionBlock role="admin" fallback="This section is only for admins.">
                <div
                    class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
                >
                    <p class="mb-2">This section is only for admins.</p>
                    <PlaceholderPattern />
                </div>
            </PermissionBlock>

            <PermissionBlock
                permission="view_admin"
                fallback="This section is for users with the view_admin permission."
            >
                <div
                    class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
                >
                    <p class="mb-2">This section is for anyone with the view_admin permission.</p>
                    <PlaceholderPattern />
                </div>
            </PermissionBlock>
        </div>

        <div
            class="relative min-h-[100vh] flex-1 rounded-xl border border-sidebar-border/70 dark:border-sidebar-border md:min-h-min"
        >
            <PlaceholderPattern />
        </div>
    </div>
</template>