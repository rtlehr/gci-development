<template>
    <div class="p-6 space-y-6">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-semibold">Candidates</h1>

            <div class="flex gap-2">
                <Button variant="outline" @click="showColumnSettings = !showColumnSettings">
                    {{ showColumnSettings ? 'Hide Column Settings' : 'Column Settings' }}
                </Button>

                <Button
                    variant="outline"
                    :disabled="isDownloading"
                    @click="exportCsv"
                >
                    {{ isDownloading ? 'Exporting...' : 'Export CSV' }}
                </Button>

                <Link href="/candidates/create" v-if="can(Permissions.CANDIDATES_CREATE)">
                    <Button>Create Candidate</Button>
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
                        placeholder="Search candidates, people, positions..."
                    />
                </div>

                <div class="w-full md:w-56 space-y-2">
                    <Label for="status">Status</Label>
                    <select
                        id="status"
                        v-model="filterForm.status"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    >
                        <option value="">All Statuses</option>
                        <option
                            v-for="option in statusOptions"
                            :key="option.value"
                            :value="option.value"
                        >
                            {{ option.label }}
                        </option>
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
                                can(Permissions.CANDIDATES_READ) ||
                                can(Permissions.CANDIDATES_UPDATE) ||
                                can(Permissions.CANDIDATES_DELETE)
                            "
                        >
                            Actions
                        </TableHead>
                    </TableRow>
                </TableHeader>

                <TableBody>
                    <TableRow v-if="!candidates?.data?.length">
                        <TableCell :colspan="activeColumns.length + 1" class="text-center py-8 text-muted-foreground">
                            No candidates found.
                        </TableCell>
                    </TableRow>

                    <TableRow
                        v-for="candidate in candidates.data"
                        :key="candidate.id"
                        class="hover:bg-muted/50"
                    >
                        <TableCell
                            v-for="col in activeColumns"
                            :key="col.key"
                        >
                            {{ formatCell(candidate, col.key) }}
                        </TableCell>

                        <TableCell class="text-right" v-if="
                                can(Permissions.CANDIDATES_READ) ||
                                can(Permissions.CANDIDATES_UPDATE) ||
                                can(Permissions.CANDIDATES_DELETE)
                            ">
                            <DropdownMenu>
                                <DropdownMenuTrigger as-child>
                                    <Button variant="ghost" size="icon">
                                        <MoreHorizontal class="h-4 w-4" />
                                    </Button>
                                </DropdownMenuTrigger>

                                <DropdownMenuContent align="end">
                                    <DropdownMenuLabel>Actions</DropdownMenuLabel>
                                    <DropdownMenuSeparator />

                                    <DropdownMenuItem as-child v-if="can(Permissions.CANDIDATES_READ)">
                                        <Link :href="`/candidates/${candidate.id}`">
                                            View
                                        </Link>
                                    </DropdownMenuItem>

                                    <DropdownMenuItem
                                        as-child
                                        v-if="can(Permissions.CANDIDATES_UPDATE)"
                                    >
                                        <Link :href="`/candidates/${candidate.id}/edit`">
                                            Edit
                                        </Link>
                                    </DropdownMenuItem>

                                    <DropdownMenuSeparator
                                        v-if="can(Permissions.CANDIDATES_DELETE)"
                                    />

                                    <DropdownMenuItem
                                        v-if="can(Permissions.CANDIDATES_DELETE)"
                                        @click="openDeleteDialog(candidate.id)"
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
                Showing {{ candidates.from ?? 0 }} to {{ candidates.to ?? 0 }} of {{ candidates.total ?? 0 }} candidates
            </div>

            <div class="flex items-center gap-2 flex-wrap">
                <Button
                    size="sm"
                    variant="outline"
                    :disabled="candidates.current_page === 1"
                    @click="goToPage(candidates.current_page - 1)"
                >
                    Previous
                </Button>

                <Button
                    v-for="page in pagesToShow"
                    :key="page"
                    size="sm"
                    :variant="page === candidates.current_page ? 'default' : 'outline'"
                    @click="goToPage(page)"
                >
                    {{ page }}
                </Button>

                <Button
                    size="sm"
                    variant="outline"
                    :disabled="candidates.current_page === candidates.last_page"
                    @click="goToPage(candidates.current_page + 1)"
                >
                    Next
                </Button>
            </div>
        </div>

        <!-- Delete Dialog -->
        <AlertDialog :open="deleteDialogOpen" @update:open="deleteDialogOpen = $event">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Delete Candidate?</AlertDialogTitle>
                    <AlertDialogDescription>
                        This action cannot be undone. This will permanently delete the candidate
                        and related workflow step events.
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
import { useFileDownload } from '@/composables/useFileDownload'

import {
    ArrowDown,
    ArrowUp,
    ArrowUpDown,
    MoreHorizontal,
} from 'lucide-vue-next'

import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'

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

// Backend-provided candidate data, filters,
// sorting state, status options, and column configuration.
const props = defineProps({
    candidates: {
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
    statusOptions: {
        type: Array,
        default: () => [],
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

// Delete confirmation dialog state and selected candidate ID.
const deleteDialogOpen = ref(false)
const candidateToDelete = ref(null)

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
    const current = props.candidates.current_page ?? 1
    const last = props.candidates.last_page ?? 1

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
    router.get('/candidates', {
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
 * and reloads the default candidate list.
 */
function resetFilters() {
    filterForm.search = ''
    filterForm.status = ''

    router.get('/candidates', {
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

    router.get('/candidates', {
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
    router.get('/candidates', {
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
    router.post('/candidates/preferences', {
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
    router.delete('/candidates/preferences', {
        preserveScroll: true,
    })
}

/**
 * Opens the delete confirmation dialog
 * for the selected candidate.
 *
 * @param {number|string} id
 */
function openDeleteDialog(id) {
    candidateToDelete.value = id
    deleteDialogOpen.value = true
}

/**
 * Deletes the selected candidate
 * and resets dialog state afterward.
 */
function confirmDelete() {
    if (!candidateToDelete.value) return

    router.delete(`/candidates/${candidateToDelete.value}`, {
        preserveScroll: true,

        onFinish: () => {
            deleteDialogOpen.value = false
            candidateToDelete.value = null
        },
    })
}

/**
 * Formats table cell values for display.
 * Applies special formatting logic to known fields.
 *
 * @param {Object} row
 * @param {string} key
 * @returns {string}
 */
function formatCell(row, key) {

    if (key === 'person_name') {
        return row.person?.full_name ?? '—'
    }

    if (key === 'person_code') {
        return row.person?.person_code ?? '—'
    }

    if (key === 'position_title') {
        return row.position?.job_title ?? '—'
    }

    if (key === 'position_code') {
        return row.position?.position_code ?? '—'
    }

    if (key === 'submitted_by') {
        return row.submitted_by?.full_name ?? '—'
    }

    const value = row[key]

    if (value === null || value === undefined || value === '') {
        return '—'
    }

    return value
}

function getFilterPayload() {
    return {
        search: filterForm.search,
        status: filterForm.status,
    }
}

function getExportPayload() {
    return {
        ...getFilterPayload(),
        visible_columns: settingsForm.visibleColumns,
        column_order: settingsForm.columnOrder,
    }
}

/**
 * Exports the current candidate list as CSV.
 */
function exportCsv() {
    downloadFile(
        '/candidates/export/csv',
        getExportPayload()
    )
}

</script>