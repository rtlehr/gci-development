<template>
    <!-- Main attachment management card -->
    <Card class="rounded-xl">
        <CardHeader>

            <!-- Header section with title, description, and add button -->
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div>
                    <CardTitle>{{ title }}</CardTitle>

                    <CardDescription>
                        {{ description }}
                    </CardDescription>
                </div>

                <!-- Adds a new attachment row -->
                <Button type="button" variant="outline" @click="addAttachment">
                    {{ addButtonLabel }}
                </Button>
            </div>
        </CardHeader>

        <CardContent class="space-y-6">

            <!-- Existing attachments section -->
            <div class="space-y-3" v-if="showExisting && internalExistingAttachments.length">
                <h3 class="font-medium">Existing Files</h3>

                <!-- Loop through existing attachments -->
                <div
                    v-for="(attachment, index) in internalExistingAttachments"
                    :key="attachment.id"
                    class="rounded-xl border p-4 space-y-4"

                    <!-- Visually indicate pending removal -->
                    :class="attachment.marked_for_removal ? 'opacity-60 border-red-300' : ''"
                >
                    <div class="flex items-center justify-between gap-4">
                        <div class="min-w-0">

                            <!-- Attachment display name -->
                            <div class="font-medium break-words">
                                {{ attachment.original_name || 'Unnamed File' }}
                            </div>

                            <!-- Attachment metadata -->
                            <div class="mt-1 flex flex-wrap gap-2 text-sm text-muted-foreground">
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

                        <!-- Attachment action buttons -->
                        <div class="flex items-center gap-2">

                            <!-- Primary attachment badge -->
                            <Badge v-if="attachment.is_primary" variant="default">
                                Primary
                            </Badge>

                            <!-- Open file button -->
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

                            <!-- Mark attachment for removal -->
                            <Button
                                v-if="!attachment.marked_for_removal"
                                type="button"
                                variant="outline"
                                size="sm"
                                @click="markExistingAttachmentForRemoval(index)"
                            >
                                Remove
                            </Button>

                            <!-- Undo removal -->
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

            <!-- New attachments section -->
            <div class="space-y-4" v-if="internalNewAttachments.length">
                <h3 class="font-medium">{{ newFilesLabel }}</h3>

                <!-- Loop through new unsaved attachments -->
                <div
                    v-for="(attachment, index) in internalNewAttachments"
                    :key="`new-attachment-${index}`"
                    class="rounded-xl border p-4 space-y-4"
                >
                    <!-- New file header -->
                    <div class="flex items-center justify-between">
                        <h3 class="font-medium">New File {{ index + 1 }}</h3>

                        <!-- Remove unsaved attachment -->
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            @click="removeAttachment(index)"
                        >
                            Remove
                        </Button>
                    </div>

                    <!-- Main attachment form fields -->
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">

                        <!-- File upload -->
                        <div class="space-y-2 xl:col-span-2">
                            <Label :for="`attachment-file-${uid}-${index}`">
                                File <span class="text-red-500">*</span>
                            </Label>

                            <Input
                                :id="`attachment-file-${uid}-${index}`"
                                type="file"
                                @change="handleFileChange($event, index)"
                            />

                            <!-- Validation error display -->
                            <p
                                v-if="attachmentFieldError(index, 'file')"
                                class="text-sm text-red-500"
                            >
                                {{ attachmentFieldError(index, 'file') }}
                            </p>
                        </div>

                        <!-- File category -->
                        <div class="space-y-2">
                            <Label :for="`attachment-category-${uid}-${index}`">Category</Label>

                            <select
                                :id="`attachment-category-${uid}-${index}`"
                                v-model="attachment.category"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            >
                                <option value="">Select category</option>

                                <!-- Category dropdown options -->
                                <option
                                    v-for="option in categories"
                                    :key="option.value"
                                    :value="option.value"
                                >
                                    {{ option.label }}
                                </option>
                            </select>
                        </div>

                        <!-- Primary attachment toggle -->
                        <div class="space-y-2">
                            <Label>Primary</Label>

                            <div class="flex items-center gap-3 rounded-md border p-3">

                                <!-- Only one new attachment can be primary -->
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

                    <!-- Attachment description -->
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

            <!-- Empty state -->
            <div
                v-if="!internalExistingAttachments.length && !internalNewAttachments.length"
                class="rounded-xl border border-dashed p-6 text-sm text-muted-foreground"
            >
                {{ emptyMessage }}
            </div>

            <!-- General attachment validation errors -->
            <p v-if="errors?.attachments" class="text-sm text-red-500">
                {{ errors.attachments }}
            </p>

            <!-- Attachment removal validation errors -->
            <p v-if="errors?.remove_attachment_ids" class="text-sm text-red-500">
                {{ errors.remove_attachment_ids }}
            </p>
        </CardContent>
    </Card>
</template>

<script setup>
// Vue reactive utilities
import { computed, ref, watch } from 'vue'

// Shared UI components
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'

