<template>
    <div class="p-6 space-y-6">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-semibold">Positions</h1>

            <div class="flex gap-2">
                <Button variant="outline" @click="showColumnSettings = !showColumnSettings">
                    {{ showColumnSettings ? 'Hide Column Settings' : 'Column Settings' }}
                </Button>

                <Button variant="outline" @click="exportCsv">
                    Export CSV
                </Button>

                <Link href="/positions/create" v-if="can(Permissions.POSITIONS_CREATE)">
                    <Button>Create Position</Button>
                </Link>
            </div>
        </div>

        <!-- Column Settings Panel -->
        <div v-if="showColumnSettings" class="border rounded-xl p-4 bg-background space-y-4">
            <div>
                <h2 class="text-lg font-semibold">Column Settings</h2>
                <p class="text-sm text-muted-foreground">
                    Choose which columns are shown and change their order.
                </p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Visibility -->
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

                <!-- Order -->
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
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                :disabled="index === 0"
                                @click="moveColumnLeft(index)"
                            >
                                Left
                            </Button>

                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                :disabled="index === settingsForm.columnOrder.length - 1"
                                @click="moveColumnRight(index)"
                            >
                                Right
                            </Button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex gap-2">
                <Button @click="saveColumnPreferences">
                    Save Preferences
                </Button>

                <Button variant="outline" @click="resetColumnSettingsLocally">
                    Reset Unsaved Changes
                </Button>

                <Button variant="outline" @click="resetPreferencesOnServer">
                    Reset to Defaults
                </Button>
            </div>
        </div>

        <!-- Filters -->
        <div class="border rounded-xl p-4 bg-background">
            <form @submit.prevent="applyFilters" class="flex flex-col md:flex-row gap-4 md:items-end">
                <div class="flex-1 space-y-2">
                    <Label for="search">Search</Label>
                    <Input
                        id="search"
                        v-model="filterForm.search"
                        placeholder="Search visible columns..."
                    />
                </div>

                <div class="w-full md:w-[220px] space-y-2">
                    <Label for="status-filter">Status</Label>
                    <select
                        id="status-filter"
                        v-model="filterForm.status"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    >
                        <option value="">All Statuses</option>
                        <option value="Open">Open</option>
                        <option value="In Process">In Process</option>
                        <option value="Closed">Closed</option>
                    </select>
                </div>

                <div class="flex gap-2">
                    <Button type="submit">Apply</Button>
                    <Button type="button" variant="outline" @click="resetFilters">
                        Reset
                    </Button>
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
                            :class="col.sortable ? 'cursor-pointer select-none' : ''"
                        >
                            <div class="flex items-center gap-2">
                                <span>{{ col.label }}</span>

                                <component
                                    v-if="col.sortable"
                                    :is="getSortIcon(col.key)"
                                    class="h-4 w-4"
                                    :class="sort === col.key ? 'text-foreground' : 'text-muted-foreground'"
                                />
                            </div>
                        </TableHead>

                        <TableHead
                            class="text-right"
                            v-if="
                                can(Permissions.POSITIONS_READ) ||
                                can(Permissions.POSITIONS_UPDATE) ||
                                can(Permissions.POSITIONS_DELETE)
                            "
                        >
                            Actions
                        </TableHead>
                    </TableRow>
                </TableHeader>

                <TableBody>
                    <TableRow v-if="!positions?.data?.length">
                        <TableCell :colspan="activeColumns.length + 1" class="text-center py-8 text-muted-foreground">
                            No positions found.
                        </TableCell>
                    </TableRow>

                    <TableRow
                        v-for="position in positions.data"
                        :key="position.id"
                        class="hover:bg-muted/50"
                    >
                        <TableCell
                            v-for="col in activeColumns"
                            :key="col.key"
                        >
                            <template v-if="col.key === 'status'">
                                <Badge :class="getStatusClass(position.status)">
                                    {{ position.status || 'Unknown' }}
                                </Badge>
                            </template>

                            <template v-else>
                                {{ formatCell(position, col.key) }}
                            </template>
                        </TableCell>

                        <TableCell
                            class="text-right"
                            v-if="
                                can(Permissions.POSITIONS_READ) ||
                                can(Permissions.POSITIONS_UPDATE) ||
                                can(Permissions.POSITIONS_DELETE)
                            "
                        >
                            <DropdownMenu>
                                <DropdownMenuTrigger as-child>
                                    <Button variant="ghost" size="icon">
                                        <MoreHorizontal class="h-4 w-4" />
                                    </Button>
                                </DropdownMenuTrigger>

                                <DropdownMenuContent align="end">
                                    <DropdownMenuLabel>Actions</DropdownMenuLabel>
                                    <DropdownMenuSeparator />

                                    <DropdownMenuItem as-child v-if="can(Permissions.POSITIONS_READ)">
                                        <Link :href="`/positions/${position.id}`">
                                            View
                                        </Link>
                                    </DropdownMenuItem>

                                    <DropdownMenuItem as-child v-if="can(Permissions.POSITIONS_UPDATE)">
                                        <Link :href="`/positions/${position.id}/edit`">
                                            Edit
                                        </Link>
                                    </DropdownMenuItem>

                                    <DropdownMenuSeparator />

                                    <DropdownMenuItem
                                         v-if="can(Permissions.POSITIONS_DELETE)"
                                        @click="openDeleteDialog(position.id)"
                                        class="text-red-600 focus:text-red-600"
                                    >
                                        Delete
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <!-- Pagination -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="text-sm text-muted-foreground">
                Showing {{ positions.from ?? 0 }} to {{ positions.to ?? 0 }} of {{ positions.total ?? 0 }} positions
            </div>

            <div class="flex items-center gap-2 flex-wrap">
                <Button
                    size="sm"
                    variant="outline"
                    :disabled="positions.current_page === 1"
                    @click="goToPage(positions.current_page - 1)"
                >
                    Previous
                </Button>

                <Button
                    v-for="page in pagesToShow"
                    :key="page"
                    size="sm"
                    :variant="page === positions.current_page ? 'default' : 'outline'"
                    @click="goToPage(page)"
                >
                    {{ page }}
                </Button>

                <Button
                    size="sm"
                    variant="outline"
                    :disabled="positions.current_page === positions.last_page"
                    @click="goToPage(positions.current_page + 1)"
                >
                    Next
                </Button>
            </div>
        </div>

        <!-- Delete Dialog -->
        <AlertDialog :open="deleteDialogOpen" @update:open="deleteDialogOpen = $event">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Delete Position?</AlertDialogTitle>
                    <AlertDialogDescription>
                        This action cannot be undone. This will permanently delete the position
                        if it does not have related assignments.
                    </AlertDialogDescription>
                </AlertDialogHeader>

                <AlertDialogFooter>
                    <AlertDialogCancel @click="deleteDialogOpen = false">
                        Cancel
                    </AlertDialogCancel>

                    <AlertDialogAction
                        @click="confirmDelete"
                        class="bg-red-600 text-white hover:bg-red-700"
                    >
                        Delete
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </div>
</template>

