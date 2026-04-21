<script setup lang="ts">
import { inject } from 'vue'
import { HelpCircle } from 'lucide-vue-next'

const props = defineProps<{
    helpKey?: string
}>()

const toggleHelpPanel = inject<(key?: string) => void>('toggleHelpPanel')
</script>

<template>
    <button
        type="button"
        @click="toggleHelpPanel?.(helpKey)"
        class="cursor-pointer"
        title="Help"
    >
        <HelpCircle class="h-4 w-4" />
    </button>
</template>