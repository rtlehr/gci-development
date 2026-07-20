<script setup lang="ts">
import { inject } from 'vue'
import { HelpCircle } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'

const props = defineProps<{
    helpKey?: string
}>()

const toggleHelpPanel = inject<(key?: string) => void>('toggleHelpPanel')
</script>

<template>
    <Button
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
