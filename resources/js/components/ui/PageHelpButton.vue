<script setup lang="ts">
import { computed, inject } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { HelpCircle } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'

const props = defineProps<{
    helpKey?: string
}>()

const toggleHelpPanel = inject<(key?: string) => void>('toggleHelpPanel')
const page = usePage()
const featureEnabled = computed(() => {
    const enabled = (page.props.siteSettings as { features?: { help?: boolean } })?.features?.help ?? true
    return enabled || page.url.startsWith('/admin')
})
</script>

<template>
    <Button
        v-if="featureEnabled"
        type="button"
        variant="outline"
        size="icon"
        aria-label="Open page help"
        title="Open page help"
        @click="toggleHelpPanel?.(props.helpKey)"
    >
        <HelpCircle class="h-4 w-4" aria-hidden="true" />
    </Button>
</template>
