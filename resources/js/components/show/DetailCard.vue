<script setup lang="ts">
import type { Component } from 'vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'

defineProps<{
    title: string
    description?: string
    icon?: Component
}>()
</script>

<template>
    <Card class="h-full">
        <CardHeader class="space-y-1 border-b bg-muted/20 pb-4">
            <div class="flex items-start gap-3">
                <div v-if="icon" class="rounded-lg border bg-background p-2 text-muted-foreground">
                    <component :is="icon" class="h-4 w-4" aria-hidden="true" />
                </div>
                <div class="min-w-0">
                    <CardTitle class="text-base">{{ title }}</CardTitle>
                    <p v-if="description" class="mt-1 text-sm text-muted-foreground">{{ description }}</p>
                </div>
            </div>
        </CardHeader>
        <CardContent class="p-5">
            <slot />
        </CardContent>
    </Card>
</template>
