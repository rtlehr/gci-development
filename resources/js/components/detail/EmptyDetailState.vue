<script setup lang="ts">
import type { Component } from 'vue'
import { Inbox } from 'lucide-vue-next'

withDefaults(defineProps<{
    title?: string
    description?: string
    icon?: Component
}>(), {
    title: 'No information available',
    description: 'There are no records to display in this section.',
    icon: Inbox,
})
</script>

<template>
    <div class="flex min-h-32 flex-col items-center justify-center rounded-lg border border-dashed bg-muted/10 px-6 py-8 text-center">
        <component :is="icon" class="mb-3 h-6 w-6 text-muted-foreground" aria-hidden="true" />
        <p class="text-sm font-medium">{{ title }}</p>
        <p class="mt-1 max-w-md text-sm text-muted-foreground">{{ description }}</p>
        <div v-if="$slots.actions" class="mt-4"><slot name="actions" /></div>
    </div>
</template>
