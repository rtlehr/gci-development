<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { LifeBuoy, Plus, Search } from 'lucide-vue-next';
import { reactive } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

const props = defineProps<{
    tickets: {
        data: Array<Record<string, any>>;
        links: Array<{ url: string | null; label: string; active: boolean }>;
        total: number;
        from: number | null;
        to: number | null;
    };
    filters: {
        search?: string;
        status?: string;
    };
}>();

const filters = reactive({
    search: props.filters.search ?? '',
    status: props.filters.status ?? '',
});

function applyFilters(): void {
    router.get('/portal/tickets', filters, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function resetFilters(): void {
    filters.search = '';
    filters.status = '';
    applyFilters();
}

function formatLabel(value: string): string {
    return value.replaceAll('_', ' ').replace(/\b\w/g, (letter) => letter.toUpperCase());
}

function formatDate(value: string): string {
    return new Intl.DateTimeFormat('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    }).format(new Date(value));
}
</script>

<template>
    <Head title="My Support Tickets" />

    <section class="border-b border-[#e3e3e3] bg-white">
        <div class="mx-auto flex max-w-7xl flex-col gap-5 px-4 py-10 sm:px-6 md:flex-row md:items-end md:justify-between lg:px-8">
            <div>
                <p class="text-sm font-bold uppercase tracking-[0.16em] text-[#005c43]">Support</p>
                <h1 class="mt-2 text-3xl font-bold tracking-tight text-[#3a3a3a]">My support tickets</h1>
                <p class="mt-3 max-w-2xl text-[#3a3a3a]/70">Submit a request and follow its progress from the Portal.</p>
            </div>

            <Button as-child class="bg-[#005c43] text-white hover:bg-[#004735]">
                <Link href="/portal/tickets/create">
                    <Plus class="mr-2 h-4 w-4" />
                    Submit request
                </Link>
            </Button>
        </div>
    </section>

    <div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        <form class="grid gap-3 rounded-xl border border-[#e3e3e3] bg-white p-4 shadow-sm md:grid-cols-[1fr_220px_auto_auto]" @submit.prevent="applyFilters">
            <div class="relative">
                <label for="ticket-search" class="sr-only">Search support tickets</label>
                <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[#3a3a3a]/50" aria-hidden="true" />
                <Input id="ticket-search" v-model="filters.search" class="pl-9" placeholder="Search ticket number, title, or description" />
            </div>

            <div>
                <label for="ticket-status-filter" class="sr-only">Filter by status</label>
                <select id="ticket-status-filter" v-model="filters.status" class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm">
                <option value="">All statuses</option>
                <option value="new">New</option>
                <option value="in_progress">In progress</option>
                <option value="on_hold">On hold</option>
                <option value="complete">Complete</option>
                <option value="canceled">Canceled</option>
                </select>
            </div>

            <Button type="submit" class="bg-[#005c43] text-white hover:bg-[#004735]">Search</Button>
            <Button type="button" variant="outline" @click="resetFilters">Reset</Button>
        </form>

        <div v-if="tickets.data.length" class="grid gap-4">
            <Link
                v-for="ticket in tickets.data"
                :key="ticket.id"
                :href="`/portal/tickets/${ticket.id}`"
                class="group rounded-xl border border-[#e3e3e3] bg-white p-5 shadow-sm transition hover:border-[#005c43]/40 hover:shadow-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#005c43]"
            >
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="font-mono text-sm font-semibold text-[#005c43]">{{ ticket.ticket_number }}</span>
                            <span class="rounded-full bg-[#005c43]/10 px-2.5 py-1 text-xs font-semibold text-[#005c43]">{{ formatLabel(ticket.status) }}</span>
                        </div>
                        <h2 class="mt-2 text-lg font-bold text-[#3a3a3a] group-hover:text-[#005c43]">{{ ticket.title }}</h2>
                        <p class="mt-2 text-sm text-[#3a3a3a]/65">{{ formatLabel(ticket.request_type) }} · {{ formatLabel(ticket.importance) }} · {{ ticket.category || 'Other' }}</p>
                    </div>
                    <p class="shrink-0 text-sm text-[#3a3a3a]/60">{{ formatDate(ticket.created_at) }}</p>
                </div>
            </Link>
        </div>

        <div v-else class="rounded-xl border border-dashed border-[#e3e3e3] bg-white px-6 py-14 text-center">
            <LifeBuoy class="mx-auto h-10 w-10 text-[#005c43]" />
            <h2 class="mt-4 text-lg font-bold text-[#3a3a3a]">No support tickets found</h2>
            <p class="mt-2 text-sm text-[#3a3a3a]/65">Submit a request when you need help or want to suggest an improvement.</p>
        </div>

        <div v-if="tickets.links.length > 3" class="flex flex-wrap justify-center gap-1">
            <template v-for="link in tickets.links" :key="link.label">
                <Link
                    v-if="link.url"
                    :href="link.url"
                    class="rounded-md border px-3 py-2 text-sm"
                    :class="link.active ? 'border-[#005c43] bg-[#005c43] text-white' : 'border-[#e3e3e3] bg-white text-[#3a3a3a] hover:bg-[#005c43]/10'"
                    v-html="link.label"
                />
                <span v-else class="rounded-md border border-[#e3e3e3] px-3 py-2 text-sm text-[#3a3a3a]/40" v-html="link.label" />
            </template>
        </div>
    </div>
</template>
