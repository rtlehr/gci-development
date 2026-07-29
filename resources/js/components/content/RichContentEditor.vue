<script setup lang="ts">
import { nextTick, onMounted, ref, watch } from 'vue';
import { Bold, Heading2, Italic, Link2, List, ListOrdered, Redo2, Undo2 } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';

const props = defineProps<{ modelValue: string }>();
const emit = defineEmits<{ 'update:modelValue': [value: string] }>();
const editor = ref<HTMLElement | null>(null);

function sync(): void { emit('update:modelValue', editor.value?.innerHTML ?? ''); }
function command(name: string, value?: string): void {
    editor.value?.focus();
    document.execCommand(name, false, value);
    sync();
}
function addLink(): void {
    const url = window.prompt('Enter the full link URL');
    if (url) command('createLink', url);
}
onMounted(() => { if (editor.value) editor.value.innerHTML = props.modelValue ?? ''; });
watch(() => props.modelValue, async value => { await nextTick(); if (editor.value && editor.value.innerHTML !== value) editor.value.innerHTML = value ?? ''; });
</script>

<template>
    <div class="overflow-hidden rounded-lg border bg-background">
        <div class="flex flex-wrap gap-1 border-b bg-muted/30 p-2" aria-label="Rich text formatting toolbar">
            <Button type="button" variant="ghost" size="icon" title="Undo" @click="command('undo')"><Undo2 class="h-4 w-4" /></Button>
            <Button type="button" variant="ghost" size="icon" title="Redo" @click="command('redo')"><Redo2 class="h-4 w-4" /></Button>
            <Button type="button" variant="ghost" size="icon" title="Heading" @click="command('formatBlock', 'h2')"><Heading2 class="h-4 w-4" /></Button>
            <Button type="button" variant="ghost" size="icon" title="Bold" @click="command('bold')"><Bold class="h-4 w-4" /></Button>
            <Button type="button" variant="ghost" size="icon" title="Italic" @click="command('italic')"><Italic class="h-4 w-4" /></Button>
            <Button type="button" variant="ghost" size="icon" title="Bulleted list" @click="command('insertUnorderedList')"><List class="h-4 w-4" /></Button>
            <Button type="button" variant="ghost" size="icon" title="Numbered list" @click="command('insertOrderedList')"><ListOrdered class="h-4 w-4" /></Button>
            <Button type="button" variant="ghost" size="icon" title="Link" @click="addLink"><Link2 class="h-4 w-4" /></Button>
        </div>
        <div ref="editor" contenteditable="true" class="prose prose-sm min-h-72 max-w-none p-4 focus:outline-none" @input="sync" @blur="sync" />
    </div>
</template>
