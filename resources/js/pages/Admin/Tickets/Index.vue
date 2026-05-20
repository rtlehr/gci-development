<script setup>
import { computed, reactive, ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import {
    ArrowDown,
    ArrowUp,
    ArrowUpDown,
} from 'lucide-vue-next'

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

// Backend-provided ticket data, filters,
// sorting state, and column configuration.
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

// Controls visibility of the column settings panel.
const showColumnSettings = ref(false)

// Local reactive filter state used by the search/filter form.
const filterForm = reactive({
    search: props.filters?.search ?? '',
    status: props.filters?.status ?? '',
    importance: props.filters?.importance ?? '',
    request_type: props.filters?.request_type ?? '',
    assigned_to_user_id: props.filters?.assigned_to_user_id ?? '',
})

// Local editable column configuration state.
const settingsForm = reactive({
    visibleColumns: [...props.visibleColumns],
    columnOrder: [...props.columnOrder],
})

// Returns the active/visible table columns
// in the correct user-defined order.
const activeColumns = computed(() => {
    return settingsForm.columnOrder
        .filter((key) => settingsForm.visibleColumns.includes(key))
        .map((key) => props.columns.find((col) => col.key === key))
        .filter(Boolean)
})

// Returns all column definitions
// in the current display order.
const orderedColumnDefinitions = computed(() => {
    return settingsForm.columnOrder
        .map((key) => props.columns.find((col) => col.key === key))
        .filter(Boolean)
})

// Generates a compact pagination range
// centered around the current page.
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

/**
 * Applies all active filters and reloads the ticket list
 * while preserving the current sorting state.
 */
function applyFilters() {
    router.get('/admin/tickets', {
        search: filterForm.search,
        status: filterForm.status,
        importance: filterForm.importance,
        request_type: filterForm.request_type,
        assigned_to_user_id: filterForm.assigned_to_user_id,
        sort: props.sort,
        direction: props.direction,
    }, {
        preserveState: true,
        replace: true,
    })
}

/**
 * Clears all active filters and reloads
 * the default ticket list.
 */
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

/**
 * Updates table sorting.
 * Clicking the same column toggles asc/desc.
 *
 * @param {string} column
 */
function sortBy(column) {
    let nextDirection = 'asc'

    if (props.sort === column && props.direction === 'asc') {
        nextDirection = 'desc'
    }

    router.get('/admin/tickets', {
        search: filterForm.search,
        status: filterForm.status,
        importance: filterForm.importance,
        request_type: filterForm.request_type,
        assigned_to_user_id: filterForm.assigned_to_user_id,
        sort: column,
        direction: nextDirection,
    }, {
        preserveState: true,
        replace: true,
    })
}

/**
 * Returns the correct sorting icon component
 * for the specified column.
 *
 * @param {string} column
 * @returns {Component}
 */
function getSortIcon(column) {
    if (props.sort !== column) return ArrowUpDown
    return props.direction === 'asc' ? ArrowUp : ArrowDown
}

/**
 * Navigates to a different pagination page
 * while preserving filters and sorting.
 *
 * @param {number} page
 */
function goToPage(page) {
    router.get('/admin/tickets', {
        page,
        search: filterForm.search,
        status: filterForm.status,
        importance: filterForm.importance,
        request_type: filterForm.request_type,
        assigned_to_user_id: filterForm.assigned_to_user_id,
        sort: props.sort,
        direction: props.direction,
    }, {
        preserveState: true,
        replace: true,
    })
}

/**
 * Returns the display label for a column key.
 *
 * @param {string} key
 * @returns {string}
 */
function getColumnLabel(key) {
    return props.columns.find((col) => col.key === key)?.label ?? key
}

/**
 * Moves a column one position left
 * in the display order.
 *
 * @param {number} index
 */
function moveColumnLeft(index) {
    if (index <= 0) return

    const temp = settingsForm.columnOrder[index - 1]
    settingsForm.columnOrder[index - 1] = settingsForm.columnOrder[index]
    settingsForm.columnOrder[index] = temp
}

/**
 * Moves a column one position right
 * in the display order.
 *
 * @param {number} index
 */
function moveColumnRight(index) {
    if (index >= settingsForm.columnOrder.length - 1) return

    const temp = settingsForm.columnOrder[index + 1]
    settingsForm.columnOrder[index + 1] = settingsForm.columnOrder[index]
    settingsForm.columnOrder[index] = temp
}

/**
 * Saves the current visible columns
 * and column ordering preferences.
 */
function saveColumnPreferences() {
    router.post('/admin/tickets/preferences', {
        visible_columns: settingsForm.visibleColumns,
        column_order: settingsForm.columnOrder,
    }, {
        preserveScroll: true,
    })
}

/**
 * Resets local unsaved column settings
 * back to the backend-provided defaults.
 */
function resetColumnSettingsLocally() {
    settingsForm.visibleColumns = [...props.visibleColumns]
    settingsForm.columnOrder = [...props.columnOrder]
}

/**
 * Removes saved user preferences
 * and restores application defaults.
 */
function resetPreferencesOnServer() {
    router.delete('/admin/tickets/preferences', {
        preserveScroll: true,
    })
}

/**
 * Builds the display name for the user
 * who submitted the ticket.
 *
 * @param {Object} ticket
 * @returns {string}
 */
function submittedByName(ticket) {
    const first = ticket.submitted_by?.person?.first_name ?? ''
    const last = ticket.submitted_by?.person?.last_name ?? ''
    const name = `${first} ${last}`.trim()

    return name || ticket.submitted_by?.name || '—'
}

/**
 * Builds the display name for the assigned user.
 * Returns "Unassigned" if no user is assigned.
 *
 * @param {Object} ticket
 * @returns {string}
 */
function assignedToName(ticket) {
    if (!ticket.assigned_to) return 'Unassigned'

    const first = ticket.assigned_to?.person?.first_name ?? ''
    const last = ticket.assigned_to?.person?.last_name ?? ''
    const name = `${first} ${last}`.trim()

    return name || ticket.assigned_to?.name || '—'
}

/**
 * Formats table cell values for display.
 * Applies special formatting logic to known ticket fields.
 *
 * @param {Object} ticket
 * @param {string} key
 * @returns {string}
 */
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

/**
 * Returns Tailwind badge classes
 * based on ticket status.
 *
 * @param {string} status
 * @returns {string}
 */
function statusBadgeClass(status) {
    if (status === 'new') return 'bg-gray-500 text-white'
    if (status === 'in_progress') return 'bg-blue-600 text-white'
    if (status === 'on_hold') return 'bg-yellow-500 text-black'
    if (status === 'complete') return 'bg-green-600 text-white'
    if (status === 'canceled') return 'bg-red-600 text-white'

    return 'bg-gray-200 text-gray-800'
}

/**
 * Returns Tailwind badge classes
 * based on ticket importance level.
 *
 * @param {string} importance
 * @returns {string}
 */
function importanceBadgeClass(importance) {
    if (importance === 'show_stopper') return 'bg-red-600 text-white'
    if (importance === 'asap') return 'bg-orange-500 text-white'
    if (importance === 'nice_to_have') return 'bg-gray-500 text-white'

    return 'bg-gray-200 text-gray-800'
}

/**
 * Converts stored status values
 * into user-friendly display text.
 *
 * @param {string} status
 * @returns {string}
 */
function formatStatus(status) {
    if (status === 'new') return 'New'
    if (status === 'in_progress') return 'In Progress'
    if (status === 'on_hold') return 'On Hold'
    if (status === 'complete') return 'Complete'
    if (status === 'canceled') return 'Canceled'

    return status || '—'
}

/**
 * Converts stored importance values
 * into user-friendly display text.
 *
 * @param {string} importance
 * @returns {string}
 */
function formatImportance(importance) {
    if (importance === 'show_stopper') return 'Show Stopper'
    if (importance === 'asap') return 'Needed ASAP'
    if (importance === 'nice_to_have') return 'Nice to Have'

    return importance || '—'
}

/**
 * Converts stored request type values
 * into user-friendly display text.
 *
 * @param {string} type
 * @returns {string}
 */
function formatRequestType(type) {
    if (type === 'bug') return 'Bug'
    if (type === 'improvement') return 'Improvement'

    return type || '—'
}

/**
 * Builds the CSV export URL using the current
 * filters and column settings, then redirects
 * the browser to the export endpoint.
 */
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
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-semibold">Tickets</h1>

            <div class="flex gap-2">
                <Button variant="outline" @click="showColumnSettings = !showColumnSettings">
                    {{ showColumnSettings ? 'Hide Column Settings' : 'Column Settings' }}
                </Button>

                <Button variant="outline" @click="exportCsv">
                    Export CSV
                </Button>
            </div>
        </div>

        <!-- Column Settings -->
        <div v-if="showColumnSettings" class="border rounded-xl p-4 bg-background space-y-4">
            <div>
                <h2 class="text-lg font-semibold">Column Settings</h2>
                <p class="text-sm text-muted-foreground">
                    Choose which columns are shown and change their order.
                </p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="space-y-3">
                    <h3 class="font-medium">Visible Columns</h3>

                    <div
                        v-for="col in orderedColumnDefinitions"
                        :key="col.key"
                        class="flex items-center justify-between rounded-lg border p-3"
                    >
                        <div class="flex items-center gap-3">
                            <input
                                :id="`visible-${col.key}`"
                                v-model="settingsForm.visibleColumns"
                                :value="col.key"
                                type="checkbox"
                                class="h-4 w-4"
                            />
                            <Label :for="`visible-${col.key}`">{{ col.label }}</Label>
                        </div>
                    </div>
                </div>

                <div class="space-y-3">
                    <h3 class="font-medium">Column Order</h3>

                    <div
                        v-for="(colKey, index) in settingsForm.columnOrder"
                        :key="colKey"
                        class="flex items-center justify-between rounded-lg border p-3"
                    >
                        <div class="font-medium">
                            {{ getColumnLabel(colKey) }}
                        </div>

                        <div class="flex gap-2">
                            <Button size="sm" variant="outline" :disabled="index === 0" @click="moveColumnLeft(index)">Left</Button>
                            <Button size="sm" variant="outline" :disabled="index === settingsForm.columnOrder.length - 1" @click="moveColumnRight(index)">Right</Button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex gap-2">
                <Button @click="saveColumnPreferences">Save Preferences</Button>
                <Button variant="outline" @click="resetColumnSettingsLocally">Reset Unsaved Changes</Button>
                <Button variant="outline" @click="resetPreferencesOnServer">Reset to Defaults</Button>
            </div>
        </div>

        <!-- Filters -->
        <div class="border rounded-xl p-4 bg-background">
            <form @submit.prevent="applyFilters" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-4">
                    <div class="space-y-2 xl:col-span-2">
                        <Label>Search</Label>
                        <Input v-model="filterForm.search" />
                    </div>

                    <div class="space-y-2">
                        <Label>Request Type</Label>
                        <select v-model="filterForm.request_type" class="h-10 border rounded-md px-3">
                            <option value="">All</option>
                            <option value="bug">Bug</option>
                            <option value="improvement">Improvement</option>
                        </select>
                    </div>

                    <div class="space-y-2">
                        <Label>Importance</Label>
                        <select v-model="filterForm.importance" class="h-10 border rounded-md px-3">   
                            <option value="">All</option>
                            <option value="show_stopper">Show Stopper</option>
                            <option value="asap">ASAP</option>
                            <option value="nice_to_have">Nice to Have</option>
                        </select>
                    </div>

                    <div class="space-y-2">
                        <Label>Status</Label>
                        <select v-model="filterForm.status" class="h-10 border rounded-md px-3">
                            <option value="">All</option>
                            <option value="new">New</option>
                            <option value="in_progress">In Progress</option>
                            <option value="on_hold">On Hold</option>
                            <option value="complete">Complete</option>
                            <option value="canceled">Canceled</option>
                        </select>
                    </div>
                </div>

                <div class="flex gap-2">
                    <Button type="submit">Apply</Button>
                    <Button type="button" variant="outline" @click="resetFilters">Reset</Button>
                </div>
            </form>
        </div>

        <!-- Table -->
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
                                <component v-if="col.sortable" :is="getSortIcon(col.key)" class="h-4 w-4" />
                            </div>
                        </TableHead>
                        <TableHead class="text-right">Actions</TableHead>
                    </TableRow>
                </TableHeader>

                <TableBody>
                    <TableRow v-if="!tickets?.data?.length">
                        <TableCell :colspan="activeColumns.length + 1" class="text-center py-8">No tickets found.</TableCell>
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

        <!-- Pagination -->
        <div class="flex justify-between">
            <div>Showing {{ tickets.from ?? 0 }} to {{ tickets.to ?? 0 }} of {{ tickets.total ?? 0 }}</div>
            <div class="flex gap-2">
                <Button size="sm" @click="goToPage(tickets.current_page - 1)">Prev</Button>
                <Button v-for="page in pagesToShow" :key="page" size="sm" @click="goToPage(page)">{{ page }}</Button>
                <Button size="sm" @click="goToPage(tickets.current_page + 1)">Next</Button>
            </div>
        </div>
    </div>
</template>