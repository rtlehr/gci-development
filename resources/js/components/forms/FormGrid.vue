<script setup lang="ts">
withDefaults(defineProps<{
    columns?: 1 | 2 | 3
    gap?: 'default' | 'compact'
}>(), {
    columns: 2,
    gap: 'default',
})

const columnClasses = {
    1: 'grid-cols-1',
    2: 'grid-cols-1 md:grid-cols-2',
    3: 'grid-cols-1 md:grid-cols-2 xl:grid-cols-3',
}

const gapClasses = {
    default: 'gap-5',
    compact: 'gap-4',
}
</script>

<template>
    <div :class="['grid', columnClasses[columns], gapClasses[gap]]">
        <slot />
    </div>
</template>
