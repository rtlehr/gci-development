<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { useAuth } from '@/composables/useAuth';
import { Permissions } from '@/constants/permissions';
import { ref } from 'vue';
import ListFilters from '@/components/Lists/ListFilters.vue';
import ListTableShell from '@/components/Lists/ListTableShell.vue';
import PageContainer from '@/components/layout/PageContainer.vue';
import PageHeader from '@/components/layout/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

const { can } = useAuth();

const props = defineProps<{
    date: string;
    users: any;
    filters: { search: string };
}>();

const search = ref(props.filters.search ?? '');

function applyFilters(): void {
    router.get(`/admin/user-event-log/${props.date}`, { search: search.value }, { preserveState: true, replace: true });
}

function resetFilters(): void {
    search.value = '';
    applyFilters();
}

function goToPage(url: string | null): void {
    if (url) router.visit(url, { preserveState: true });
}

function formatDate(date: string): string {
    return new Date(`${date}T12:00:00`).toLocaleDateString(undefined, {
        weekday: 'long', year: 'numeric', month: 'long', day: 'numeric',
    });
}

function formatTime(value: string | null): string {
    return value ? new Date(value).toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' }) : '—';
}

function exportUrl(format: 'csv' | 'splunk'): string {
    const params = new URLSearchParams();
    if (search.value) params.set('search', search.value);
    const query = params.toString();
    return `/admin/user-event-log/${props.date}/export/${format}${query ? `?${query}` : ''}`;
}
</script>

<template>
    <Head :title="`User Event Log - ${formatDate(date)}`" />

    <PageContainer class="space-y-6">
        <PageHeader
            eyebrow="User Event Log"
            :title="formatDate(date)"
            description="Users who accessed IRAD on this date. Select a user to review their activity in chronological order."
            back-href="/admin/user-event-log"
            back-label="All Dates"
        >
            <template #actions>
                <div v-if="can(Permissions.USER_EVENT_LOG_EXPORT)" class="flex flex-wrap gap-2">
                    <Button as-child variant="outline"><a :href="exportUrl('csv')">Export CSV</a></Button>
                    <Button as-child><a :href="exportUrl('splunk')">Export for Splunk</a></Button>
                </div>
            </template>
        </PageHeader>

        <ListFilters
            v-model:search="search"
            search-placeholder="Search by user name or email..."
            apply-label="Search"
            @apply="applyFilters"
            @reset="resetFilters"
        />

        <ListTableShell label="Users active on selected date">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>User</TableHead>
                        <TableHead class="text-right">Events</TableHead>
                        <TableHead>First Activity</TableHead>
                        <TableHead>Last Activity</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-if="!users.data?.length">
                        <TableCell colspan="4" class="py-8 text-center text-muted-foreground">No users found for this date.</TableCell>
                    </TableRow>
                    <TableRow v-for="entry in users.data" :key="`${entry.user_id}-${entry.email}`" class="hover:bg-muted/50">
                        <TableCell>
                            <Link
                                v-if="entry.user_id"
                                :href="`/admin/user-event-log/${date}/users/${entry.user_id}`"
                                class="font-semibold text-primary hover:underline"
                            >
                                {{ entry.name }}
                            </Link>
                            <span v-else class="font-semibold">{{ entry.name }}</span>
                            <div v-if="entry.email" class="mt-1 text-xs text-muted-foreground">{{ entry.email }}</div>
                        </TableCell>
                        <TableCell class="text-right">{{ entry.event_count.toLocaleString() }}</TableCell>
                        <TableCell>{{ formatTime(entry.first_activity_at) }}</TableCell>
                        <TableCell>{{ formatTime(entry.last_activity_at) }}</TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </ListTableShell>

        <nav v-if="users.last_page > 1" class="flex flex-wrap items-center justify-between gap-3" aria-label="User pagination">
            <p class="text-sm text-muted-foreground">Page {{ users.current_page }} of {{ users.last_page }}</p>
            <div class="flex gap-2">
                <Button type="button" variant="outline" :disabled="!users.prev_page_url" @click="goToPage(users.prev_page_url)">Previous</Button>
                <Button type="button" variant="outline" :disabled="!users.next_page_url" @click="goToPage(users.next_page_url)">Next</Button>
            </div>
        </nav>
    </PageContainer>
</template>
