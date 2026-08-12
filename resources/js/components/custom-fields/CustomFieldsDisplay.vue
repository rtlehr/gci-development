<script setup lang="ts">
import FormSection from '@/components/forms/FormSection.vue'

type DisplayField = {
    id: number
    name: string
    field_type: string
    description?: string | null
    value?: string | string[] | null
}

withDefaults(defineProps<{ fields?: DisplayField[] }>(), {
    fields: () => [],
})

function displayValue(field: DisplayField): string {
    if (Array.isArray(field.value)) return field.value.length ? field.value.join(', ') : '—'
    if (!field.value) return '—'
    if (field.field_type === 'date') {
        const date = new Date(`${field.value}T00:00:00`)
        return Number.isNaN(date.getTime()) ? field.value : date.toLocaleDateString()
    }
    return field.value
}
</script>

<template>
    <FormSection
        title="Other Information"
        description="Additional information configured by your organization."
    >
        <div v-if="fields.length" class="grid gap-x-8 gap-y-5 md:grid-cols-2">
            <div v-for="field in fields" :key="field.id" class="min-w-0">
                <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">{{ field.name }}</p>
                <p class="mt-1 whitespace-pre-wrap text-sm text-foreground">{{ displayValue(field) }}</p>
                <p v-if="field.description" class="mt-1 text-xs text-muted-foreground">{{ field.description }}</p>
            </div>
        </div>
        <p v-else class="text-sm text-muted-foreground">No additional information is configured.</p>
    </FormSection>
</template>
