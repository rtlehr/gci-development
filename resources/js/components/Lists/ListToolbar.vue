<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import { Download, Plus, Settings2 } from 'lucide-vue-next'
import PageHeader from '@/components/layout/PageHeader.vue'
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
    <PageHeader
        :title="title"
        :description="description"
        :eyebrow="eyebrow"
        :show-help="showHelp"
    >
        <template v-if="$slots.breadcrumbs" #breadcrumbs>
            <slot name="breadcrumbs" />
        </template>

        <template v-if="$slots.status" #status>
            <slot name="status" />
        </template>

        <template v-if="$slots.meta" #meta>
            <slot name="meta" />
        </template>

        <template #actions>
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

            <slot name="after-actions" />
        </template>
    </PageHeader>
</template>
