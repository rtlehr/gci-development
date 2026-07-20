<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Spinner } from '@/components/ui/spinner'

withDefaults(defineProps<{
    cancelHref?: string
    cancelLabel?: string
    submitLabel?: string
    processingLabel?: string
    processing?: boolean
    sticky?: boolean
    dirty?: boolean
}>(), {
    cancelHref: '',
    cancelLabel: 'Cancel',
    submitLabel: 'Save',
    processingLabel: 'Saving…',
    processing: false,
    sticky: false,
    dirty: false,
})
</script>

<template>
    <div :class="[
        'flex flex-col-reverse gap-3 border-t bg-background/95 pt-5 sm:flex-row sm:items-center sm:justify-between',
        sticky ? 'sticky bottom-0 z-20 -mx-4 px-4 pb-4 backdrop-blur sm:-mx-6 sm:px-6' : '',
    ]">
        <div class="min-h-5 text-xs text-muted-foreground" aria-live="polite">
            <slot name="status">
                <span v-if="dirty">You have unsaved changes.</span>
            </slot>
        </div>

        <div class="flex flex-col-reverse gap-2 sm:flex-row sm:items-center sm:justify-end">
            <slot name="before" />

            <Button v-if="cancelHref" variant="outline" as-child>
                <Link :href="cancelHref">{{ cancelLabel }}</Link>
            </Button>

            <Button type="submit" :disabled="processing">
                <Spinner v-if="processing" class="mr-2" />
                {{ processing ? processingLabel : submitLabel }}
            </Button>

            <slot name="after" />
        </div>
    </div>
</template>
