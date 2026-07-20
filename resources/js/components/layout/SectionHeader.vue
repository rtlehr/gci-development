<script setup lang="ts">
withDefaults(defineProps<{
    title: string
    description?: string
}>(), {
    description: '',
})
</script>

<template>
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h2 class="text-lg font-semibold tracking-tight">{{ title }}</h2>
            <p v-if="description" class="mt-1 text-sm text-muted-foreground">{{ description }}</p>
        </div>
        <div v-if="$slots.actions" class="flex shrink-0 flex-wrap items-center gap-2">
            <slot name="actions" />
        </div>
    </div>
</template>
