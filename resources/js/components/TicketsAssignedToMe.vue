<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import { LifeBuoy } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'

type Ticket = {
    id: number
    ticket_number: string
    title: string
    request_type: string
    importance: string
    category: string | null
    status: string
    created_at: string
}

defineProps<{
    tickets: Ticket[]
}>()

function formatLabel(value: string | null) {
    if (!value) return ''

    return value
        .replaceAll('_', ' ')
        .replace(/\b\w/g, (letter) => letter.toUpperCase())
}
</script>

<template>
    <div class="rounded-2xl border bg-background p-6 shadow-sm">
        <div class="mb-4 flex items-center justify-between gap-4">
            <div>
                <h2 class="flex items-center gap-2 text-lg font-semibold">
                    <LifeBuoy class="h-5 w-5" />
                    Tickets Assigned To Me
                </h2>

                <p class="mt-1 text-sm text-muted-foreground">
                    Tickets currently assigned to the active user.
                </p>
            </div>

            <Button as-child variant="outline" size="sm"><Link href="/admin/tickets">
                    View All
                </Link></Button>
        </div>

        <div v-if="tickets.length === 0" class="rounded-lg border border-dashed p-6 text-sm text-muted-foreground">
            No tickets are currently assigned to you.
        </div>

        <div v-else class="space-y-3">
            <div
                v-for="ticket in tickets"
                :key="ticket.id"
                class="rounded-xl border p-4 transition hover:bg-muted/40"
            >
                <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                    <div class="space-y-1">
                        <div class="text-sm font-semibold">
                            {{ ticket.ticket_number }} — {{ ticket.title }}
                        </div>

                        <div class="flex flex-wrap gap-2 text-xs text-muted-foreground">
                            <span>Status: {{ formatLabel(ticket.status) }}</span>
                            <span>•</span>
                            <span>Type: {{ formatLabel(ticket.request_type) }}</span>
                            <span>•</span>
                            <span>Importance: {{ formatLabel(ticket.importance) }}</span>
                            <template v-if="ticket.category">
                                <span>•</span>
                                <span>Category: {{ ticket.category }}</span>
                            </template>
                        </div>
                    </div>

                    <Button as-child size="sm" variant="outline"><Link :href="`/admin/tickets/${ticket.id}`">
                            View Ticket
                        </Link></Button>
                </div>
            </div>
        </div>
    </div>
</template>