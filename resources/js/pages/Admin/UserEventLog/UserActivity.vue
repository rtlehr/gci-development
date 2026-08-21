<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { useAuth } from '@/composables/useAuth';
import { Permissions } from '@/constants/permissions';
import { ExternalLink } from 'lucide-vue-next';
import { reactive } from 'vue';
import ListFilters from '@/components/Lists/ListFilters.vue';
import ListTableShell from '@/components/Lists/ListTableShell.vue';
import PageContainer from '@/components/layout/PageContainer.vue';
import PageHeader from '@/components/layout/PageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

const { can } = useAuth();

const props = defineProps<{
    date: string;
    user: { id: number; name: string; email: string | null };
    events: any;
    modules: string[];
    eventTypes: string[];
    filters: { search: string; module: string; event_type: string };
}>();

const filters = reactive({
    search: props.filters.search ?? '',
    module: props.filters.module || 'all',
    event_type: props.filters.event_type || 'all',
});

function applyFilters(): void {
    router.get(
        `/admin/user-event-log/${props.date}/users/${props.user.id}`,
        {
            search: filters.search,
            module: filters.module === 'all' ? '' : filters.module,
            event_type: filters.event_type === 'all' ? '' : filters.event_type,
        },
        { preserveState: true, replace: true },
    );
}

function resetFilters(): void {
    filters.search = '';
    filters.module = 'all';
    filters.event_type = 'all';
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

function formatTime(value: string): string {
    return new Date(value).toLocaleTimeString([], { hour: 'numeric', minute: '2-digit', second: '2-digit' });
}

function humanize(value: string | null): string {
    if (!value) return '—';
    return value.replaceAll('_', ' ').replace(/\b\w/g, (char) => char.toUpperCase());
}

function formatChangeValue(value: unknown): string {
    if (value === null || value === undefined || value === '') return '—';
    if (typeof value === 'boolean') return value ? 'Yes' : 'No';
    return String(value);
}

function exportUrl(format: 'csv' | 'splunk'): string {
    const params = new URLSearchParams();
    if (filters.search) params.set('search', filters.search);
    if (filters.module !== 'all') params.set('module', filters.module);
    if (filters.event_type !== 'all') params.set('event_type', filters.event_type);
    const query = params.toString();
    return `/admin/user-event-log/${props.date}/users/${props.user.id}/export/${format}${query ? `?${query}` : ''}`;
}

function changeSummary(event: any): string[] {
    const changes = event?.metadata?.changes;
    if (!changes || typeof changes !== 'object') return [];

    return Object.entries(changes).map(([field, values]: [string, any]) =>
        `${humanize(field)}: ${formatChangeValue(values?.from)} → ${formatChangeValue(values?.to)}`,
    );
}
</script>

<template>
    <Head :title="`${user.name} Activity - ${formatDate(date)}`" />

    <PageContainer class="space-y-6">
        <PageHeader
            eyebrow="User Activity"
            :title="user.name"
            :description="`Activity for ${formatDate(date)}. Details link directly to the record or workflow involved whenever that record still exists.`"
            :back-href="`/admin/user-event-log/${date}`"
            back-label="Users for this Date"
        >
            <template #actions>
                <div v-if="can(Permissions.USER_EVENT_LOG_EXPORT)" class="flex flex-wrap gap-2">
                    <Button as-child variant="outline"><a :href="exportUrl('csv')">Export CSV</a></Button>
                    <Button as-child><a :href="exportUrl('splunk')">Export for Splunk</a></Button>
                </div>
            </template>
            <template #meta>
                <span v-if="user.email">{{ user.email }}</span>
                <span>{{ events.total.toLocaleString() }} matching events</span>
            </template>
        </PageHeader>

        <ListFilters
            v-model:search="filters.search"
            search-placeholder="Search activity, details, route, or page..."
            @apply="applyFilters"
            @reset="resetFilters"
        >
            <template #filters>
                <div class="w-full space-y-2 sm:w-48">
                    <Label for="event-module">Module</Label>
                    <Select v-model="filters.module">
                        <SelectTrigger id="event-module"><SelectValue placeholder="All modules" /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All modules</SelectItem>
                            <SelectItem v-for="module in modules" :key="module" :value="module">{{ humanize(module) }}</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                <div class="w-full space-y-2 sm:w-48">
                    <Label for="event-type">Event Type</Label>
                    <Select v-model="filters.event_type">
                        <SelectTrigger id="event-type"><SelectValue placeholder="All event types" /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All event types</SelectItem>
                            <SelectItem v-for="eventType in eventTypes" :key="eventType" :value="eventType">{{ humanize(eventType) }}</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </template>
        </ListFilters>

        <ListTableShell label="User activity events">
            <Table class="min-w-[950px]">
                <TableHeader>
                    <TableRow>
                        <TableHead>Time</TableHead>
                        <TableHead>Activity</TableHead>
                        <TableHead>Module</TableHead>
                        <TableHead>Details</TableHead>
                        <TableHead>Page / Route</TableHead>
                        <TableHead>IP Address</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-if="!events.data?.length">
                        <TableCell colspan="6" class="py-8 text-center text-muted-foreground">No activity matches the current filters.</TableCell>
                    </TableRow>
                    <TableRow v-for="event in events.data" :key="event.id" class="align-top hover:bg-muted/50">
                        <TableCell class="whitespace-nowrap font-medium">{{ formatTime(event.occurred_at) }}</TableCell>
                        <TableCell>
                            <Badge variant="outline">{{ humanize(event.event_type) }}</Badge>
                            <div v-if="event.action && event.action !== event.event_type" class="mt-1 text-xs text-muted-foreground">
                                {{ humanize(event.action) }}
                            </div>
                        </TableCell>
                        <TableCell>{{ humanize(event.module) }}</TableCell>
                        <TableCell class="max-w-md">
                            <Link
                                v-if="event.detail?.href"
                                :href="event.detail.href"
                                class="inline-flex items-start gap-1 font-semibold text-primary hover:underline"
                            >
                                <span>{{ event.detail.label }}</span>
                                <ExternalLink class="mt-0.5 h-3.5 w-3.5 shrink-0" aria-hidden="true" />
                            </Link>
                            <span v-else class="font-medium">{{ event.detail?.label || event.subject_label || '—' }}</span>
                            <div v-if="event.description && event.description !== event.detail?.label" class="mt-1 text-xs text-muted-foreground">
                                {{ event.description }}
                            </div>
                            <div v-if="changeSummary(event).length" class="mt-2 space-y-1 text-xs text-muted-foreground">
                                <div v-for="change in changeSummary(event)" :key="change">{{ change }}</div>
                            </div>
                        </TableCell>
                        <TableCell class="max-w-xs text-xs text-muted-foreground">
                            <div>{{ event.route_name || '—' }}</div>
                            <div v-if="event.path" class="mt-1 break-all">{{ event.path }}</div>
                        </TableCell>
                        <TableCell class="whitespace-nowrap text-sm text-muted-foreground">{{ event.ip_address || '—' }}</TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </ListTableShell>

        <nav v-if="events.last_page > 1" class="flex flex-wrap items-center justify-between gap-3" aria-label="Activity pagination">
            <p class="text-sm text-muted-foreground">Page {{ events.current_page }} of {{ events.last_page }}</p>
            <div class="flex gap-2">
                <Button type="button" variant="outline" :disabled="!events.prev_page_url" @click="goToPage(events.prev_page_url)">Previous</Button>
                <Button type="button" variant="outline" :disabled="!events.next_page_url" @click="goToPage(events.next_page_url)">Next</Button>
            </div>
        </nav>
    </PageContainer>
</template>
