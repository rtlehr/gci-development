<script setup lang="ts">
import { ref, watch, computed  } from 'vue'
import { X } from 'lucide-vue-next'
import { useAuth } from '@/composables/useAuth'
import { Link } from '@inertiajs/vue3'

const { can } = useAuth()

const createHelpUrl = computed(() => {
    if (!props.helpKey) return '/admin/page-help/create'

    return `/admin/page-help/create?help_key=${encodeURIComponent(props.helpKey)}`
})

const props = defineProps<{
    open: boolean
    helpKey?: string
}>()

defineEmits<{
    (e: 'close'): void
}>()

const title = ref('Page Help')

const EMPTY_HTML = '<p>Help content has not been added for this page yet.</p>'
const contentHtml = ref(EMPTY_HTML)

const isEmpty = computed(() => {
    return !contentHtml.value || contentHtml.value === EMPTY_HTML
})

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
        class="flex h-full w-[520px] min-w-[520px] max-w-[520px] flex-col border-l bg-background"
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

            <!-- EMPTY STATE -->
            <div v-else-if="isEmpty" class="space-y-3 text-sm">
                <div class="text-muted-foreground italic">
                    There is no help for this screen.
                </div>

                <Link
                    v-if="can('view_admin')"
                    :href="createHelpUrl"
                    class="text-blue-600 underline"
                >
                    Would you like to create it?
                </Link>
            </div>

            <!-- NORMAL CONTENT -->
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
                "
                v-html="contentHtml"
            ></div>
        </div>


    </aside>
</template>