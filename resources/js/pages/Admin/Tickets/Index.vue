<script setup>
import { computed, reactive } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Badge } from '@/components/ui/badge'
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table'

const props = defineProps({
    tickets: {
        type: Object,
        required: true,
    },
    assignableUsers: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({
            search: '',
            status: '',
            importance: '',
            request_type: '',
            assigned_to_user_id: '',
        }),
    },
})

const filterForm = reactive({
    search: props.filters?.search ?? '',
    status: props.filters?.status ?? '',
    importance: props.filters?.importance ?? '',
    request_type: props.filters?.request_type ?? '',
    assigned_to_user_id: props.filters?.assigned_to_user_id ?? '',
})

const pagesToShow = computed(() => {
    const current = props.tickets.current_page ?? 1
    const last = props.tickets.last_page ?? 1

    const start = Math.max(current - 2, 1)
    const end = Math.min(current + 2, last)

    const pages = []
    for (let i = start; i <= end; i++) {
        pages.push(i)
    }

    return pages
})

function applyFilters() {
    router.get('/admin/tickets', {
        search: filterForm.search,
        status: filterForm.status,
        importance: filterForm.importance,
        request_type: filterForm.request_type,
        assigned_to_user_id: filterForm.assigned_to_user_id,
    }, {
        preserveState: true,
        replace: true,
    })
}

function resetFilters() {
    filterForm.search = ''
    filterForm.status = ''
    filterForm.importance = ''
    filterForm.request_type = ''
    filterForm.assigned_to_user_id = ''

    router.get('/admin/tickets', {}, {
        preserveState: true,
        replace: true,
    })
}

function goToPage(page) {
    router.get('/admin/tickets', {
        page,
        search: filterForm.search,
        status: filterForm.status,
        importance: filterForm.importance,
        request_type: filterForm.request_type,
        assigned_to_user_id: filterForm.assigned_to_user_id,
    }, {
        preserveState: true,
        replace: true,
    })
}

function submittedByName(ticket) {
    const first = ticket.submitted_by?.person?.first_name ?? ''
    const last = ticket.submitted_by?.person?.last_name ?? ''
    const name = `${first} ${last}`.trim()

    return name || ticket.submitted_by?.name || '—'
}

function assignedToName(ticket) {
    if (!ticket.assigned_to) return 'Unassigned'

    const first = ticket.assigned_to?.person?.first_name ?? ''
    const last = ticket.assigned_to?.person?.last_name ?? ''
    const name = `${first} ${last}`.trim()

    return name || ticket.assigned_to?.name || '—'
}

function statusBadgeClass(status) {
    if (status === 'new') return 'bg-gray-500 text-white hover:bg-gray-500'
    if (status === 'in_progress') return 'bg-blue-600 text-white hover:bg-blue-600'
    if (status === 'on_hold') return 'bg-yellow-500 text-black hover:bg-yellow-500'
    if (status === 'complete') return 'bg-green-600 text-white hover:bg-green-600'
    if (status === 'canceled') return 'bg-red-600 text-white hover:bg-red-600'

    return 'bg-gray-200 text-gray-800 hover:bg-gray-200'
}

function importanceBadgeClass(importance) {
    if (importance === 'show_stopper') return 'bg-red-600 text-white hover:bg-red-600'
    if (importance === 'asap') return 'bg-orange-500 text-white hover:bg-orange-500'
    if (importance === 'nice_to_have') return 'bg-gray-500 text-white hover:bg-gray-500'

    return 'bg-gray-200 text-gray-800 hover:bg-gray-200'
}

function formatStatus(status) {
    if (status === 'new') return 'New'
    if (status === 'in_progress') return 'In Progress'
    if (status === 'on_hold') return 'On Hold'
    if (status === 'complete') return 'Complete'
    if (status === 'canceled') return 'Canceled'

    return status || '—'
}

function formatImportance(importance) {
    if (importance === 'show_stopper') return 'Show Stopper'
    if (importance === 'asap') return 'Needed ASAP'
    if (importance === 'nice_to_have') return 'Nice to Have'

    return importance || '—'
}

function formatRequestType(type) {
    if (type === 'bug') return 'Bug'
    if (type === 'improvement') return 'Improvement'

    return type || '—'
}
</script>

