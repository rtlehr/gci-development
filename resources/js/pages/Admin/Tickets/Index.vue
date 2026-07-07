<script setup>
import { computed, reactive, ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import ColumnSettings from '@/Components/Lists/ColumnSettings.vue'
import ListToolbar from '@/Components/Lists/ListToolbar.vue'
import ListFilters from '@/Components/Lists/ListFilters.vue'

import {
    ArrowDown,
    ArrowUp,
    ArrowUpDown,
} from 'lucide-vue-next'

import { Button } from '@/components/ui/button'
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
    columns: {
        type: Array,
        default: () => [
            { key: 'ticket_number', label: 'Ticket', sortable: true },
            { key: 'title', label: 'Title', sortable: true },
            { key: 'request_type', label: 'Type', sortable: true },
            { key: 'importance', label: 'Importance', sortable: true },
            { key: 'status', label: 'Status', sortable: true },
            { key: 'submitted_by_name', label: 'Submitted By', sortable: true },
            { key: 'assigned_to_name', label: 'Assigned To', sortable: true },
        ],
    },
    visibleColumns: {
        type: Array,
        default: () => [
            'ticket_number',
            'title',
            'request_type',
            'importance',
            'status',
            'submitted_by_name',
            'assigned_to_name',
        ],
    },
    columnOrder: {
        type: Array,
        default: () => [
            'ticket_number',
            'title',
            'request_type',
            'importance',
            'status',
            'submitted_by_name',
            'assigned_to_name',
        ],
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
    sort: {
        type: String,
        default: 'ticket_number',
    },
    direction: {
        type: String,
        default: 'desc',
    },
})

const showColumnSettings = ref(false)

const filterForm = reactive({
    search: props.filters?.search ?? '',
    status: props.filters?.status ?? '',
    importance: props.filters?.importance ?? '',
    request_type: props.filters?.request_type ?? '',
    assigned_to_user_id: props.filters?.assigned_to_user_id ?? '',
})

const settingsForm = reactive({
    visibleColumns: [...props.visibleColumns],
    columnOrder: [...props.columnOrder],
})

const activeColumns = computed(() => {
    return settingsForm.columnOrder
        .filter((key) => settingsForm.visibleColumns.includes(key))
        .map((key) => props.columns.find((col) => col.key === key))
        .filter(Boolean)
})

const columnsForSettings = computed(() => {
    return settingsForm.columnOrder
        .map((key) => {
            const column = props.columns.find((col) => col.key === key)

            if (!column) {
                return null
            }

            return {
                ...column,
                visible: settingsForm.visibleColumns.includes(key),
            }
        })
        .filter(Boolean)
})

