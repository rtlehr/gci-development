<script setup lang="ts">
import { computed, provide, ref } from 'vue'
import { usePage } from '@inertiajs/vue3'
import FlashMessages from '@/components/FlashMessages.vue'
import HelpPanel from '@/components/ui/HelpPanel.vue'
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue'
import type { BreadcrumbItem } from '@/types'

const props = defineProps<{
    breadcrumbs?: BreadcrumbItem[]
    helpKey?: string
}>()

const page = usePage()
const helpOpen = ref(false)

const currentHelpKey = computed(() => {
    if (props.helpKey && props.helpKey.trim() !== '') {
        return props.helpKey
    }

    const componentName = page.component ?? ''

    // Example:
    // "People/Create" -> "people.create"
    // "Positions/Edit" -> "positions.edit"
    return componentName.replace(/\//g, '.').toLowerCase()
})

const openHelpPanel = () => {
    helpOpen.value = true
}

const closeHelpPanel = () => {
    helpOpen.value = false
}

const toggleHelpPanel = () => {
    helpOpen.value = !helpOpen.value
}

provide('openHelpPanel', openHelpPanel)
provide('closeHelpPanel', closeHelpPanel)
provide('toggleHelpPanel', toggleHelpPanel)
provide('helpOpen', helpOpen)
provide('currentHelpKey', currentHelpKey)
</script>

<template>
    <div class="flex min-h-screen w-full">
        <div class="min-w-0 flex-1">
            <AppSidebarLayout :breadcrumbs="breadcrumbs ?? []">
                <FlashMessages />
                <slot />
            </AppSidebarLayout>
        </div>

        <HelpPanel
            :open="helpOpen"
            :help-key="currentHelpKey"
            @close="closeHelpPanel"
        />
    </div>
</template>