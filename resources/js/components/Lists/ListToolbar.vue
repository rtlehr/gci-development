<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Download, Plus, Settings2 } from 'lucide-vue-next';

const props = withDefaults(defineProps<{
    title: string;
    description?: string;
    createLabel?: string;
    createHref?: string;
    createVariant?: 'default' | 'secondary' | 'destructive' | 'outline' | 'ghost' | 'link';
    canCreate?: boolean;
    canExport?: boolean;
    isDownloading?: boolean;
    exportLabel?: string;
    downloadingLabel?: string;
    showColumnSettings?: boolean;
}>(), {
    description: '',
    createLabel: 'Create',
    createHref: '',
    createVariant: 'default',
    canCreate: false,
    canExport: false,
    isDownloading: false,
    exportLabel: 'Export CSV',
    downloadingLabel: 'Exporting...',
    showColumnSettings: true,
});

const emit = defineEmits<{
    openColumnSettings: [];
    export: [];
}>();
</script>

<template>
    <div class="flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold">
                {{ title }}
            </h1>

            <p
                v-if="description"
                class="mt-1 text-sm text-muted-foreground"
            >
                {{ description }}
            </p>
        </div>

        <div class="flex items-center gap-2">
            <slot name="before-actions" />

            <Button
                v-if="showColumnSettings"
                type="button"
                variant="outline"
                @click="emit('openColumnSettings')"
            >
                <Settings2 class="mr-2 h-4 w-4" />
                Column Settings
            </Button>

            <Button
                v-if="canExport"
                type="button"
                variant="outline"
                :disabled="isDownloading"
                @click="emit('export')"
            >
                <Download class="mr-2 h-4 w-4" />
                {{ isDownloading ? downloadingLabel : exportLabel }}
            </Button>

            <Link
                v-if="canCreate && createHref"
                :href="createHref"
            >
                <Button :variant="createVariant">
                    <Plus class="mr-2 h-4 w-4" />
                    {{ createLabel }}
                </Button>
            </Link>

            <slot name="after-actions" />
        </div>
    </div>
</template>