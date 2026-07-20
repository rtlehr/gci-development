<script setup lang="ts">
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
    <main :class="['mx-auto w-full space-y-6 p-4 sm:p-6 lg:p-8', sizes[size]]">
        <slot />
    </main>
</template>
