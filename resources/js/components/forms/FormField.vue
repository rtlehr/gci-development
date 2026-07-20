<script setup lang="ts">
import InputError from '@/components/InputError.vue'
import { Label } from '@/components/ui/label'

withDefaults(defineProps<{
    label: string
    forId: string
    description?: string
    error?: string
    required?: boolean
    class?: string
}>(), {
    description: '',
    error: '',
    required: false,
    class: '',
})
</script>

<template>
    <div :class="['grid gap-2', $props.class]">
        <div class="flex items-center justify-between gap-3">
            <Label :for="forId">
                {{ label }}
                <span v-if="required" class="text-destructive" aria-hidden="true">*</span>
                <span v-if="required" class="sr-only">(required)</span>
            </Label>
            <slot name="label-action" />
        </div>

        <slot
            :described-by="description || error ? `${forId}-help` : undefined"
            :invalid="Boolean(error)"
        />

        <div v-if="description || error" :id="`${forId}-help`" aria-live="polite">
            <InputError v-if="error" :message="error" />
            <p v-else class="text-xs leading-5 text-muted-foreground">{{ description }}</p>
        </div>
    </div>
</template>
