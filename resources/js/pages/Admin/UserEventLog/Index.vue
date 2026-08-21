<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { useAuth } from '@/composables/useAuth';
import { Permissions } from '@/constants/permissions';
import { CalendarDays, Download, FilePenLine, UsersRound } from 'lucide-vue-next';
import { reactive } from 'vue';
import ListTableShell from '@/components/Lists/ListTableShell.vue';
import PageContainer from '@/components/layout/PageContainer.vue';
import PageHeader from '@/components/layout/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

const { can } = useAuth();

const props = defineProps<{
    days: any;
    filters: { from: string; to: string };
    today: { date: string; users: number; events: number; changes: number; exports: number };
}>();

const filters = reactive({
    from: props.filters.from ?? '',
    to: props.filters.to ?? '',
});

const cards = [
    { key: 'users', label: 'Active Users', icon: UsersRound },
    { key: 'events', label: 'Events', icon: CalendarDays },
    { key: 'changes', label: 'Changes', icon: FilePenLine },
    { key: 'exports', label: 'Exports', icon: Download },
] as const;

function applyFilters(): void {
    router.get('/admin/user-event-log', { ...filters }, { preserveState: true, replace: true });
}

function resetFilters(): void {
    filters.from = '';
    filters.to = '';
    applyFilters();
}

function goToPage(url: string | null): void {
    if (url) router.visit(url, { preserveState: true });
}

function formatDate(date: string): string {
    return new Date(`${date}T12:00:00`).toLocaleDateString(undefined, {
        year: 'numeric', month: 'long', day: 'numeric',
    });
}

function formatDateTime(value: string | null): string {
    return value ? new Date(value).toLocaleString() : '—';
}

function exportUrl(format: 'csv' | 'splunk'): string {
    const params = new URLSearchParams();
    if (filters.from) params.set('from', filters.from);
    if (filters.to) params.set('to', filters.to);
    const query = params.toString();
    return `/admin/user-event-log/export/${format}${query ? `?${query}` : ''}`;
}
</script>

<template>
    <Head title="User Event Log" />

    <PageContainer class="space-y-6">
        <PageHeader
            eyebrow="Security and Access"
            title="User Event Log"
            description="Review when users accessed IRAD, then drill into each user's activity and the records they worked with."
        >
            <template #actions>
                <div v-if="can(Permissions.USER_EVENT_LOG_EXPORT)" class="flex flex-wrap gap-2">
                    <Button as-child variant="outline"><a :href="exportUrl('csv')">Export CSV</a></Button>
                    <Button as-child><a :href="exportUrl('splunk')">Export for Splunk</a></Button>
                </div>
            </template>
        </PageHeader>

        <section class="space-y-3" aria-labelledby="today-summary-heading">
            <div>
                <h2 id="today-summary-heading" class="text-lg font-semibold">Today</h2>
                <p class="text-sm text-muted-foreground">{{ formatDate(today.date) }}</p>
            </div>
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div v-for="card in cards" :key="card.key" class="rounded-xl border bg-background p-4 shadow-sm">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">{{ card.label }}</p>
                            <p class="mt-2 text-2xl font-semibold">{{ today[card.key].toLocaleString() }}</p>
                        </div>
                        <component :is="card.icon" class="h-5 w-5 text-muted-foreground" aria-hidden="true" />
                    </div>
                </div>
            </div>
        </section>

        <section class="rounded-xl border bg-background p-4 shadow-sm" aria-label="Date filters">
            <form class="flex flex-col gap-4 lg:flex-row lg:items-end" @submit.prevent="applyFilters">
                <div class="space-y-2">
                    <Label for="event-log-from">From</Label>
                    <Input id="event-log-from" v-model="filters.from" type="date" />
                </div>
                <div class="space-y-2">
                    <Label for="event-log-to">To</Label>
                    <Input id="event-log-to" v-model="filters.to" type="date" />
                </div>
                <div class="flex gap-2">
                    <Button type="submit">Apply Filters</Button>
                    <Button type="button" variant="outline" @click="resetFilters">Clear</Button>
                </div>
            </form>
        </section>

        <ListTableShell label="User event log dates">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Date</TableHead>
                        <TableHead class="text-right">Users</TableHead>
                        <TableHead class="text-right">Events</TableHead>
                        <TableHead>First Activity</TableHead>
                        <TableHead>Last Activity</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-if="!days.data?.length">
                        <TableCell colspan="5" class="py-8 text-center text-muted-foreground">No user activity found.</TableCell>
                    </TableRow>
                    <TableRow v-for="day in days.data" :key="day.date" class="hover:bg-muted/50">
                        <TableCell>
                            <Link :href="`/admin/user-event-log/${day.date}`" class="font-semibold text-primary hover:underline">
                                {{ formatDate(day.date) }}
                            </Link>
                        </TableCell>
                        <TableCell class="text-right">{{ day.user_count.toLocaleString() }}</TableCell>
                        <TableCell class="text-right">{{ day.event_count.toLocaleString() }}</TableCell>
                        <TableCell>{{ formatDateTime(day.first_activity_at) }}</TableCell>
                        <TableCell>{{ formatDateTime(day.last_activity_at) }}</TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </ListTableShell>

        <nav v-if="days.last_page > 1" class="flex flex-wrap items-center justify-between gap-3" aria-label="Event log date pagination">
            <p class="text-sm text-muted-foreground">Page {{ days.current_page }} of {{ days.last_page }}</p>
            <div class="flex gap-2">
                <Button type="button" variant="outline" :disabled="!days.prev_page_url" @click="goToPage(days.prev_page_url)">Previous</Button>
                <Button type="button" variant="outline" :disabled="!days.next_page_url" @click="goToPage(days.next_page_url)">Next</Button>
            </div>
        </nav>
    </PageContainer>
</template>
