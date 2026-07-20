<script setup lang="ts">
import { computed } from 'vue'
import { AlertCircle } from 'lucide-vue-next'
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert'

const props = withDefaults(defineProps<{
    errors?: Record<string, string | undefined>
    title?: string
}>(), {
    errors: () => ({}),
    title: 'Please review the highlighted fields',
})

const messages = computed(() =>
    [...new Set(Object.values(props.errors).filter((message): message is string => Boolean(message)))],
)
</script>

<template>
    <Alert v-if="messages.length" variant="destructive" role="alert" aria-live="polite">
        <AlertCircle class="h-4 w-4" />
        <AlertTitle>{{ title }}</AlertTitle>
        <AlertDescription>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                <li v-for="message in messages" :key="message">{{ message }}</li>
            </ul>
        </AlertDescription>
    </Alert>
</template>