// Component props
const props = defineProps({

    // New attachments being uploaded
    modelValue: {
        type: Array,
        default: () => [],
    },

    // Existing saved attachments
    existingAttachments: {
        type: Array,
        default: () => [],
    },

    // IDs of attachments marked for deletion
    removeAttachmentIds: {
        type: Array,
        default: () => [],
    },

    // Validation errors
    errors: {
        type: Object,
        default: () => ({}),
    },

    // Card title
    title: {
        type: String,
        default: 'Attachments',
    },

    // Card description
    description: {
        type: String,
        default: 'Upload files such as resumes or supporting documents.',
    },

    // Add button label
    addButtonLabel: {
        type: String,
        default: 'Add File',
    },

    // Label for new file section
    newFilesLabel: {
        type: String,
        default: 'New Files',
    },

    // Empty-state message
    emptyMessage: {
        type: String,
        default: 'No files added yet.',
    },

    // Controls whether existing files are shown
    showExisting: {
        type: Boolean,
        default: true,
    },

    // Available attachment categories
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

// Events emitted back to the parent component
const emit = defineEmits([
    'update:modelValue',
    'update:existingAttachments',
    'update:removeAttachmentIds',
])

// Unique ID prefix for accessibility-safe form field IDs
const uid = Math.random().toString(36).slice(2, 9)

// Internal reactive copies of incoming data
const internalNewAttachments = ref(structuredCloneSafe(props.modelValue))
const internalExistingAttachments = ref(structuredCloneSafe(props.existingAttachments))
const internalRemoveAttachmentIds = ref([...(props.removeAttachmentIds ?? [])])

// Sync incoming new attachments from parent
watch(
    () => props.modelValue,
    (value) => {
        internalNewAttachments.value = structuredCloneSafe(value)
    },
    { deep: true }
)

// Sync incoming existing attachments from parent
watch(
    () => props.existingAttachments,
    (value) => {
        internalExistingAttachments.value = structuredCloneSafe(value)
    },
    { deep: true }
)

// Sync incoming remove IDs from parent
watch(
    () => props.removeAttachmentIds,
    (value) => {
        internalRemoveAttachmentIds.value = [...(value ?? [])]
    },
    { deep: true }
)

// Emit new attachment updates back to parent
watch(
    internalNewAttachments,
    (value) => {
        emit('update:modelValue', value)
    },
    { deep: true }
)

// Emit existing attachment updates back to parent
watch(
    internalExistingAttachments,
    (value) => {
        emit('update:existingAttachments', value)
    },
    { deep: true }
)

// Emit removal ID updates back to parent
watch(
    internalRemoveAttachmentIds,
    (value) => {
        emit('update:removeAttachmentIds', value)
    },
    { deep: true }
)

// Creates a shallow cloned copy of attachment data
// to avoid mutating props directly
function structuredCloneSafe(value) {
    return (value ?? []).map((item) => ({ ...item }))
}

// Returns a blank attachment object
function createEmptyAttachment() {
    return {
        file: null,
        category: '',
        description: '',
        is_primary: false,
    }
}

// Adds a new attachment row
function addAttachment() {
    internalNewAttachments.value.push(createEmptyAttachment())
}

// Removes a new unsaved attachment
function removeAttachment(index) {
    internalNewAttachments.value.splice(index, 1)
}

// Handles file input changes
function handleFileChange(event, index) {

    // Get selected file from input
    const file = event.target.files?.[0] ?? null

    internalNewAttachments.value[index].file = file
}

// Sets one attachment as primary
function setPrimaryAttachment(index) {
    internalNewAttachments.value = internalNewAttachments.value.map((attachment, attachmentIndex) => ({
        ...attachment,
        is_primary: attachmentIndex === index,
    }))
}

// Marks an existing attachment for removal
function markExistingAttachmentForRemoval(index) {
    const attachment = internalExistingAttachments.value[index]

    if (!attachment) return

    attachment.marked_for_removal = true

    // Track attachment ID for backend deletion
    if (!internalRemoveAttachmentIds.value.includes(attachment.id)) {
        internalRemoveAttachmentIds.value.push(attachment.id)
    }
}

// Restores an attachment previously marked for removal
function undoExistingAttachmentRemoval(index) {
    const attachment = internalExistingAttachments.value[index]

    if (!attachment) return

    attachment.marked_for_removal = false

    // Remove ID from deletion tracking list
    internalRemoveAttachmentIds.value = internalRemoveAttachmentIds.value.filter((id) => id !== attachment.id)
}

// Returns nested validation errors for attachment fields
function attachmentFieldError(index, field) {
    return props.errors?.[`attachments.${index}.${field}`]
}

// Formats category values into readable labels
function formatCategory(value) {
    if (!value) return 'Other'

    const normalized = String(value).replaceAll('_', ' ')

    return normalized.charAt(0).toUpperCase() + normalized.slice(1)
}

// Converts raw byte size into readable file sizes
function formatFileSize(size) {
    const value = Number(size ?? 0)

    if (!value) return ''

    if (value < 1024) return `${value} B`
    if (value < 1024 * 1024) return `${(value / 1024).toFixed(1)} KB`

    return `${(value / (1024 * 1024)).toFixed(1)} MB`
}

// Local validation helper
function validate() {
    let hasError = false

    // Ensure all new attachment rows contain a file
    internalNewAttachments.value.forEach((attachment) => {
        if (!attachment.file) {
            hasError = true
        }
    })

    return !hasError
}

// Expose validation method to parent components
defineExpose({
    validate,
})
</script>