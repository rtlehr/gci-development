<script setup lang="ts">
withDefaults(defineProps<{
    columns?: 1 | 2 | 3 | 4
}>(), {
    columns: 2,
})
</script>

<template>
    <dl
        :class="[
            'grid gap-x-8 gap-y-5',
            columns >= 2 && 'sm:grid-cols-2',
            columns >= 3 && 'lg:grid-cols-3',
            columns >= 4 && 'xl:grid-cols-4',
        ]"
    >
        <slot />
    </dl>
</template>
