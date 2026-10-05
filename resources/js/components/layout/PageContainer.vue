<script setup lang="ts">
import { inject } from 'vue'
const publicPortalPageWidth = inject('publicPortalPageWidth', false)
withDefaults(defineProps<{
    size?: 'default' | 'wide' | 'full'
}>(), {
    size: 'wide',
})

const sizes = {
    default: 'max-w-6xl',
    wide: 'max-w-none lg:w-[90%]',
    full: 'max-w-none',
}
</script>

<template>
    <div :class="['mx-auto w-full space-y-6 p-4 sm:p-6 lg:p-8', publicPortalPageWidth ? 'max-w-7xl' : sizes[size]]">
        <slot />
    </div>
</template>