<template>
    <div class="p-6 space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold">Tickets</h1>
                <p class="text-sm text-muted-foreground mt-1">
                    Manage bug reports and improvement requests.
                </p>
            </div>
        </div>

        <div class="border rounded-xl p-4 bg-background">
            <form @submit.prevent="applyFilters" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-4">
                    <div class="space-y-2 xl:col-span-2">
                        <Label for="search">Search</Label>
                        <Input
                            id="search"
                            v-model="filterForm.search"
                            placeholder="Search ticket number, title, or description..."
                        />
                    </div>

                    <div class="space-y-2">
                        <Label for="request_type">Request Type</Label>
                        <select
                            id="request_type"
                            v-model="filterForm.request_type"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        >
                            <option value="">All Types</option>
                            <option value="bug">Bug</option>
                            <option value="improvement">Improvement</option>
                        </select>
                    </div>

                    <div class="space-y-2">
                        <Label for="importance">Importance</Label>
                        <select
                            id="importance"
                            v-model="filterForm.importance"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        >
                            <option value="">All Importance</option>
                            <option value="show_stopper">Show Stopper</option>
                            <option value="asap">Needed ASAP</option>
                            <option value="nice_to_have">Nice to Have</option>
                        </select>
                    </div>

                    <div class="space-y-2">
                        <Label for="status">Status</Label>
                        <select
                            id="status"
                            v-model="filterForm.status"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        >
                            <option value="">All Statuses</option>
                            <option value="new">New</option>
                            <option value="in_progress">In Progress</option>
                            <option value="on_hold">On Hold</option>
                            <option value="complete">Complete</option>
                            <option value="canceled">Canceled</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-4">
                    <div class="space-y-2 xl:col-span-2">
                        <Label for="assigned_to_user_id">Assigned To</Label>
                        <select
                            id="assigned_to_user_id"
                            v-model="filterForm.assigned_to_user_id"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        >
                            <option value="">All</option>
                            <option value="unassigned">Unassigned</option>
                            <option
                                v-for="user in assignableUsers"
                                :key="user.id"
                                :value="String(user.id)"
                            >
                                {{ user.name }}
                            </option>
                        </select>
                    </div>

                    <div class="flex gap-2 md:items-end xl:col-span-3">
                        <Button type="submit">Apply</Button>
                        <Button type="button" variant="outline" @click="resetFilters">
                            Reset
                        </Button>
                    </div>
                </div>
            </form>
        </div>

        <div class="border rounded-xl bg-background overflow-hidden">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Ticket</TableHead>
                        <TableHead>Title</TableHead>
                        <TableHead>Type</TableHead>
                        <TableHead>Importance</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead>Submitted By</TableHead>
                        <TableHead>Assigned To</TableHead>
                        <TableHead class="text-right">Actions</TableHead>
                    </TableRow>
                </TableHeader>

                <TableBody>
                    <TableRow v-if="!tickets?.data?.length">
                        <TableCell colspan="8" class="text-center py-8 text-muted-foreground">
                            No tickets found.
                        </TableCell>
                    </TableRow>

                    <TableRow
                        v-for="ticket in tickets.data"
                        :key="ticket.id"
                        class="hover:bg-muted/50"
                    >
                        <TableCell>{{ ticket.ticket_number }}</TableCell>
                        <TableCell>{{ ticket.title }}</TableCell>
                        <TableCell>{{ formatRequestType(ticket.request_type) }}</TableCell>

                        <TableCell>
                            <Badge :class="importanceBadgeClass(ticket.importance)">
                                {{ formatImportance(ticket.importance) }}
                            </Badge>
                        </TableCell>

                        <TableCell>
                            <Badge :class="statusBadgeClass(ticket.status)">
                                {{ formatStatus(ticket.status) }}
                            </Badge>
                        </TableCell>

                        <TableCell>{{ submittedByName(ticket) }}</TableCell>
                        <TableCell>{{ assignedToName(ticket) }}</TableCell>

                        <TableCell class="text-right">
                            <Link :href="`/admin/tickets/${ticket.id}`">
                                <Button variant="outline" size="sm">
                                    Open
                                </Button>
                            </Link>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="text-sm text-muted-foreground">
                Showing {{ tickets.from ?? 0 }} to {{ tickets.to ?? 0 }} of {{ tickets.total ?? 0 }} tickets
            </div>

            <div class="flex items-center gap-2 flex-wrap">
                <Button
                    size="sm"
                    variant="outline"
                    :disabled="tickets.current_page === 1"
                    @click="goToPage(tickets.current_page - 1)"
                >
                    Previous
                </Button>

                <Button
                    v-for="page in pagesToShow"
                    :key="page"
                    size="sm"
                    :variant="page === tickets.current_page ? 'default' : 'outline'"
                    @click="goToPage(page)"
                >
                    {{ page }}
                </Button>

                <Button
                    size="sm"
                    variant="outline"
                    :disabled="tickets.current_page === tickets.last_page"
                    @click="goToPage(tickets.current_page + 1)"
                >
                    Next
                </Button>
            </div>
        </div>
    </div>
</template>