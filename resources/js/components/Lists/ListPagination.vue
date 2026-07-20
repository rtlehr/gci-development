<script setup lang="ts">
import { Button } from '@/components/ui/button'

const props = withDefaults(defineProps<{
    currentPage: number
    lastPage: number
    from?: number | null
    to?: number | null
    total?: number | null
    itemLabel?: string
    pages?: number[]
}>(), {
    from: 0,
    to: 0,
    total: 0,
    itemLabel: 'items',
    pages: () => [],
})

const emit = defineEmits<{
    change: [page: number]
}>()

function goTo(page: number) {
    if (page < 1 || page > props.lastPage || page === props.currentPage) return
    emit('change', page)
}
</script>

<template>
    <nav
        class="flex flex-col gap-4 rounded-xl border bg-background px-4 py-3 sm:flex-row sm:items-center sm:justify-between"
        :aria-label="`${itemLabel} pagination`"
    >
        <p class="text-sm text-muted-foreground" aria-live="polite">
            Showing {{ from ?? 0 }} to {{ to ?? 0 }} of {{ total ?? 0 }} {{ itemLabel }}
        </p>

        <div class="flex flex-wrap items-center gap-2">
            <Button
                type="button"
                size="sm"
                variant="outline"
                :disabled="currentPage <= 1"
                aria-label="Go to previous page"
                @click="goTo(currentPage - 1)"
            >
                Previous
            </Button>

            <Button
                v-for="page in pages"
                :key="page"
                type="button"
                size="sm"
                :variant="page === currentPage ? 'default' : 'outline'"
                :aria-label="`Go to page ${page}`"
                :aria-current="page === currentPage ? 'page' : undefined"
                @click="goTo(page)"
            >
                {{ page }}
            </Button>

            <Button
                type="button"
                size="sm"
                variant="outline"
                :disabled="currentPage >= lastPage"
                aria-label="Go to next page"
                @click="goTo(currentPage + 1)"
            >
                Next
            </Button>
        </div>
    </nav>
</template>
