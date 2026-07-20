<script setup lang="ts">
import { Info } from 'lucide-vue-next'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'

withDefaults(defineProps<{
    title?: string
    description?: string
    sticky?: boolean
}>(), {
    title: 'Information',
    description: '',
    sticky: true,
})
</script>

<template>
    <Card :class="sticky ? 'xl:sticky xl:top-6' : ''">
        <CardHeader>
            <div class="flex items-start gap-3">
                <div class="rounded-lg bg-primary/10 p-2 text-primary">
                    <Info class="h-4 w-4" aria-hidden="true" />
                </div>
                <div>
                    <CardTitle class="text-base">{{ title }}</CardTitle>
                    <CardDescription v-if="description" class="mt-1">{{ description }}</CardDescription>
                </div>
            </div>
        </CardHeader>
        <CardContent class="grid gap-5">
            <slot />
        </CardContent>
    </Card>
</template>
