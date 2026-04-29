<template>
    <div class="space-y-2">
        <Label :for="id">
            {{ label }}
            <span v-if="required" class="text-red-500">*</span>
        </Label>

        <select
            :id="id"
            :value="modelValue"
            @change="$emit('update:modelValue', Number(($event.target as HTMLSelectElement).value))"
            :class="[
                'flex h-10 w-full rounded-md border bg-background px-3 py-2 text-sm',
                error ? 'border-red-500' : 'border-input'
            ]"
        >
            <option value="">Select an organization</option>

            <option
                v-for="organization in organizations"
                :key="organization.id"
                :value="organization.id"
            >
                {{ organization.full_path || organization.name }}
            </option>
        </select>

        <p v-if="error" class="text-sm text-red-500">
            {{ error }}
        </p>
    </div>
</template>

<script setup lang="ts">
import { Label } from '@/components/ui/label'

defineProps<{
    modelValue: number | string | null
    organizations: {
        id: number
        name: string
        full_path: string
        depth?: number
    }[]
    label?: string
    id?: string
    error?: string
    required?: boolean
}>()

defineEmits<{
    'update:modelValue': [value: number | null]
}>()
</script>

<script lang="ts">
export default {
    props: {
        label: {
            default: 'Organization',
        },
        id: {
            default: 'organization_id',
        },
        required: {
            default: false,
        },
    },
}
</script>