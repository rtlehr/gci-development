<template>
    <button
        @click="openHelp"
        title="Help"
    >
        <HelpCircle class="h-4 w-4 text-black" />
    </button>
</template>

<script setup>
import { HelpCircle } from 'lucide-vue-next'

defineProps({
    helpKey: {
        type: String,
        default: '',
    },
})

const openHelp = () => {
    window.open('about:blank', '_blank')
}
</script>