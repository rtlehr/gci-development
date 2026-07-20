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
    sticky?: boolean
}>(), {
    description: '',
    eyebrow: '',
    backHref: '',
    backLabel: 'Back',
    showHelp: true,
    sticky: false,
})
</script>

<template>
    <header
        :class="[
            'border-b pb-5',
            sticky && 'sticky top-0 z-20 -mx-4 bg-background/95 px-4 pt-4 backdrop-blur supports-[backdrop-filter]:bg-background/80 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8',
        ]"
    >
        <div v-if="$slots.breadcrumbs" class="mb-4">
            <slot name="breadcrumbs" />
        </div>

        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="min-w-0 space-y-2">
                <Link
                    v-if="backHref"
                    :href="backHref"
                    class="inline-flex items-center gap-1 rounded-sm text-sm font-medium text-muted-foreground transition-colors hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                >
                    <ArrowLeft class="h-4 w-4" aria-hidden="true" />
                    {{ backLabel }}
                </Link>

                <p
                    v-if="eyebrow"
                    class="text-sm font-semibold uppercase tracking-wide text-primary"
                >
                    {{ eyebrow }}
                </p>

                <div class="flex min-w-0 flex-wrap items-center gap-x-3 gap-y-2">
                    <h1 class="min-w-0 text-2xl font-semibold tracking-tight sm:text-3xl">
                        {{ title }}
                    </h1>

                    <div v-if="$slots.status" class="flex shrink-0 items-center gap-2">
                        <slot name="status" />
                    </div>
                </div>

                <p
                    v-if="description"
                    class="max-w-3xl text-sm text-muted-foreground sm:text-base"
                >
                    {{ description }}
                </p>

                <div v-if="$slots.meta" class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-muted-foreground">
                    <slot name="meta" />
                </div>
            </div>

            <div
                v-if="$slots.actions || showHelp"
                class="flex shrink-0 flex-wrap items-center gap-2 sm:justify-end"
                aria-label="Page actions"
            >
                <slot name="actions" />
                <PageHelpButton v-if="showHelp" />
            </div>
        </div>
    </header>
</template>
