<script setup lang="ts">
import { ref, watch } from 'vue'
import { X } from 'lucide-vue-next'

const props = defineProps<{
    open: boolean
    helpKey?: string
}>()

defineEmits<{
    (e: 'close'): void
}>()

const title = ref('Page Help')
const contentHtml = ref('<p>Help content has not been added for this page yet.</p>')
const loading = ref(false)

const loadHelp = async () => {
    if (!props.helpKey) {
        title.value = 'Page Help'
        contentHtml.value = '<p>Help content has not been added for this page yet.</p>'
        return
    }

    loading.value = true

    try {
        const response = await fetch(`/page-help/${encodeURIComponent(props.helpKey)}`)

        if (!response.ok) {
            throw new Error('Failed to load help content.')
        }

        const data = await response.json()

        title.value = data.title ?? 'Page Help'
        contentHtml.value = data.content_html ?? '<p>Help content has not been added for this page yet.</p>'
    } catch (error) {
        title.value = 'Page Help'
        contentHtml.value = '<p>There was a problem loading the help content.</p>'
    } finally {
        loading.value = false
    }
}

watch(
    () => [props.open, props.helpKey],
    ([open]) => {
        if (open) {
            loadHelp()
        }
    },
    { immediate: true }
)
</script>

<template>
    <aside
        v-if="open"
        class="flex h-full w-[380px] min-w-[380px] max-w-[380px] flex-col border-l bg-background"
    >
        <div class="flex items-center justify-between border-b px-4 py-3">
            <div>
                <h2 class="text-base font-semibold">{{ title }}</h2>
                <p class="text-sm text-muted-foreground">Instructions for this page</p>
            </div>

            <button
                type="button"
                @click="$emit('close')"
                class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-black text-black transition hover:bg-black hover:text-white"
                title="Close help"
            >
                <X class="h-4 w-4" />
            </button>
        </div>

        <div class="flex-1 overflow-y-auto p-4">
            <div v-if="loading" class="text-sm text-muted-foreground">
                Loading help...
            </div>

            <div
                v-else
                class="text-sm leading-6
                    [&_h1]:mb-4 [&_h1]:text-2xl [&_h1]:font-bold
                    [&_h2]:mb-3 [&_h2]:text-xl [&_h2]:font-semibold
                    [&_h3]:mb-2 [&_h3]:text-lg [&_h3]:font-semibold
                    [&_p]:mb-4
                    [&_ul]:mb-4 [&_ul]:list-disc [&_ul]:pl-6
                    [&_ol]:mb-4 [&_ol]:list-decimal [&_ol]:pl-6
                    [&_li]:mb-1
                    [&_a]:text-blue-600 [&_a]:underline"
                v-html="contentHtml"
            ></div>

        </div>
    </aside>
</template>