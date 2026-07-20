<script setup lang="ts">
import { Check, X } from 'lucide-vue-next'

defineProps<{
    label: string
    active: boolean
}>()
</script>

<template>
    <div class="flex items-center justify-between gap-3 rounded-lg border p-3">
        <span class="text-sm font-medium">{{ label }}</span>
        <span
            :class="[
                'inline-flex h-7 w-7 items-center justify-center rounded-full',
                active
                    ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300'
                    : 'bg-muted text-muted-foreground',
            ]"
            :aria-label="active ? 'Yes' : 'No'"
        >
            <Check v-if="active" class="h-4 w-4" aria-hidden="true" />
            <X v-else class="h-4 w-4" aria-hidden="true" />
        </span>
    </div>
</template>
