<script setup lang="ts">
import type { Component } from 'vue'
import { Link } from '@inertiajs/vue3'
import { Inbox } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'

withDefaults(defineProps<{
    title: string
    description?: string
    icon?: Component
    actionLabel?: string
    actionHref?: string
}>(), {
    description: '',
    icon: Inbox,
    actionLabel: '',
    actionHref: '',
})
</script>

<template>
    <div class="flex min-h-56 flex-col items-center justify-center rounded-xl border border-dashed bg-muted/20 px-6 py-10 text-center">
        <div class="rounded-full border bg-background p-3 text-muted-foreground shadow-sm">
            <component :is="icon" class="h-6 w-6" aria-hidden="true" />
        </div>
        <h3 class="mt-4 text-base font-semibold">{{ title }}</h3>
        <p v-if="description" class="mt-1 max-w-md text-sm text-muted-foreground">{{ description }}</p>
        <div v-if="$slots.actions || (actionLabel && actionHref)" class="mt-5 flex flex-wrap justify-center gap-2">
            <slot name="actions">
                <Button v-if="actionLabel && actionHref" as-child>
                    <Link :href="actionHref">{{ actionLabel }}</Link>
                </Button>
            </slot>
        </div>
    </div>
</template>
