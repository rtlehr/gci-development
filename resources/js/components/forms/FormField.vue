<script setup lang="ts">
import InputError from '@/components/InputError.vue'
import { Label } from '@/components/ui/label'

withDefaults(defineProps<{
    label: string
    forId: string
    description?: string
    error?: string
    required?: boolean
}>(), {
    description: '',
    error: '',
    required: false,
})
</script>

<template>
    <div class="grid gap-2">
        <Label :for="forId">
            {{ label }}
            <span v-if="required" class="text-destructive" aria-hidden="true">*</span>
            <span v-if="required" class="sr-only">(required)</span>
        </Label>
        <slot :described-by="description || error ? `${forId}-help` : undefined" />
        <div v-if="description || error" :id="`${forId}-help`">
            <InputError v-if="error" :message="error" />
            <p v-else class="text-xs text-muted-foreground">{{ description }}</p>
        </div>
    </div>
</template>
