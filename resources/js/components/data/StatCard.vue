<script setup lang="ts">
import type { Component } from 'vue'
import { Card, CardContent } from '@/components/ui/card'

defineProps<{
    label: string
    value: string | number
    description?: string
    icon?: Component
}>()
</script>

<template>
    <Card>
        <CardContent class="flex items-start justify-between gap-4 p-5">
            <div class="min-w-0">
                <p class="text-sm font-medium text-muted-foreground">{{ label }}</p>
                <p class="mt-2 text-2xl font-semibold tracking-tight">{{ value }}</p>
                <p v-if="description" class="mt-1 text-xs text-muted-foreground">{{ description }}</p>
            </div>
            <div v-if="icon" class="rounded-lg border bg-muted/50 p-2.5 text-muted-foreground">
                <component :is="icon" class="h-5 w-5" aria-hidden="true" />
            </div>
        </CardContent>
    </Card>
</template>
