<template>
    <Card class="rounded-xl">
        <CardHeader>
            <CardTitle>{{ title }}</CardTitle>
            <CardDescription v-if="description">
                {{ description }}
            </CardDescription>
        </CardHeader>

        <CardContent>
            <div v-if="sortedAttachments.length" class="space-y-3">
                <div
                    v-for="attachment in sortedAttachments"
                    :key="attachment.id"
                    class="rounded-lg border p-4"
                >
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div class="min-w-0">
                            <div class="font-medium break-words">
                                {{ attachment.original_name || 'Unnamed File' }}
                            </div>

                            <div class="mt-1 flex flex-wrap items-center gap-2 text-sm text-muted-foreground">
                                <span>{{ formatCategory(attachment.category) }}</span>
                                <span v-if="attachment.extension">• .{{ attachment.extension }}</span>
                                <span v-if="attachment.size">• {{ formatFileSize(attachment.size) }}</span>
                                <span v-if="attachment.is_primary">• Primary</span>
                            </div>

                            <div
                                v-if="attachment.description"
                                class="mt-2 text-sm text-muted-foreground whitespace-pre-line"
                            >
                                {{ attachment.description }}
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <Badge v-if="attachment.is_primary" variant="default">
                                Primary
                            </Badge>

                            <a
                                v-if="attachment.url"
                                :href="attachment.url"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                <Button type="button" variant="outline" size="sm">
                                    {{ openLabel }}
                                </Button>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div v-else class="text-sm text-muted-foreground">
                {{ emptyMessage }}
            </div>
        </CardContent>
    </Card>
</template>

<script setup>
import { computed } from 'vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'

const props = defineProps({
    attachments: {
        type: Array,
        default: () => [],
    },
    title: {
        type: String,
        default: 'Attachments',
    },
    description: {
        type: String,
        default: '',
    },
    emptyMessage: {
        type: String,
        default: 'No attachments available.',
    },
    openLabel: {
        type: String,
        default: 'Open',
    },
})

const sortedAttachments = computed(() => {
    return [...(props.attachments ?? [])].sort((a, b) => {
        if (Number(Boolean(b.is_primary)) !== Number(Boolean(a.is_primary))) {
            return Number(Boolean(b.is_primary)) - Number(Boolean(a.is_primary))
        }

        if ((a.sort_order ?? 0) !== (b.sort_order ?? 0)) {
            return (a.sort_order ?? 0) - (b.sort_order ?? 0)
        }

        return (a.id ?? 0) - (b.id ?? 0)
    })
})

function formatCategory(value) {
    if (!value) return 'Other'
    const normalized = String(value).replaceAll('_', ' ')
    return normalized.charAt(0).toUpperCase() + normalized.slice(1)
}

function formatFileSize(size) {
    const value = Number(size ?? 0)

    if (!value) return ''

    if (value < 1024) return `${value} B`
    if (value < 1024 * 1024) return `${(value / 1024).toFixed(1)} KB`
    return `${(value / (1024 * 1024)).toFixed(1)} MB`
}
</script>