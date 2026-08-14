<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { useAuth } from '@/composables/useAuth'
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet'

const props = defineProps<{
    open: boolean
    helpKey?: string
}>()

const emit = defineEmits<{
    (e: 'close'): void
}>()

const { can } = useAuth()
const page = usePage()

const featureEnabled = computed(() => {
    const enabled = (page.props.siteSettings as { features?: { help?: boolean } })?.features?.help ?? true
    return enabled || page.url.startsWith('/admin')
})

const createHelpUrl = computed(() => {
    if (!props.helpKey) return '/admin/page-help/create'

    return `/admin/page-help/create?help_key=${encodeURIComponent(props.helpKey)}`
})

const title = ref('Page Help')
const EMPTY_HTML = '<p>Help content has not been added for this page yet.</p>'
const contentHtml = ref(EMPTY_HTML)
const loading = ref(false)

const isEmpty = computed(() => {
    return !contentHtml.value || contentHtml.value === EMPTY_HTML
})

const loadHelp = async () => {
    if (!props.helpKey) {
        title.value = 'Page Help'
        contentHtml.value = EMPTY_HTML
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
        contentHtml.value = data.content_html ?? EMPTY_HTML
    } catch {
        title.value = 'Page Help'
        contentHtml.value = '<p>There was a problem loading the help content.</p>'
    } finally {
        loading.value = false
    }
}

const handleOpenChange = (nextOpen: boolean) => {
    if (!nextOpen && props.open) {
        emit('close')
    }
}

watch(
    () => [props.open, props.helpKey] as const,
    ([open]) => {
        if (open && featureEnabled.value) {
            loadHelp()
        }
    },
    { immediate: true },
)
</script>

<template>
    <Sheet :open="open && featureEnabled" @update:open="handleOpenChange">
        <SheetContent
            side="right"
            class="w-[520px] max-w-[calc(100vw-1rem)] gap-0 p-0 sm:max-w-[520px]"
        >
            <SheetHeader class="border-b px-4 py-3 pr-12">
                <SheetTitle>{{ title }}</SheetTitle>
                <SheetDescription>Instructions for this page</SheetDescription>
            </SheetHeader>

            <div class="flex-1 overflow-y-auto p-4">
                <div
                    v-if="loading"
                    role="status"
                    aria-live="polite"
                    class="text-sm text-muted-foreground"
                >
                    Loading help...
                </div>

                <div v-else-if="isEmpty" class="space-y-3 text-sm">
                    <div class="text-muted-foreground italic">
                        There is no help for this screen.
                    </div>

                    <Link
                        v-if="can('view_admin')"
                        :href="createHelpUrl"
                        class="text-blue-600 underline focus-visible:rounded-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                    >
                        Would you like to create it?
                    </Link>
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
                        [&_a]:underline [&_a]:underline-offset-2
                        [&_a:focus-visible]:rounded-sm [&_a:focus-visible]:outline-none [&_a:focus-visible]:ring-2 [&_a:focus-visible]:ring-ring"
                    v-html="contentHtml"
                ></div>
            </div>
        </SheetContent>
    </Sheet>
</template>
