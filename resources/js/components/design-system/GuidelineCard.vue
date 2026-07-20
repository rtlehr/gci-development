<script setup lang="ts">
import type { Component } from 'vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'

defineProps<{
    title: string
    description: string
    icon?: Component
}>()
</script>

<template>
    <Card class="h-full">
        <CardHeader class="pb-3">
            <div class="flex items-start gap-3">
                <div v-if="icon" class="rounded-lg border bg-muted/40 p-2 text-muted-foreground">
                    <component :is="icon" class="h-4 w-4" aria-hidden="true" />
                </div>
                <div class="min-w-0">
                    <CardTitle class="text-base">{{ title }}</CardTitle>
                    <CardDescription class="mt-1 leading-5">{{ description }}</CardDescription>
                </div>
            </div>
        </CardHeader>
        <CardContent v-if="$slots.default" class="pt-0 text-sm text-muted-foreground">
            <slot />
        </CardContent>
    </Card>
</template>
