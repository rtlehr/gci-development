<template>
    <PageContainer>
        <ListToolbar
            title="People"
            description="Manage personnel records, assignments, contact information, and organizational relationships."
            create-label="Add Person"
            create-href="/people/create"
            :can-create="can(Permissions.PEOPLE_CREATE)"
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
            search-placeholder="Search visible columns..."
            @apply="applyFilters"
            @reset="resetFilters"
        />

        <!-- Table -->
        <ListTableShell label="People results">
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
                                can(Permissions.PEOPLE_READ) ||
                                can(Permissions.PEOPLE_UPDATE) ||
                                can(Permissions.PEOPLE_DELETE)
                            "
                        >
                            Actions
                        </TableHead>
                    </TableRow>
                </TableHeader>

                <TableBody>
                    <ListEmptyRow
                        v-if="!people?.data?.length"
                        :colspan="activeColumns.length + 1"
                        title="No people found"
                    />

                    <TableRow
                        v-for="person in people.data"
                        :key="person.id"
                        class="hover:bg-muted/50"
                    >
                        <TableCell
                            v-for="col in activeColumns"
                            :key="col.key"
                        >
                            {{ formatCell(person, col.key) }}
                        </TableCell>

                        <TableCell
                            class="text-right"
                            v-if="
                                can(Permissions.PEOPLE_READ) ||
                                can(Permissions.PEOPLE_UPDATE) ||
                                can(Permissions.PEOPLE_DELETE)
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

                                    <DropdownMenuItem as-child v-if="can(Permissions.PEOPLE_READ)">
                                        <Link :href="`/people/${person.id}`">
                                            View
                                        </Link>
                                    </DropdownMenuItem>

                                    <DropdownMenuItem as-child v-if="can(Permissions.PEOPLE_UPDATE)">
                                        <Link :href="`/people/${person.id}/edit`">
                                            Edit
                                        </Link>
                                    </DropdownMenuItem>

                                    <DropdownMenuSeparator v-if="can(Permissions.PEOPLE_DELETE)" />

                                    <DropdownMenuItem
                                        v-if="can(Permissions.PEOPLE_DELETE)"
                                        @click="openDeleteDialog(person.id)"
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
            :current-page="people.current_page"
            :last-page="people.last_page"
            :from="people.from"
            :to="people.to"
            :total="people.total"
            :pages="pagesToShow"
            item-label="people"
            @change="goToPage"
        />

        <!-- Delete Dialog -->
        <AlertDialog :open="deleteDialogOpen" @update:open="deleteDialogOpen = $event">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Delete Person?</AlertDialogTitle>
                    <AlertDialogDescription>
                        This action cannot be undone. This will permanently delete the person
                        if they do not have related assignments.
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

const {
    downloadFile,
    isDownloading,
    downloadError,
} = useFileDownload()

// Backend-provided people data, filters,
// sorting state, and column configuration.
const props = defineProps({
    people: {
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
        }),
    },
    sort: {
        type: String,
        default: 'last_name',
    },
    direction: {
        type: String,
        default: 'asc',
    },
})

// Controls visibility of the column settings panel.
const showColumnSettings = ref(false)

// Local reactive filter state used by the search form.
const filterForm = reactive({
    search: props.filters?.search ?? '',
})

// Local editable column configuration state.
const settingsForm = reactive({
    visibleColumns: [...(props.visibleColumns ?? [])],
    columnOrder: [...(props.columnOrder ?? [])],
})

// Delete confirmation dialog state and selected person ID.
const deleteDialogOpen = ref(false)
const personToDelete = ref(null)

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
    const current = props.people.current_page ?? 1
    const last = props.people.last_page ?? 1

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
 * Builds the payload used for filtering the list.
 */
function getFilterPayload() {
    return {
        search: filterForm.search,
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
 * Applies the current search filter
 * while preserving sorting state.
 */
function applyFilters() {
    router.get('/people', {
        ...getFilterPayload(),
        sort: props.sort,
        direction: props.direction,
    }, {
        preserveState: true,
        replace: true,
    })
}

/**
 * Clears the active search filter
 * and reloads the default results.
 */
function resetFilters() {
    filterForm.search = ''

    router.get('/people', {
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

    router.get('/people', {
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
    router.get('/people', {
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

    router.post('/people/preferences', {
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
    router.delete('/people/preferences', {
        preserveScroll: true,
    })
}

/**
 * Opens the delete confirmation dialog
 * for the selected person.
 *
 * @param {number|string} id
 */
function openDeleteDialog(id) {
    personToDelete.value = id
    deleteDialogOpen.value = true
}

/**
 * Deletes the selected person
 * and resets dialog state afterward.
 */
function confirmDelete() {
    if (!personToDelete.value) return

    router.delete(`/people/${personToDelete.value}`, {
        preserveScroll: true,

        onFinish: () => {
            deleteDialogOpen.value = false
            personToDelete.value = null
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
    // Handle primary phone number display from multiple possible relationships.
    if (key === 'primary_phone_number') {
        return row.primary_phone_number
            ?? row.primary_phone?.phone_number
            ?? row.primaryPhoneNumber?.phone_number
            ?? row.primary_phone_number_value
            ?? '—'
    }

    const value = row[key]

    if (value === null || value === undefined || value === '') {
        return '—'
    }

    return value
}

/**
 * Exports the current people list as CSV.
 */
function exportCsv() {
    downloadFile(
        '/people/export/csv',
        getExportPayload()
    )
}
</script>