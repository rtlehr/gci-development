<script setup lang="ts">
import { ArrowDown, ArrowUp, ChevronsUpDown } from 'lucide-vue-next'
import { computed } from 'vue'
import { TableHead } from '@/components/ui/table'

type SortDirection = 'asc' | 'desc' | null

const props = withDefaults(defineProps<{
    sortable?: boolean
    direction?: SortDirection
    class?: string
    buttonClass?: string
    ariaLabel?: string
}>(), {
    sortable: false,
    direction: null,
    class: '',
    buttonClass: '',
})

const emit = defineEmits<{
    (e: 'sort'): void
}>()

const ariaSort = computed<'ascending' | 'descending' | 'none' | undefined>(() => {
    if (!props.sortable) return undefined
    if (props.direction === 'asc') return 'ascending'
    if (props.direction === 'desc') return 'descending'
    return 'none'
})
</script>

<template>
    <TableHead :class="$props.class" :aria-sort="ariaSort">
        <button
            v-if="sortable"
            type="button"
            :aria-label="ariaLabel"
            :class="[
                'inline-flex min-h-9 items-center gap-1.5 rounded-md px-1 text-left font-medium hover:bg-muted focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2',
                buttonClass,
            ]"
            @click="emit('sort')"
        >
            <slot />
            <ArrowUp v-if="direction === 'asc'" class="h-4 w-4" aria-hidden="true" />
            <ArrowDown v-else-if="direction === 'desc'" class="h-4 w-4" aria-hidden="true" />
            <ChevronsUpDown v-else class="h-4 w-4 text-muted-foreground" aria-hidden="true" />
        </button>
        <span v-else>
            <slot />
        </span>
    </TableHead>
</template>
