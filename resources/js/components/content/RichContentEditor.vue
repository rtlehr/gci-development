<script setup lang="ts">
import { nextTick, onMounted, ref, watch } from 'vue'
import { Bold, Heading2, Italic, Link2, List, ListOrdered, Redo2, Undo2 } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'

const props = withDefaults(defineProps<{
    modelValue: string
    ariaLabel?: string
    ariaLabelledby?: string
    describedBy?: string
}>(), {
    ariaLabel: 'Rich text content',
})

const emit = defineEmits<{ 'update:modelValue': [value: string] }>()
const editor = ref<HTMLElement | null>(null)
const linkDialogOpen = ref(false)
const linkUrl = ref('')
const savedSelection = ref<Range | null>(null)

function sync(): void {
    emit('update:modelValue', editor.value?.innerHTML ?? '')
}

function command(name: string, value?: string): void {
    editor.value?.focus()
    document.execCommand(name, false, value)
    sync()
}

function saveSelection(): void {
    const selection = window.getSelection()

    if (selection && selection.rangeCount > 0 && editor.value?.contains(selection.anchorNode)) {
        savedSelection.value = selection.getRangeAt(0).cloneRange()
    }
}

function restoreSelection(): void {
    if (!savedSelection.value) return

    const selection = window.getSelection()
    selection?.removeAllRanges()
    selection?.addRange(savedSelection.value)
}

function openLinkDialog(): void {
    saveSelection()
    linkUrl.value = ''
    linkDialogOpen.value = true
}

function applyLink(): void {
    const url = linkUrl.value.trim()

    if (!url) return

    linkDialogOpen.value = false

    nextTick(() => {
        editor.value?.focus()
        restoreSelection()
        document.execCommand('createLink', false, url)
        savedSelection.value = null
        sync()
    })
}

onMounted(() => {
    if (editor.value) {
        editor.value.innerHTML = props.modelValue ?? ''
    }
})

watch(
    () => props.modelValue,
    async (value) => {
        await nextTick()

        if (editor.value && editor.value.innerHTML !== value) {
            editor.value.innerHTML = value ?? ''
        }
    },
)
</script>

<template>
    <div class="overflow-hidden rounded-lg border bg-background focus-within:ring-2 focus-within:ring-ring/50 focus-within:ring-offset-2">
        <div
            role="toolbar"
            aria-label="Text formatting"
            class="flex flex-wrap gap-1 border-b bg-muted/30 p-2"
        >
            <Button type="button" variant="ghost" size="icon" aria-label="Undo" title="Undo" @click="command('undo')">
                <Undo2 class="h-4 w-4" aria-hidden="true" />
            </Button>
            <Button type="button" variant="ghost" size="icon" aria-label="Redo" title="Redo" @click="command('redo')">
                <Redo2 class="h-4 w-4" aria-hidden="true" />
            </Button>
            <Button type="button" variant="ghost" size="icon" aria-label="Heading level 2" title="Heading" @click="command('formatBlock', 'h2')">
                <Heading2 class="h-4 w-4" aria-hidden="true" />
            </Button>
            <Button type="button" variant="ghost" size="icon" aria-label="Bold" title="Bold" @click="command('bold')">
                <Bold class="h-4 w-4" aria-hidden="true" />
            </Button>
            <Button type="button" variant="ghost" size="icon" aria-label="Italic" title="Italic" @click="command('italic')">
                <Italic class="h-4 w-4" aria-hidden="true" />
            </Button>
            <Button type="button" variant="ghost" size="icon" aria-label="Bulleted list" title="Bulleted list" @click="command('insertUnorderedList')">
                <List class="h-4 w-4" aria-hidden="true" />
            </Button>
            <Button type="button" variant="ghost" size="icon" aria-label="Numbered list" title="Numbered list" @click="command('insertOrderedList')">
                <ListOrdered class="h-4 w-4" aria-hidden="true" />
            </Button>
            <Button type="button" variant="ghost" size="icon" aria-label="Add link" title="Link" @click="openLinkDialog">
                <Link2 class="h-4 w-4" aria-hidden="true" />
            </Button>
        </div>

        <div
            ref="editor"
            contenteditable="true"
            role="textbox"
            aria-multiline="true"
            :aria-label="ariaLabelledby ? undefined : ariaLabel"
            :aria-labelledby="ariaLabelledby"
            :aria-describedby="describedBy"
            class="prose prose-sm min-h-72 max-w-none rounded-md p-4 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-inset"
            @input="sync"
            @blur="sync"
        />
    </div>

    <Dialog v-model:open="linkDialogOpen">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Add link</DialogTitle>
                <DialogDescription>
                    Enter the full URL for the selected text.
                </DialogDescription>
            </DialogHeader>

            <form class="space-y-4" @submit.prevent="applyLink">
                <div class="space-y-2">
                    <Label for="rich-content-link-url">URL</Label>
                    <Input
                        id="rich-content-link-url"
                        v-model="linkUrl"
                        type="url"
                        inputmode="url"
                        autocomplete="url"
                        placeholder="https://example.com"
                    />
                </div>

                <DialogFooter>
                    <Button type="button" variant="outline" @click="linkDialogOpen = false">
                        Cancel
                    </Button>
                    <Button type="submit" :disabled="!linkUrl.trim()">
                        Add link
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
