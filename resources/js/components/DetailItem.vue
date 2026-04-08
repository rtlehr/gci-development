<template>
    <div>
        <div class="text-sm text-muted-foreground">{{ label }}</div>
        <div class="font-medium">{{ value || '—' }}</div>
    </div>
</template>

<script setup>
defineProps({
    label: {
        type: String,
        required: true,
    },
    value: {
        type: [String, Number, null],
        default: '—',
    },
})
</script>