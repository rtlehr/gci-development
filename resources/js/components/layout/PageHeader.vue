<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import { ArrowLeft } from 'lucide-vue-next'
import PageHelpButton from '@/components/ui/PageHelpButton.vue'
import { Button } from '@/components/ui/button'

withDefaults(defineProps<{
    title: string
    description?: string
    eyebrow?: string
    backHref?: string
    backLabel?: string
    showHelp?: boolean
}>(), {
    description: '',
    eyebrow: '',
    backHref: '',
    backLabel: 'Back',
    showHelp: true,
})
</script>

<template>
    <header class="flex flex-col gap-4 border-b pb-5 sm:flex-row sm:items-start sm:justify-between">
        <div class="min-w-0 space-y-2">
            <Link
                v-if="backHref"
                :href="backHref"
                class="inline-flex items-center gap-1 text-sm font-medium text-muted-foreground transition-colors hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
            >
                <ArrowLeft class="h-4 w-4" aria-hidden="true" />
                {{ backLabel }}
            </Link>

            <p v-if="eyebrow" class="text-sm font-semibold uppercase tracking-wide text-primary">
                {{ eyebrow }}
            </p>

            <div>
                <h1 class="text-2xl font-semibold tracking-tight sm:text-3xl">
                    {{ title }}
                </h1>
                <p v-if="description" class="mt-1 max-w-3xl text-sm text-muted-foreground sm:text-base">
                    {{ description }}
                </p>
            </div>

            <slot name="meta" />
        </div>

        <div class="flex shrink-0 flex-wrap items-center gap-2">
            <slot name="actions" />
            <PageHelpButton v-if="showHelp" />
        </div>
    </header>
</template>
