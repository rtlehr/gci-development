<script setup lang="ts">
withDefaults(defineProps<{
    label: string
    value?: string | number | null
    muted?: boolean
}>(), {
    value: '—',
    muted: false,
})
</script>

<template>
    <div class="grid gap-1">
        <dt class="text-xs font-medium uppercase tracking-wide text-muted-foreground">{{ label }}</dt>
        <dd :class="['text-sm font-medium', muted ? 'text-muted-foreground' : 'text-foreground']">
            <slot>{{ value === null || value === '' ? '—' : value }}</slot>
        </dd>
    </div>
</template>
