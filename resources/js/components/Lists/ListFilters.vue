<script setup lang="ts">
import { Search } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'

withDefaults(defineProps<{
    search?: string
    searchLabel?: string
    searchPlaceholder?: string
    showSearch?: boolean
    applyLabel?: string
    resetLabel?: string
}>(), {
    search: '',
    searchLabel: 'Search',
    searchPlaceholder: 'Search...',
    showSearch: true,
    applyLabel: 'Apply Filters',
    resetLabel: 'Clear',
})

const emit = defineEmits<{
    'update:search': [value: string]
    apply: []
    reset: []
}>()
</script>

<template>
    <section class="rounded-xl border bg-background p-4 shadow-sm" aria-label="List filters">
        <form class="space-y-4" role="search" @submit.prevent="emit('apply')">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end">
                <div v-if="showSearch" class="min-w-0 flex-1 space-y-2">
                    <Label for="list-search">{{ searchLabel }}</Label>
                    <div class="relative">
                        <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" aria-hidden="true" />
                        <Input
                            id="list-search"
                            class="pl-9"
                            :model-value="search"
                            :placeholder="searchPlaceholder"
                            @update:model-value="emit('update:search', String($event))"
                        />
                    </div>
                </div>

                <slot name="filters" />

                <div class="flex flex-wrap gap-2">
                    <Button type="submit">{{ applyLabel }}</Button>
                    <Button type="button" variant="outline" @click="emit('reset')">{{ resetLabel }}</Button>
                </div>
            </div>

            <slot name="advanced-filters" />
        </form>
    </section>
</template>
