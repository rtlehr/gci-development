<script setup lang="ts">
import FlashMessages from '@/components/FlashMessages.vue'
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import type { BreadcrumbItem } from '@/types';


const { breadcrumbs = [] } = defineProps<{
    breadcrumbs?: BreadcrumbItem[];
}>();
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <FlashMessages />
        <slot />
    </AppLayout>
</template> 
