<script setup lang="ts">
import type { ButtonVariants } from '@/components/ui/button'
import { Button } from '@/components/ui/button'

withDefaults(defineProps<{
    label: string
    title?: string
    variant?: ButtonVariants['variant']
    size?: ButtonVariants['size']
    disabled?: boolean
    type?: 'button' | 'submit' | 'reset'
}>(), {
    variant: 'ghost',
    size: 'icon',
    disabled: false,
    type: 'button',
})
</script>

<template>
    <Button
        :type="type"
        :variant="variant"
        :size="size"
        :disabled="disabled"
        :aria-label="label"
        :title="title ?? label"
    >
        <slot />
    </Button>
</template>
