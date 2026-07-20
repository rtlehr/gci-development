<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import { Download, Plus, Settings2 } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'

withDefaults(defineProps<{
    title: string
    description?: string
    eyebrow?: string
    createLabel?: string
    createHref?: string
    createVariant?: 'default' | 'secondary' | 'destructive' | 'outline' | 'ghost' | 'link'
    canCreate?: boolean
    canExport?: boolean
    isDownloading?: boolean
    exportLabel?: string
    downloadingLabel?: string
    showColumnSettings?: boolean
    showHelp?: boolean
}>(), {
    description: '',
    eyebrow: '',
    createLabel: 'Create',
    createHref: '',
    createVariant: 'default',
    canCreate: false,
    canExport: false,
    isDownloading: false,
    exportLabel: 'Export CSV',
    downloadingLabel: 'Exporting...',
    showColumnSettings: true,
    showHelp: true,
})

const emit = defineEmits<{
    openColumnSettings: []
    export: []
}>()
</script>

<template>
    <header class="flex flex-col gap-4 border-b pb-5 lg:flex-row lg:items-start lg:justify-between">
        <div class="min-w-0">
            <p v-if="eyebrow" class="mb-1 text-sm font-semibold uppercase tracking-wide text-primary">
                {{ eyebrow }}
            </p>
            <h1 class="text-2xl font-semibold tracking-tight sm:text-3xl">{{ title }}</h1>
            <p v-if="description" class="mt-1 max-w-3xl text-sm text-muted-foreground sm:text-base">
                {{ description }}
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <slot name="before-actions" />

            <Button
                v-if="showColumnSettings"
                type="button"
                variant="outline"
                @click="emit('openColumnSettings')"
            >
                <Settings2 class="mr-2 h-4 w-4" aria-hidden="true" />
                Column Settings
            </Button>

            <Button
                v-if="canExport"
                type="button"
                variant="outline"
                :disabled="isDownloading"
                @click="emit('export')"
            >
                <Download class="mr-2 h-4 w-4" aria-hidden="true" />
                {{ isDownloading ? downloadingLabel : exportLabel }}
            </Button>

            <Link v-if="canCreate && createHref" :href="createHref">
                <Button :variant="createVariant">
                    <Plus class="mr-2 h-4 w-4" aria-hidden="true" />
                    {{ createLabel }}
                </Button>
            </Link>

        </div>
    </header>
</template>
