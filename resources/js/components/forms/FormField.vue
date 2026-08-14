<script setup lang="ts">
import InputError from '@/components/InputError.vue'
import { computed } from 'vue'
import { Label } from '@/components/ui/label'

const props = withDefaults(defineProps<{
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

const descriptionId = computed(() => props.description ? `${props.forId}-description` : undefined)
const errorId = computed(() => props.error ? `${props.forId}-error` : undefined)
const describedBy = computed(() => [descriptionId.value, errorId.value].filter(Boolean).join(' ') || undefined)
</script>

<template>
    <div :class="['grid gap-2', $props.class]">
        <div class="flex items-center justify-between gap-3">
            <Label :id="`${forId}-label`" :for="forId">
                {{ label }}
                <span v-if="required" class="text-destructive" aria-hidden="true">*</span>
                <span v-if="required" class="sr-only">(required)</span>
            </Label>
            <slot name="label-action" />
        </div>

        <slot
            :described-by="describedBy"
            :invalid="Boolean(error)"
            :required="required"
        />

        <p
            v-if="description"
            :id="descriptionId"
            class="text-xs leading-5 text-muted-foreground"
        >
            {{ description }}
        </p>

        <div v-if="error" :id="errorId" aria-live="polite">
            <InputError :message="error" />
        </div>
    </div>
</template>
