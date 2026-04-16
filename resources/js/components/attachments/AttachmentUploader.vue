<template>
    <Card class="rounded-xl">
        <CardHeader>
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div>
                    <CardTitle>{{ title }}</CardTitle>
                    <CardDescription>
                        {{ description }}
                    </CardDescription>
                </div>

                <Button type="button" variant="outline" @click="addAttachment">
                    {{ addButtonLabel }}
                </Button>
            </div>
        </CardHeader>

        <CardContent class="space-y-6">
            <div class="space-y-3" v-if="showExisting && internalExistingAttachments.length">
                <h3 class="font-medium">Existing Files</h3>

                <div
                    v-for="(attachment, index) in internalExistingAttachments"
                    :key="attachment.id"
                    class="rounded-xl border p-4 space-y-4"
                    :class="attachment.marked_for_removal ? 'opacity-60 border-red-300' : ''"
                >
                    <div class="flex items-center justify-between gap-4">
                        <div class="min-w-0">
                            <div class="font-medium break-words">
                                {{ attachment.original_name || 'Unnamed File' }}
                            </div>

                            <div class="mt-1 flex flex-wrap gap-2 text-sm text-muted-foreground">
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
                                v-if="attachment.url && !attachment.marked_for_removal"
                                :href="attachment.url"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                <Button type="button" variant="outline" size="sm">
                                    Open
                                </Button>
                            </a>

                            <Button
                                v-if="!attachment.marked_for_removal"
                                type="button"
                                variant="outline"
                                size="sm"
                                @click="markExistingAttachmentForRemoval(index)"
                            >
                                Remove
                            </Button>

                            <Button
                                v-else
                                type="button"
                                variant="outline"
                                size="sm"
                                @click="undoExistingAttachmentRemoval(index)"
                            >
                                Undo Remove
                            </Button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-4" v-if="internalNewAttachments.length">
                <h3 class="font-medium">{{ newFilesLabel }}</h3>

                <div
                    v-for="(attachment, index) in internalNewAttachments"
                    :key="`new-attachment-${index}`"
                    class="rounded-xl border p-4 space-y-4"
                >
                    <div class="flex items-center justify-between">
                        <h3 class="font-medium">New File {{ index + 1 }}</h3>

                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            @click="removeAttachment(index)"
                        >
                            Remove
                        </Button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                        <div class="space-y-2 xl:col-span-2">
                            <Label :for="`attachment-file-${uid}-${index}`">
                                File <span class="text-red-500">*</span>
                            </Label>
                            <Input
                                :id="`attachment-file-${uid}-${index}`"
                                type="file"
                                @change="handleFileChange($event, index)"
                            />
                            <p
                                v-if="attachmentFieldError(index, 'file')"
                                class="text-sm text-red-500"
                            >
                                {{ attachmentFieldError(index, 'file') }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label :for="`attachment-category-${uid}-${index}`">Category</Label>
                            <select
                                :id="`attachment-category-${uid}-${index}`"
                                v-model="attachment.category"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            >
                                <option value="">Select category</option>
                                <option
                                    v-for="option in categories"
                                    :key="option.value"
                                    :value="option.value"
                                >
                                    {{ option.label }}
                                </option>
                            </select>
                        </div>

                        <div class="space-y-2">
                            <Label>Primary</Label>
                            <div class="flex items-center gap-3 rounded-md border p-3">
                                <input
                                    :id="`attachment-primary-${uid}-${index}`"
                                    :checked="attachment.is_primary"
                                    type="checkbox"
                                    class="h-4 w-4"
                                    @change="setPrimaryAttachment(index)"
                                />
                                <Label :for="`attachment-primary-${uid}-${index}`" class="cursor-pointer">
                                    Primary
                                </Label>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label :for="`attachment-description-${uid}-${index}`">Description</Label>
                        <Textarea
                            :id="`attachment-description-${uid}-${index}`"
                            v-model="attachment.description"
                            rows="3"
                        />
                    </div>
                </div>
            </div>

            <div
                v-if="!internalExistingAttachments.length && !internalNewAttachments.length"
                class="rounded-xl border border-dashed p-6 text-sm text-muted-foreground"
            >
                {{ emptyMessage }}
            </div>

            <p v-if="errors?.attachments" class="text-sm text-red-500">
                {{ errors.attachments }}
            </p>

            <p v-if="errors?.remove_attachment_ids" class="text-sm text-red-500">
                {{ errors.remove_attachment_ids }}
            </p>
        </CardContent>
    </Card>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'

const props = defineProps({
    modelValue: {
        type: Array,
        default: () => [],
    },
    existingAttachments: {
        type: Array,
        default: () => [],
    },
    removeAttachmentIds: {
        type: Array,
        default: () => [],
    },
    errors: {
        type: Object,
        default: () => ({}),
    },
    title: {
        type: String,
        default: 'Attachments',
    },
    description: {
        type: String,
        default: 'Upload files such as resumes or supporting documents.',
    },
    addButtonLabel: {
        type: String,
        default: 'Add File',
    },
    newFilesLabel: {
        type: String,
        default: 'New Files',
    },
    emptyMessage: {
        type: String,
        default: 'No files added yet.',
    },
    showExisting: {
        type: Boolean,
        default: true,
    },
    categories: {
        type: Array,
        default: () => [
            { value: 'resume', label: 'Resume' },
            { value: 'cover_letter', label: 'Cover Letter' },
            { value: 'certificate', label: 'Certificate' },
            { value: 'other', label: 'Other' },
        ],
    },
})

const emit = defineEmits([
    'update:modelValue',
    'update:existingAttachments',
    'update:removeAttachmentIds',
])

const uid = Math.random().toString(36).slice(2, 9)

const internalNewAttachments = ref(structuredCloneSafe(props.modelValue))
const internalExistingAttachments = ref(structuredCloneSafe(props.existingAttachments))
const internalRemoveAttachmentIds = ref([...(props.removeAttachmentIds ?? [])])

watch(
    () => props.modelValue,
    (value) => {
        internalNewAttachments.value = structuredCloneSafe(value)
    },
    { deep: true }
)

watch(
    () => props.existingAttachments,
    (value) => {
        internalExistingAttachments.value = structuredCloneSafe(value)
    },
    { deep: true }
)

watch(
    () => props.removeAttachmentIds,
    (value) => {
        internalRemoveAttachmentIds.value = [...(value ?? [])]
    },
    { deep: true }
)

watch(
    internalNewAttachments,
    (value) => {
        emit('update:modelValue', value)
    },
    { deep: true }
)

watch(
    internalExistingAttachments,
    (value) => {
        emit('update:existingAttachments', value)
    },
    { deep: true }
)

watch(
    internalRemoveAttachmentIds,
    (value) => {
        emit('update:removeAttachmentIds', value)
    },
    { deep: true }
)

function structuredCloneSafe(value) {
    return (value ?? []).map((item) => ({ ...item }))
}

function createEmptyAttachment() {
    return {
        file: null,
        category: '',
        description: '',
        is_primary: false,
    }
}

function addAttachment() {
    internalNewAttachments.value.push(createEmptyAttachment())
}

function removeAttachment(index) {
    internalNewAttachments.value.splice(index, 1)
}

function handleFileChange(event, index) {
    const file = event.target.files?.[0] ?? null
    internalNewAttachments.value[index].file = file
}

function setPrimaryAttachment(index) {
    internalNewAttachments.value = internalNewAttachments.value.map((attachment, attachmentIndex) => ({
        ...attachment,
        is_primary: attachmentIndex === index,
    }))
}

function markExistingAttachmentForRemoval(index) {
    const attachment = internalExistingAttachments.value[index]
    if (!attachment) return

    attachment.marked_for_removal = true

    if (!internalRemoveAttachmentIds.value.includes(attachment.id)) {
        internalRemoveAttachmentIds.value.push(attachment.id)
    }
}

function undoExistingAttachmentRemoval(index) {
    const attachment = internalExistingAttachments.value[index]
    if (!attachment) return

    attachment.marked_for_removal = false
    internalRemoveAttachmentIds.value = internalRemoveAttachmentIds.value.filter((id) => id !== attachment.id)
}

function attachmentFieldError(index, field) {
    return props.errors?.[`attachments.${index}.${field}`]
}

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

function validate() {
    let hasError = false

    internalNewAttachments.value.forEach((attachment) => {
        if (!attachment.file) {
            hasError = true
        }
    })

    return !hasError
}

defineExpose({
    validate,
})
</script>