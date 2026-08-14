<template>
    <!-- Main card wrapper for displaying attachments -->
    <Card class="rounded-xl">
        <CardHeader>
            <CardTitle>{{ title }}</CardTitle>

            <!-- Optional description shown only when provided -->
            <CardDescription v-if="description">
                {{ description }}
            </CardDescription>
        </CardHeader>

        <CardContent>
            <!-- Attachment list -->
            <div v-if="sortedAttachments.length" class="space-y-3">

                <!-- Loop through sorted attachments -->
                <div
                    v-for="attachment in sortedAttachments"
                    :key="attachment.id"
                    class="rounded-lg border p-4"
                >
                    <!-- File details and actions layout -->
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div class="min-w-0">

                            <!-- File display name -->
                            <div class="font-medium break-words">
                                {{ attachment.original_name || 'Unnamed File' }}
                            </div>

                            <!-- File metadata row -->
                            <div class="mt-1 flex flex-wrap items-center gap-2 text-sm text-muted-foreground">
                                <span>{{ formatCategory(attachment.category) }}</span>
                                <span v-if="attachment.extension">• .{{ attachment.extension }}</span>
                                <span v-if="attachment.size">• {{ formatFileSize(attachment.size) }}</span>
                                <span v-if="attachment.is_primary">• Primary</span>
                            </div>

                            <!-- Optional attachment description -->
                            <div
                                v-if="attachment.description"
                                class="mt-2 text-sm text-muted-foreground whitespace-pre-line"
                            >
                                {{ attachment.description }}
                            </div>
                        </div>

                        <!-- Attachment action area -->
                        <div class="flex items-center gap-2">

                            <!-- Primary badge shown for primary attachment -->
                            <Badge v-if="attachment.is_primary" variant="default">
                                Primary
                            </Badge>

                            <!-- Open attachment link -->
                            <Button as-child v-if="attachment.url" variant="outline" size="sm"><a
                                :href="attachment.url"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                    {{ openLabel }}
                                </a></Button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty state when no attachments are available -->
            <div v-else class="text-sm text-muted-foreground">
                {{ emptyMessage }}
            </div>
        </CardContent>
    </Card>
</template>

<script setup>
// Computed is used to sort attachments reactively
import { computed } from 'vue'

// Shared UI components
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'

// Component props
const props = defineProps({

    // Attachment records to display
    attachments: {
        type: Array,
        default: () => [],
    },

    // Card title
    title: {
        type: String,
        default: 'Attachments',
    },

    // Optional card description
    description: {
        type: String,
        default: '',
    },

    // Message shown when there are no attachments
    emptyMessage: {
        type: String,
        default: 'No attachments available.',
    },

    // Label for the open attachment button
    openLabel: {
        type: String,
        default: 'Open',
    },
})

// Sort attachments for consistent display order
const sortedAttachments = computed(() => {
    return [...(props.attachments ?? [])].sort((a, b) => {

        // Primary attachments should appear first
        if (Number(Boolean(b.is_primary)) !== Number(Boolean(a.is_primary))) {
            return Number(Boolean(b.is_primary)) - Number(Boolean(a.is_primary))
        }

        // Then sort by custom sort order
        if ((a.sort_order ?? 0) !== (b.sort_order ?? 0)) {
            return (a.sort_order ?? 0) - (b.sort_order ?? 0)
        }

        // Finally sort by ID for a stable fallback order
        return (a.id ?? 0) - (b.id ?? 0)
    })
})

// Converts stored category values into readable labels
function formatCategory(value) {
    if (!value) return 'Other'

    const normalized = String(value).replaceAll('_', ' ')

    return normalized.charAt(0).toUpperCase() + normalized.slice(1)
}

// Converts raw byte size into a readable file size label
function formatFileSize(size) {
    const value = Number(size ?? 0)

    if (!value) return ''

    if (value < 1024) return `${value} B`
    if (value < 1024 * 1024) return `${(value / 1024).toFixed(1)} KB`

    return `${(value / (1024 * 1024)).toFixed(1)} MB`
}
</script>