<template>
    <PageContainer>
        <ListToolbar
            title="Candidates"
            description="Track candidates, qualifications, workflow progress, and placement activity."
            create-label="Create Candidate"
            create-href="/candidates/create"
            :can-create="can(Permissions.CANDIDATES_CREATE)"
            :can-export="true"
            :is-downloading="isDownloading"
            @open-column-settings="showColumnSettings = true"
            @export="exportCsv"
        />

        <DownloadErrorAlert :error="downloadError" />

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
            search-placeholder="Search candidates, people, positions..."
            @apply="applyFilters"
            @reset="resetFilters"
        >
            <template #filters>
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
            </template>
        </ListFilters>

        <!-- Table -->
        <ListTableShell label="Candidates results">
            <Table>
                <TableHeader>
                    <TableRow>
                        <SortableTableHead
                            v-for="col in activeColumns"
                            :key="col.key"

                            :sortable="col.sortable"
                            :direction="sort === col.key ? direction : null"

                            :aria-label="col.sortable ? `Sort by ${col.label}` : undefined"
                            @sort="sortBy(col.key)"
                        >
                            {{ col.label }}
                        </SortableTableHead>

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
                    <ListEmptyRow
                        v-if="!candidates?.data?.length"
                        :colspan="activeColumns.length + 1"
                        title="No candidates found"
                    />

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

                        <TableCell
                            class="text-right"
                            v-if="
                                can(Permissions.CANDIDATES_READ) ||
                                can(Permissions.CANDIDATES_UPDATE) ||
                                can(Permissions.CANDIDATES_DELETE)
                            "
                        >
                            <DropdownMenu>
                                <DropdownMenuTrigger as-child>
                                    <Button variant="ghost" size="icon" aria-label="Open actions menu">
                                        <MoreHorizontal class="h-4 w-4" aria-hidden="true" />
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
                                        class="text-destructive focus:text-destructive"
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
            :current-page="candidates.current_page"
            :last-page="candidates.last_page"
            :from="candidates.from"
            :to="candidates.to"
            :total="candidates.total"
            :pages="pagesToShow"
            item-label="candidates"
            @change="goToPage"
        />

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
                        class="bg-destructive text-destructive-foreground hover:bg-destructive/90"
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
import { useFileDownload } from '@/composables/useFileDownload'
import ColumnSettings from '@/components/Lists/ColumnSettings.vue'
import SortableTableHead from '@/components/Lists/SortableTableHead.vue'
import DownloadErrorAlert from '@/components/Lists/DownloadErrorAlert.vue'
import ListToolbar from '@/components/Lists/ListToolbar.vue'
import ListFilters from '@/components/Lists/ListFilters.vue'
import ListEmptyRow from '@/components/Lists/ListEmptyRow.vue'
import ListPagination from '@/components/Lists/ListPagination.vue'
import ListTableShell from '@/components/Lists/ListTableShell.vue'
import PageContainer from '@/components/layout/PageContainer.vue'

import { MoreHorizontal } from 'lucide-vue-next'

import { Button } from '@/components/ui/button'
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
    downloadError,
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

function updateColumnSettings(updatedColumns) {
    settingsForm.visibleColumns = updatedColumns
        .filter((column) => column.visible !== false)
        .map((column) => column.key)

    settingsForm.columnOrder = updatedColumns.map((column) => column.key)
}

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
 * Saves the current visible columns
 * and column ordering preferences.
 */
function saveColumnPreferences(updatedColumns = columnsForSettings.value) {
    updateColumnSettings(updatedColumns)

    router.post('/candidates/preferences', {
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
