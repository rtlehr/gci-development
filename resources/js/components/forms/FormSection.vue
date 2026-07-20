<script setup lang="ts">
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'

withDefaults(defineProps<{
    title: string
    description?: string
    contentClass?: string
}>(), {
    description: '',
    contentClass: 'grid gap-5 p-5 sm:p-6',
})
</script>

<template>
    <Card class="overflow-hidden">
        <CardHeader class="border-b bg-muted/20">
            <CardTitle class="text-lg">{{ title }}</CardTitle>
            <CardDescription v-if="description" class="max-w-3xl">{{ description }}</CardDescription>
        </CardHeader>
        <CardContent :class="contentClass">
            <slot />
        </CardContent>
    </Card>
</template>
