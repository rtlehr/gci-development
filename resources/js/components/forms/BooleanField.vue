<script setup lang="ts">
defineProps<{
    id: string
    label: string
    description?: string
    modelValue: boolean
}>()

defineEmits<{
    'update:modelValue': [value: boolean]
}>()
</script>

<template>
    <label :for="id" class="flex cursor-pointer items-start justify-between gap-4 rounded-lg border p-4 transition-colors hover:bg-muted/40">
        <span class="grid gap-1">
            <span class="text-sm font-medium">{{ label }}</span>
            <span v-if="description" class="text-xs leading-5 text-muted-foreground">{{ description }}</span>
        </span>
        <input
            :id="id"
            type="checkbox"
            :checked="modelValue"
            class="mt-0.5 h-5 w-5 shrink-0 rounded border-input accent-primary"
            @change="$emit('update:modelValue', ($event.target as HTMLInputElement).checked)"
        />
    </label>
</template>
