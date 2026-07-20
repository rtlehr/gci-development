<script setup lang="ts">
withDefaults(defineProps<{
    label?: string
}>(), {
    label: 'Results',
})
</script>

<template>
    <section class="overflow-hidden rounded-xl border bg-background shadow-sm" :aria-label="label">
        <div class="overflow-x-auto">
            <slot />
        </div>
    </section>
</template>