<script setup>
import { computed, reactive, ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { useAuth } from '@/composables/useAuth'

import {
    ArrowDown,
    ArrowUp,
    ArrowUpDown,
    MoreHorizontal,
} from 'lucide-vue-next'

import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Badge } from '@/components/ui/badge'

import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog'

import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'

import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table'

import { Permissions } from '@/constants/permissions'

const { can } = useAuth()

// Backend-provided position data, filters,
// sorting state, and column configuration.
const props = defineProps({
    positions: {
        type: Object,
        required: true,
    },
    columns: {
        type: Array,
        default: () => [],
    },
    visibleColumns: {
        type: Array,
        default: () => [],
    },
    columnOrder: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({
            search: '',
            status: '',
        }),
    },
    sort: {
        type: String,
        default: 'created_at',
    },
    direction: {
        type: String,
        default: 'desc',
    },
})

// Controls visibility of the column settings panel.
const showColumnSettings = ref(false)

// Local reactive filter state used by the search form.
const filterForm = reactive({
    search: props.filters?.search ?? '',
    status: props.filters?.status ?? '',
})

// Local editable column configuration state.
const settingsForm = reactive({
    visibleColumns: [...(props.visibleColumns ?? [])],
    columnOrder: [...(props.columnOrder ?? [])],
})

// Delete confirmation dialog state and selected position ID.
const deleteDialogOpen = ref(false)
const positionToDelete = ref(null)

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
    const current = props.positions.current_page ?? 1
    const last = props.positions.last_page ?? 1

    const start = Math.max(current - 2, 1)
    const end = Math.min(current + 2, last)

    const pages = []

    for (let i = start; i <= end; i++) {
        pages.push(i)
    }

    return pages
})

