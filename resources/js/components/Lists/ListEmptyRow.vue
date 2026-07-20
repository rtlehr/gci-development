<script setup lang="ts">
import { SearchX } from 'lucide-vue-next'
import { TableCell, TableRow } from '@/components/ui/table'

withDefaults(defineProps<{
    colspan: number
    title?: string
    description?: string
}>(), {
    title: 'No results found',
    description: 'Try adjusting your search or filters.',
})
</script>

<template>
    <TableRow>
        <TableCell :colspan="colspan" class="p-0">
            <div class="flex min-h-44 flex-col items-center justify-center gap-2 px-6 py-10 text-center">
                <span class="rounded-full bg-muted p-3" aria-hidden="true">
                    <SearchX class="h-5 w-5 text-muted-foreground" />
                </span>
                <p class="font-medium">{{ title }}</p>
                <p class="max-w-md text-sm text-muted-foreground">{{ description }}</p>
            </div>
        </TableCell>
    </TableRow>
</template>
