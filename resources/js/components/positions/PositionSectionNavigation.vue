<script setup lang="ts">
import { BriefcaseBusiness, ClipboardList, ListPlus, Users } from 'lucide-vue-next'

export type PositionSection = 'general' | 'other' | 'requirements' | 'candidates'

defineProps<{
    modelValue: PositionSection
    candidateCount?: number
}>()

const emit = defineEmits<{
    'update:modelValue': [value: PositionSection]
}>()

const sections = [
    { value: 'general' as const, label: 'General Information', icon: BriefcaseBusiness },
    { value: 'other' as const, label: 'Other Information', icon: ListPlus },
    { value: 'requirements' as const, label: 'Requirements', icon: ClipboardList },
    { value: 'candidates' as const, label: 'Candidates', icon: Users },
]
</script>

<template>
    <nav class="rounded-xl border bg-background p-2" aria-label="Position sections">
        <div class="grid gap-1 sm:grid-cols-4">
            <button
                v-for="section in sections"
                :key="section.value"
                type="button"
                class="flex items-center justify-center gap-2 rounded-lg px-4 py-3 text-sm font-medium transition-colors"
                :class="modelValue === section.value
                    ? 'bg-primary text-primary-foreground'
                    : 'text-muted-foreground hover:bg-muted hover:text-foreground'"
                @click="emit('update:modelValue', section.value)"
            >
                <component :is="section.icon" class="h-4 w-4" />
                <span>{{ section.label }}</span>
                <span
                    v-if="section.value === 'candidates'"
                    class="rounded-full px-2 py-0.5 text-xs"
                    :class="modelValue === section.value ? 'bg-primary-foreground/15' : 'bg-muted'"
                >
                    {{ candidateCount ?? 0 }}
                </span>
            </button>
        </div>
    </nav>
</template>
