<script setup lang="ts">
import { CircleAlert } from 'lucide-vue-next'
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert'

defineProps<{
    error?: string | null
}>()
</script>

<template>
    <Alert v-if="error" variant="destructive" role="alert">
        <CircleAlert class="h-4 w-4" aria-hidden="true" />
        <AlertTitle>Download failed</AlertTitle>
        <AlertDescription>{{ error }}</AlertDescription>
    </Alert>
</template>