/**
 * Applies the current search and status filters
 * while preserving sorting state.
 */
function applyFilters() {
    router.get('/positions', {
        search: filterForm.search,
        status: filterForm.status,
        sort: props.sort,
        direction: props.direction,
    }, {
        preserveState: true,
        replace: true,
    })
}

/**
 * Clears all active filters
 * and reloads the default position list.
 */
function resetFilters() {
    filterForm.search = ''
    filterForm.status = ''

    router.get('/positions', {
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

    router.get('/positions', {
        search: filterForm.search,
        status: filterForm.status,
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

    return props.direction === 'asc'
        ? ArrowUp
        : ArrowDown
}

/**
 * Navigates to a different pagination page
 * while preserving filters and sorting.
 *
 * @param {number} page
 */
function goToPage(page) {
    router.get('/positions', {
        page,
        search: filterForm.search,
        status: filterForm.status,
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
    router.post('/positions/preferences', {
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
    settingsForm.visibleColumns = [...(props.visibleColumns ?? [])]
    settingsForm.columnOrder = [...(props.columnOrder ?? [])]
}

/**
 * Removes saved user preferences
 * and restores application defaults.
 */
function resetPreferencesOnServer() {
    router.delete('/positions/preferences', {
        preserveScroll: true,
    })
}

/**
 * Opens the delete confirmation dialog
 * for the selected position.
 *
 * @param {number|string} id
 */
function openDeleteDialog(id) {
    positionToDelete.value = id
    deleteDialogOpen.value = true
}

/**
 * Deletes the selected position
 * and resets dialog state afterward.
 */
function confirmDelete() {
    if (!positionToDelete.value) return

    router.delete(`/positions/${positionToDelete.value}`, {
        preserveScroll: true,

        onFinish: () => {
            deleteDialogOpen.value = false
            positionToDelete.value = null
        },
    })
}

/**
 * Returns Tailwind badge classes
 * based on position status.
 *
 * @param {string} status
 * @returns {string}
 */
function getStatusClass(status) {
    if (!status) {
        return 'bg-gray-200 text-gray-800 hover:bg-gray-200'
    }

    const value = String(status).toLowerCase()

    if (value === 'open') {
        return 'bg-blue-500 text-white hover:bg-blue-500'
    }

    if (value === 'in process') {
        return 'bg-yellow-500 text-black hover:bg-yellow-500'
    }

    if (value === 'closed') {
        return 'bg-green-600 text-white hover:bg-green-600'
    }

    return 'bg-gray-200 text-gray-800 hover:bg-gray-200'
}

/**
 * Formats table cell values for display.
 *
 * Handles:
 * - Empty values
 * - Boolean Yes/No fields
 * - Date fields
 *
 * @param {Object} row
 * @param {string} key
 * @returns {string}
 */
function formatCell(row, key) {

    const value = row[key]

    // Show a dash for empty values.
    if (
        value === null ||
        value === undefined ||
        value === ''
    ) {
        return '—'
    }

    // Convert boolean-style fields to Yes/No.
    const booleanFields = [
        'is_essential',
        'travel_required',
        'high_risk_role',
        'request_to_close',
    ]

    if (booleanFields.includes(key)) {
        return value ? 'Yes' : 'No'
    }

    // Format date-style fields for display.
    const dateFields = [
        'scheduled_to_close',
        'close_date',
        'created_at',
        'updated_at',
    ]

    if (dateFields.includes(key)) {
        return formatDate(value)
    }

    return value
}

/**
 * Formats a date value into a readable local date.
 *
 * @param {string} value
 * @returns {string}
 */
function formatDate(value) {

    if (!value) {
        return '—'
    }

    const date = new Date(value)

    if (Number.isNaN(date.getTime())) {
        return value
    }

    return date.toLocaleDateString()
}

/**
 * Builds the CSV export URL using the current
 * filters and column settings, then redirects
 * the browser to the export endpoint.
 */
function exportCsv() {
    const params = new URLSearchParams()

    // Current filters.
    if (filterForm.search) {
        params.append('search', filterForm.search)
    }

    if (filterForm.status) {
        params.append('status', filterForm.status)
    }

    // Current visible columns.
    settingsForm.visibleColumns.forEach((col) => {
        params.append('visible_columns[]', col)
    })

    // Current column order.
    settingsForm.columnOrder.forEach((col) => {
        params.append('column_order[]', col)
    })

    window.location.href = `/positions/export/csv?${params.toString()}`
}
</script>