const defaultColumnsForSettings = computed(() => {
    return props.columns.map((column) => ({
        ...column,
        visible: true,
    }))
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

function updateColumnSettings(updatedColumns) {
    settingsForm.visibleColumns = updatedColumns
        .filter((column) => column.visible !== false)
        .map((column) => column.key)

    settingsForm.columnOrder = updatedColumns.map((column) => column.key)
}

function getFilterPayload() {
    return {
        search: filterForm.search,
        status: filterForm.status,
        importance: filterForm.importance,
        request_type: filterForm.request_type,
        assigned_to_user_id: filterForm.assigned_to_user_id,
    }
}

function applyFilters() {
    router.get('/admin/tickets', {
        ...getFilterPayload(),
        sort: props.sort,
        direction: props.direction,
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

    router.get('/admin/tickets', {
        sort: props.sort,
        direction: props.direction,
    }, {
        preserveState: true,
        replace: true,
    })
}

function sortBy(column) {
    let nextDirection = 'asc'

    if (props.sort === column && props.direction === 'asc') {
        nextDirection = 'desc'
    }

    router.get('/admin/tickets', {
        ...getFilterPayload(),
        sort: column,
        direction: nextDirection,
    }, {
        preserveState: true,
        replace: true,
    })
}

function getSortIcon(column) {
    if (props.sort !== column) return ArrowUpDown

    return props.direction === 'asc' ? ArrowUp : ArrowDown
}

function goToPage(page) {
    router.get('/admin/tickets', {
        page,
        ...getFilterPayload(),
        sort: props.sort,
        direction: props.direction,
    }, {
        preserveState: true,
        replace: true,
    })
}

function saveColumnPreferences(updatedColumns = columnsForSettings.value) {
    updateColumnSettings(updatedColumns)

    router.post('/admin/tickets/preferences', {
        visible_columns: settingsForm.visibleColumns,
        column_order: settingsForm.columnOrder,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            showColumnSettings.value = false
        },
    })
}

function resetColumnSettingsLocally() {
    settingsForm.visibleColumns = [...props.visibleColumns]
    settingsForm.columnOrder = [...props.columnOrder]
}

function resetPreferencesOnServer() {
    router.delete('/admin/tickets/preferences', {
        preserveScroll: true,
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

function formatCell(ticket, key) {
    switch (key) {
        case 'submitted_by_name':
            return ticket.submitted_by_name || submittedByName(ticket)

        case 'assigned_to_name':
            return ticket.assigned_to_name || assignedToName(ticket)

        case 'request_type':
            return formatRequestType(ticket.request_type)

        case 'importance':
            return formatImportance(ticket.importance)

        case 'status':
            return formatStatus(ticket.status)

        default:
            return ticket[key] ?? '—'
    }
}

function statusBadgeClass(status) {
    if (status === 'new') return 'bg-gray-500 text-white'
    if (status === 'in_progress') return 'bg-blue-600 text-white'
    if (status === 'on_hold') return 'bg-yellow-500 text-black'
    if (status === 'complete') return 'bg-green-600 text-white'
    if (status === 'canceled') return 'bg-red-600 text-white'

    return 'bg-gray-200 text-gray-800'
}

function importanceBadgeClass(importance) {
    if (importance === 'show_stopper') return 'bg-red-600 text-white'
    if (importance === 'asap') return 'bg-orange-500 text-white'
    if (importance === 'nice_to_have') return 'bg-gray-500 text-white'

    return 'bg-gray-200 text-gray-800'
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

function exportCsv() {
    const params = new URLSearchParams()

    if (filterForm.search) params.append('search', filterForm.search)
    if (filterForm.status) params.append('status', filterForm.status)
    if (filterForm.importance) params.append('importance', filterForm.importance)
    if (filterForm.request_type) params.append('request_type', filterForm.request_type)
    if (filterForm.assigned_to_user_id) params.append('assigned_to_user_id', filterForm.assigned_to_user_id)

    settingsForm.visibleColumns.forEach(col => params.append('visible_columns[]', col))
    settingsForm.columnOrder.forEach(col => params.append('column_order[]', col))

    window.location.href = `/admin/tickets/export/csv?${params.toString()}`
}
</script>

<template>
    <div class="p-6 space-y-6">
        <ListToolbar
            title="Tickets"
            :can-export="false"
            :can-create="false"
            @open-column-settings="showColumnSettings = true"
            @export="exportCsv"
        />

        <ColumnSettings
            v-model:open="showColumnSettings"
            :columns="columnsForSettings"
            :default-columns="defaultColumnsForSettings"
            @update:columns="updateColumnSettings"
            @save="saveColumnPreferences"
            @reset="resetColumnSettingsLocally"
            @reset-defaults="resetPreferencesOnServer"
        />

        <ListFilters
            v-model:search="filterForm.search"
            search-placeholder="Search tickets..."
            @apply="applyFilters"
            @reset="resetFilters"
        >
            <template #filters>
                <div class="space-y-2">
                    <Label>Request Type</Label>
                    <select
                        v-model="filterForm.request_type"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    >
                        <option value="">All</option>
                        <option value="bug">Bug</option>
                        <option value="improvement">Improvement</option>
                    </select>
                </div>

                <div class="space-y-2">
                    <Label>Importance</Label>
                    <select
                        v-model="filterForm.importance"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    >
                        <option value="">All</option>
                        <option value="show_stopper">Show Stopper</option>
                        <option value="asap">ASAP</option>
                        <option value="nice_to_have">Nice to Have</option>
                    </select>
                </div>

                <div class="space-y-2">
                    <Label>Status</Label>
                    <select
                        v-model="filterForm.status"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    >
                        <option value="">All</option>
                        <option value="new">New</option>
                        <option value="in_progress">In Progress</option>
                        <option value="on_hold">On Hold</option>
                        <option value="complete">Complete</option>
                        <option value="canceled">Canceled</option>
                    </select>
                </div>
            </template>
        </ListFilters>

        <div class="border rounded-xl bg-background overflow-hidden">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead
                            v-for="col in activeColumns"
                            :key="col.key"
                            @click="col.sortable ? sortBy(col.key) : null"
                            :class="col.sortable ? 'cursor-pointer' : ''"
                        >
                            <div class="flex items-center gap-2">
                                {{ col.label }}
                                <component
                                    v-if="col.sortable"
                                    :is="getSortIcon(col.key)"
                                    class="h-4 w-4"
                                    :class="sort === col.key ? 'text-foreground' : 'text-muted-foreground'"
                                />
                            </div>
                        </TableHead>

                        <TableHead class="text-right">Actions</TableHead>
                    </TableRow>
                </TableHeader>

                <TableBody>
                    <TableRow v-if="!tickets?.data?.length">
                        <TableCell :colspan="activeColumns.length + 1" class="text-center py-8">
                            No tickets found.
                        </TableCell>
                    </TableRow>

                    <TableRow v-for="ticket in tickets.data" :key="ticket.id">
                        <TableCell v-for="col in activeColumns" :key="col.key">
                            <template v-if="col.key === 'status'">
                                <Badge :class="statusBadgeClass(ticket.status)">
                                    {{ formatStatus(ticket.status) }}
                                </Badge>
                            </template>

                            <template v-else-if="col.key === 'importance'">
                                <Badge :class="importanceBadgeClass(ticket.importance)">
                                    {{ formatImportance(ticket.importance) }}
                                </Badge>
                            </template>

                            <template v-else>
                                {{ formatCell(ticket, col.key) }}
                            </template>
                        </TableCell>

                        <TableCell class="text-right">
                            <Link :href="`/admin/tickets/${ticket.id}`">
                                <Button size="sm">Open</Button>
                            </Link>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <div class="flex justify-between">
            <div>
                Showing {{ tickets.from ?? 0 }} to {{ tickets.to ?? 0 }} of {{ tickets.total ?? 0 }}
            </div>

            <div class="flex gap-2">
                <Button
                    size="sm"
                    variant="outline"
                    :disabled="tickets.current_page === 1"
                    @click="goToPage(tickets.current_page - 1)"
                >
                    Prev
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
