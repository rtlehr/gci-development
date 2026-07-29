<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, ExternalLink, Image as ImageIcon } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';

const props = defineProps<{
    ticket: Record<string, any>;
}>();

function formatLabel(value?: string | null): string {
    if (!value) return '—';
    return value.replaceAll('_', ' ').replace(/\b\w/g, (letter) => letter.toUpperCase());
}

function formatDateTime(value: string): string {
    return new Intl.DateTimeFormat('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    }).format(new Date(value));
}

function activityText(activity: Record<string, any>): string {
    switch (activity.event_type) {
        case 'created':
            return 'Ticket created';
        case 'status_changed':
            return `Status changed from ${formatLabel(activity.old_value)} to ${formatLabel(activity.new_value)}`;
        case 'importance_changed':
            return `Importance changed from ${formatLabel(activity.old_value)} to ${formatLabel(activity.new_value)}`;
        case 'assignment_changed':
            return `Assignment changed from ${activity.old_value || 'Unassigned'} to ${activity.new_value || 'Unassigned'}`;
        case 'resolution_updated':
            return 'Resolution information updated';
        default:
            return formatLabel(activity.event_type);
    }
}
</script>

<template>
    <Head :title="ticket.ticket_number" />

    <section class="border-b border-[#e3e3e3] bg-white">
        <div class="mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:px-8">
            <Link href="/portal/tickets" class="inline-flex items-center gap-2 text-sm font-semibold text-[#005c43] hover:underline">
                <ArrowLeft class="h-4 w-4" />
                Back to my tickets
            </Link>
            <div class="mt-6 flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                <div>
                    <p class="font-mono text-sm font-bold text-[#005c43]">{{ ticket.ticket_number }}</p>
                    <h1 class="mt-2 text-3xl font-bold tracking-tight text-[#3a3a3a]">{{ ticket.title }}</h1>
                    <p class="mt-3 text-[#3a3a3a]/70">Submitted {{ formatDateTime(ticket.created_at) }}</p>
                </div>
                <span class="w-fit rounded-full bg-[#005c43]/10 px-3 py-1.5 text-sm font-semibold text-[#005c43]">{{ formatLabel(ticket.status) }}</span>
            </div>
        </div>
    </section>

    <div class="mx-auto grid max-w-6xl gap-6 px-4 py-8 sm:px-6 lg:grid-cols-[minmax(0,1fr)_320px] lg:px-8">
        <div class="space-y-6">
            <section class="rounded-xl border border-[#e3e3e3] bg-white p-6 shadow-sm">
                <h2 class="text-lg font-bold text-[#3a3a3a]">Request details</h2>
                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    <div><p class="text-xs font-semibold uppercase tracking-wide text-[#3a3a3a]/55">Request type</p><p class="mt-1 font-medium">{{ formatLabel(ticket.request_type) }}</p></div>
                    <div><p class="text-xs font-semibold uppercase tracking-wide text-[#3a3a3a]/55">Importance</p><p class="mt-1 font-medium">{{ formatLabel(ticket.importance) }}</p></div>
                    <div><p class="text-xs font-semibold uppercase tracking-wide text-[#3a3a3a]/55">Category</p><p class="mt-1 font-medium">{{ ticket.category || 'Other' }}</p></div>
                    <div><p class="text-xs font-semibold uppercase tracking-wide text-[#3a3a3a]/55">Assigned to</p><p class="mt-1 font-medium">{{ ticket.assigned_to || 'Support team' }}</p></div>
                </div>
                <div class="mt-6 border-t border-[#e3e3e3] pt-5">
                    <p class="text-xs font-semibold uppercase tracking-wide text-[#3a3a3a]/55">Description</p>
                    <p class="mt-2 whitespace-pre-wrap leading-7 text-[#3a3a3a]">{{ ticket.description }}</p>
                </div>

                <div v-if="ticket.source_url" class="mt-6 border-t border-[#e3e3e3] pt-5">
                    <a :href="ticket.source_url" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 text-sm font-semibold text-[#005c43] hover:underline">
                        View source page <ExternalLink class="h-4 w-4" />
                    </a>
                </div>
            </section>

            <section v-if="ticket.screenshot_url" class="rounded-xl border border-[#e3e3e3] bg-white p-6 shadow-sm">
                <h2 class="flex items-center gap-2 text-lg font-bold text-[#3a3a3a]"><ImageIcon class="h-5 w-5 text-[#005c43]" /> Screenshot</h2>
                <a :href="ticket.screenshot_url" target="_blank" rel="noopener noreferrer">
                    <img :src="ticket.screenshot_url" alt="Ticket screenshot" class="mt-4 max-h-[560px] w-full rounded-lg border border-[#e3e3e3] object-contain" />
                </a>
            </section>

            <section v-if="ticket.resolution_notes" class="rounded-xl border border-[#005c43]/25 bg-[#005c43]/5 p-6">
                <h2 class="text-lg font-bold text-[#005c43]">Resolution</h2>
                <p class="mt-3 whitespace-pre-wrap leading-7 text-[#3a3a3a]">{{ ticket.resolution_notes }}</p>
            </section>
        </div>

        <aside class="space-y-6">
            <section class="rounded-xl border border-[#e3e3e3] bg-white p-5 shadow-sm">
                <h2 class="font-bold text-[#3a3a3a]">Activity</h2>
                <ol class="mt-4 space-y-5">
                    <li v-for="activity in ticket.activities" :key="activity.id" class="relative border-l-2 border-[#005c43]/20 pl-4">
                        <span class="absolute -left-[5px] top-1 h-2 w-2 rounded-full bg-[#005c43]" />
                        <p class="text-sm font-medium text-[#3a3a3a]">{{ activityText(activity) }}</p>
                        <p class="mt-1 text-xs text-[#3a3a3a]/55">{{ formatDateTime(activity.created_at) }}<span v-if="activity.changed_by"> · {{ activity.changed_by }}</span></p>
                    </li>
                </ol>
            </section>

            <Button as-child variant="outline" class="w-full"><Link href="/portal/tickets">Return to tickets</Link></Button>
        </aside>
    </div>
</template>
