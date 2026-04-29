<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3'
import { LifeBuoy } from 'lucide-vue-next'
import { computed } from 'vue'

const page = usePage()

const href = computed(() => {
    const currentPath = page.url
    const origin = window.location.origin
    const currentFullUrl = `${origin}${currentPath}`

    // Prevent nesting source_url if already on ticket create page
    if (currentPath.startsWith('/tickets/create')) {
        const url = new URL(currentFullUrl)
        const existingSourceUrl = url.searchParams.get('source_url')

        return existingSourceUrl
            ? `/tickets/create?source_url=${encodeURIComponent(existingSourceUrl)}`
            : '/tickets/create'
    }

    return `/tickets/create?source_url=${encodeURIComponent(currentFullUrl)}`
})
</script>

<template>
    <div class="px-2 pb-2">
        <Link
            :href="href"
            class="flex w-full items-center gap-2 rounded-md px-3 py-2 text-sm text-sidebar-foreground hover:bg-sidebar-accent hover:text-sidebar-accent-foreground transition"
            title="Submit bug or change request"
        >
            <LifeBuoy class="h-4 w-4 shrink-0" />
            <span>Change Ticket</span>
        </Link>
    </div>
</template>