<template>
    <PageContainer size="full">
        <ListToolbar
            title="Positions"
            description="Manage staffing requirements, position details, assignments, and mission needs from the Portal."
            create-label="Create Position"
            create-href="/portal/positions/create"
            :can-create="can(Permissions.POSITIONS_CREATE)"
            :can-export="true"
            :is-downloading="isDownloading"
            @open-column-settings="showColumnSettings = true"
            @export="exportCsv"
        />

        <!-- Column Settings Panel -->
        <ColumnSettings
            v-model:open="showColumnSettings"
            :columns="columnsForSettings"
            :default-columns="defaultColumnsForSettings"
            @update:columns="updateColumnSettings"
            @save="saveColumnPreferences"
            @reset="resetColumnSettingsLocally"
            @reset-defaults="resetPreferencesOnServer"
        />

        <!-- Filters -->
        <ListFilters
            v-model:search="filterForm.search"
            search-placeholder="Search visible columns..."
            @apply="applyFilters"
            @reset="resetFilters"
        >
            <template #filters>
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
            </template>
            <template v-if="customFieldFilters.length" #advanced-filters>
                <div class="grid gap-4 border-t pt-4 md:grid-cols-2 xl:grid-cols-3">
                    <div v-for="field in customFieldFilters" :key="field.id" class="space-y-2">
                        <Label :for="`custom-filter-${field.id}`">{{ field.name }}</Label>
                        <Input
                            v-if="['text', 'textarea'].includes(field.field_type)"
                            :id="`custom-filter-${field.id}`"
                            v-model="filterForm.custom_filters[field.id]"
                            placeholder="Contains..."
                        />
                        <Input
                            v-else-if="field.field_type === 'date'"
                            :id="`custom-filter-${field.id}`"
                            v-model="filterForm.custom_filters[field.id]"
                            type="date"
                        />
                        <Select v-else v-model="filterForm.custom_filters[field.id]">
                            <SelectTrigger :id="`custom-filter-${field.id}`"><SelectValue placeholder="Any value" /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="__all__">Any value</SelectItem>
                                <SelectItem v-for="option in field.options" :key="option.value" :value="option.value">{{ option.label }}</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>
            </template>
        </ListFilters>

        <!-- Table -->
        <ListTableShell label="Positions results">
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
                    <ListEmptyRow
                        v-if="!positions?.data?.length"
                        :colspan="activeColumns.length + 1"
                        title="No positions found"
                    />

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
                                        <Link :href="`/portal/positions/${position.id}`">
                                            View
                                        </Link>
                                    </DropdownMenuItem>

                                    <DropdownMenuItem as-child v-if="can(Permissions.POSITIONS_UPDATE)">
                                        <Link :href="`/portal/positions/${position.id}/edit`">
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
        </ListTableShell>

        <ListPagination
            :current-page="positions.current_page"
            :last-page="positions.last_page"
            :from="positions.from"
            :to="positions.to"
            :total="positions.total"
            :pages="pagesToShow"
            item-label="positions"
            @change="goToPage"
        />

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
    </PageContainer>
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
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Badge } from '@/components/ui/badge'
import { useFileDownload } from '@/composables/useFileDownload'
import ColumnSettings from '@/components/Lists/ColumnSettings.vue'
import ListToolbar from '@/components/Lists/ListToolbar.vue'
import ListFilters from '@/components/Lists/ListFilters.vue'
import ListEmptyRow from '@/components/Lists/ListEmptyRow.vue'
import ListPagination from '@/components/Lists/ListPagination.vue'
import ListTableShell from '@/components/Lists/ListTableShell.vue'
import PageContainer from '@/components/layout/PageContainer.vue'

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

const {
    downloadFile,
    isDownloading,
} = useFileDownload()

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
    customFieldFilters: {
        type: Array,
        default: () => [],
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
    custom_filters: { ...(props.filters?.custom_filters ?? {}) },
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

/**
 * Applies the current search and status filters
 * while preserving sorting state.
 */
function applyFilters() {
    router.get('/portal/positions', {
        ...getFilterPayload(),
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
    filterForm.custom_filters = {}

    router.get('/portal/positions', {
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

    router.get('/portal/positions', {
        ...getFilterPayload(),
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
    router.get('/portal/positions', {
        page,
        ...getFilterPayload(),
        sort: props.sort,
        direction: props.direction,
    }, {
        preserveState: true,
        replace: true,
    })
}

/**
 * Saves the current visible columns
 * and column ordering preferences.
 */
function saveColumnPreferences(updatedColumns = columnsForSettings.value) {
    updateColumnSettings(updatedColumns)

    router.post('/portal/positions/preferences', {
        visible_columns: settingsForm.visibleColumns,
        column_order: settingsForm.columnOrder,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            showColumnSettings.value = false
        },
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
    router.delete('/portal/positions/preferences', {
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

    router.delete(`/portal/positions/${positionToDelete.value}`, {
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
 * Builds the payload used for filtering the list.
 */
function getFilterPayload() {
    return {
        search: filterForm.search,
        status: filterForm.status,
        custom_filters: Object.fromEntries(Object.entries(filterForm.custom_filters).filter(([, value]) => value !== '' && value !== '__all__')),
    }
}

/**
 * Builds the payload used for exporting.
 */
function getExportPayload() {
    return {
        ...getFilterPayload(),
        visible_columns: settingsForm.visibleColumns,
        column_order: settingsForm.columnOrder,
    }
}


/**
 * Exports the current list as CSV.
 */
function exportCsv() {
    downloadFile(
        '/portal/positions/export/csv',
        getExportPayload()
    )
}

function updateColumnSettings(updatedColumns) {
    settingsForm.visibleColumns = updatedColumns
        .filter((column) => column.visible !== false)
        .map((column) => column.key)

    settingsForm.columnOrder = updatedColumns.map((column) => column.key)
}

</script>