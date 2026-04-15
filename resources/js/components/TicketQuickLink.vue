<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import { LifeBuoy } from 'lucide-vue-next'
import { onMounted, ref } from 'vue'

const href = ref('/tickets/create')

onMounted(() => {
    href.value = `/tickets/create?source_url=${encodeURIComponent(window.location.href)}`
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