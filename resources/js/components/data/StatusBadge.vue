<script setup lang="ts">
import { computed } from 'vue'
import { Badge } from '@/components/ui/badge'

type Tone = 'success' | 'warning' | 'danger' | 'info' | 'neutral'

const props = withDefaults(defineProps<{
    label: string
    tone?: Tone
    dot?: boolean
}>(), {
    tone: 'neutral',
    dot: true,
})

const toneClasses = computed(() => ({
    success: 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-300',
    warning: 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-300',
    danger: 'border-red-200 bg-red-50 text-red-700 dark:border-red-900 dark:bg-red-950 dark:text-red-300',
    info: 'border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-900 dark:bg-blue-950 dark:text-blue-300',
    neutral: 'border-border bg-muted text-muted-foreground',
}[props.tone]))

const dotClasses = computed(() => ({
    success: 'bg-emerald-500', warning: 'bg-amber-500', danger: 'bg-red-500', info: 'bg-blue-500', neutral: 'bg-muted-foreground',
}[props.tone]))
</script>

<template>
    <Badge variant="outline" :class="['gap-1.5 font-medium', toneClasses]">
        <span v-if="dot" :class="['h-1.5 w-1.5 rounded-full', dotClasses]" aria-hidden="true" />
        {{ label }}
    </Badge>
</template>
