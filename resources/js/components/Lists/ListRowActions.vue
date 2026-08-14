<script setup lang="ts">
import { MoreHorizontal } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'

withDefaults(defineProps<{
    label?: string
    ariaLabel?: string
}>(), {
    label: 'Actions',
    ariaLabel: 'Open actions menu',
})
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button variant="ghost" size="icon" :aria-label="ariaLabel" aria-haspopup="menu">
                <MoreHorizontal class="h-4 w-4" aria-hidden="true" />
            </Button>
        </DropdownMenuTrigger>

        <DropdownMenuContent align="end">
            <DropdownMenuLabel>{{ label }}</DropdownMenuLabel>
            <DropdownMenuSeparator />
            <slot />
        </DropdownMenuContent>
    </DropdownMenu>
</template>
