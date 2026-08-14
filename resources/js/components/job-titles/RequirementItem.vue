<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3'
import { nextTick, ref, useTemplateRef, watch } from 'vue'
import { Pencil, Trash2 } from 'lucide-vue-next'
import InputError from '@/components/InputError.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/components/ui/alert-dialog'

type RequirementType = 'skill' | 'task'
type SkillRequirementType = 'required' | 'desired'

type Requirement = {
    id: number
    name: string
    description?: string | null
    requirement_type?: SkillRequirementType
    sort_order?: number | null
    is_active: boolean
}

const props = defineProps<{
    item: Requirement
    index: number
    type: RequirementType
    jobTitleId: number
    editing: boolean
    basePath?: string
}>()

const emit = defineEmits<{
    startEdit: [key: string]
    finishEdit: []
}>()

const nameInput = useTemplateRef<HTMLInputElement>('nameInput')
const deleteDialogOpen = ref(false)
const deleting = ref(false)

const form = useForm({
    name: props.item.name,
    description: props.item.description ?? '',
    requirement_type: props.item.requirement_type ?? 'required',
    sort_order: props.item.sort_order ?? 0,
    is_active: props.item.is_active,
})

const itemKey = `${props.type}-${props.item.id}`
const itemLabel = props.type === 'skill' ? 'skill' : 'task'
const basePath = props.basePath ?? '/job-titles'
const updateUrl = props.type === 'skill'
    ? `${basePath}/${props.jobTitleId}/skills/${props.item.id}`
    : `${basePath}/${props.jobTitleId}/tasks/${props.item.id}`
const deleteUrl = updateUrl

watch(
    () => props.editing,
    async (editing) => {
        if (!editing) {
            return
        }

        form.name = props.item.name
        form.description = props.item.description ?? ''
        form.requirement_type = props.item.requirement_type ?? 'required'
        form.sort_order = props.item.sort_order ?? 0
        form.is_active = props.item.is_active
        form.clearErrors()

        await nextTick()
        nameInput.value?.focus()
        nameInput.value?.select()
    },
)

function startEditing() {
    emit('startEdit', itemKey)
}

function cancelEditing() {
    form.clearErrors()
    emit('finishEdit')
}

function submit() {
    form.put(updateUrl, {
        preserveScroll: true,
        onSuccess: () => emit('finishEdit'),
    })
}

function handleKeydown(event: KeyboardEvent) {
    if (event.key === 'Escape') {
        event.preventDefault()
        cancelEditing()
    }

    if (event.key === 'Enter' && (event.ctrlKey || event.metaKey)) {
        event.preventDefault()
        submit()
    }
}

function confirmDelete() {
    deleting.value = true

    router.delete(deleteUrl, {
        preserveScroll: true,
        onFinish: () => {
            deleting.value = false
            deleteDialogOpen.value = false
        },
    })
}
</script>

<template>
    <div class="rounded-lg border bg-background p-4 transition-all">
        <form
            v-if="editing"
            class="space-y-4"
            @submit.prevent="submit"
            @keydown="handleKeydown"
        >
            <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_10rem]">
                <div class="space-y-2">
                    <Label :for="`${itemKey}-name`">Name</Label>
                    <Input
                        :id="`${itemKey}-name`"
                        ref="nameInput"
                        v-model="form.name"
                        autocomplete="off"
                    />
                    <InputError :message="form.errors.name" />
                </div>

                <div class="space-y-2">
                    <Label :for="`${itemKey}-sort-order`">Sort Order</Label>
                    <Input
                        :id="`${itemKey}-sort-order`"
                        v-model="form.sort_order"
                        type="number"
                    />
                    <InputError :message="form.errors.sort_order" />
                </div>
            </div>

            <div class="space-y-2">
                <Label :for="`${itemKey}-description`">Description</Label>
                <Textarea
                    :id="`${itemKey}-description`"
                    v-model="form.description"
                    rows="3"
                />
                <InputError :message="form.errors.description" />
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div v-if="type === 'skill'" class="space-y-2">
                    <Label :for="`${itemKey}-requirement-type`">Requirement</Label>
                    <select
                        :id="`${itemKey}-requirement-type`"
                        v-model="form.requirement_type"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    >
                        <option value="required">Required</option>
                        <option value="desired">Desired</option>
                    </select>
                    <InputError :message="form.errors.requirement_type" />
                </div>

                <label :for="`${itemKey}-active`" class="flex cursor-pointer items-center justify-between rounded-lg border p-3">
                    <span class="text-sm font-medium">Active</span>
                    <input :id="`${itemKey}-active`" v-model="form.is_active" type="checkbox" class="h-5 w-5" />
                </label>
            </div>

            <p class="text-xs text-muted-foreground">
                Press Ctrl+Enter to save or Escape to cancel.
            </p>

            <div class="flex justify-end gap-2">
                <Button type="button" variant="outline" :disabled="form.processing" @click="cancelEditing">
                    Cancel
                </Button>
                <Button type="submit" :disabled="form.processing">
                    {{ form.processing ? 'Saving...' : 'Save' }}
                </Button>
            </div>
        </form>

        <div v-else class="flex items-start justify-between gap-4">
            <div class="flex min-w-0 items-start gap-3">
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full border bg-muted text-sm font-semibold">
                    {{ index + 1 }}
                </span>

                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <div class="text-sm font-medium">{{ item.name }}</div>
                        <Badge
                            v-if="type === 'skill'"
                            :variant="item.requirement_type === 'desired' ? 'secondary' : 'destructive'"
                        >
                            {{ item.requirement_type === 'desired' ? 'Desired' : 'Required' }}
                        </Badge>
                        <Badge :variant="item.is_active ? 'outline' : 'secondary'">
                            {{ item.is_active ? 'Active' : 'Inactive' }}
                        </Badge>
                    </div>

                    <p v-if="item.description" class="mt-1 whitespace-pre-line text-sm text-muted-foreground">
                        {{ item.description }}
                    </p>

                    <p class="mt-2 text-xs text-muted-foreground">
                        Sort Order: {{ item.sort_order ?? 0 }}
                    </p>
                </div>
            </div>

            <div class="flex shrink-0 gap-2">
                <Button variant="outline" size="sm" @click="startEditing">
                    <Pencil class="mr-1 h-4 w-4" />
                    Edit
                </Button>

                <AlertDialog v-model:open="deleteDialogOpen">
                    <AlertDialogTrigger as-child>
                        <Button variant="destructive" size="sm">
                            <Trash2 class="mr-1 h-4 w-4" />
                            Delete
                        </Button>
                    </AlertDialogTrigger>

                    <AlertDialogContent>
                        <AlertDialogHeader>
                            <AlertDialogTitle>Delete this {{ itemLabel }}?</AlertDialogTitle>
                            <AlertDialogDescription>
                                “{{ item.name }}” will be permanently removed from this Job Title. This action cannot be undone.
                            </AlertDialogDescription>
                        </AlertDialogHeader>

                        <AlertDialogFooter>
                            <AlertDialogCancel :disabled="deleting">Cancel</AlertDialogCancel>
                            <AlertDialogAction
                                class="bg-destructive text-white hover:bg-destructive/90"
                                :disabled="deleting"
                                @click.prevent="confirmDelete"
                            >
                                {{ deleting ? 'Deleting...' : `Delete ${itemLabel}` }}
                            </AlertDialogAction>
                        </AlertDialogFooter>
                    </AlertDialogContent>
                </AlertDialog>
            </div>
        </div>
    </div>
</template>
