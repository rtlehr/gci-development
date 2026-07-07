<template>
    <div class="p-6 space-y-6">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-semibold">Teams</h1>

            <div class="flex gap-2">
                <Button variant="outline" @click="showColumnSettings = !showColumnSettings">
                    {{ showColumnSettings ? 'Hide Column Settings' : 'Column Settings' }}
                </Button>

                <!--
                <Button variant="outline" @click="exportCsv">
                    Export CSV
                </Button>
                -->
                
                <Link href="/admin/teams/create" v-if="can('view_admin')">
                    <Button>Add Team</Button>
                </Link>
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
                <!-- Visible Columns -->
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
                            <Label :for="`visible-${col.key}`">
                                {{ col.label }}
                            </Label>
                        </div>
                    </div>
                </div>

                <!-- Column Order -->
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
                        placeholder="Search teams..."
                    />
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

                        <TableHead class="text-right">Actions</TableHead>
                    </TableRow>
                </TableHeader>

                <TableBody>
                    <TableRow v-if="!teams?.data?.length">
                        <TableCell :colspan="activeColumns.length + 1" class="text-center py-8 text-muted-foreground">
                            No teams found.
                        </TableCell>
                    </TableRow>

                    <TableRow
                        v-for="team in teams.data"
                        :key="team.id"
                        class="hover:bg-muted/50"
                    >
                        <TableCell
                            v-for="col in activeColumns"
                            :key="col.key"
                        >
                            {{ formatCell(team, col.key) }}
                        </TableCell>

                        <TableCell class="text-right">
                            <DropdownMenu>
                                <DropdownMenuTrigger as-child>
                                    <Button variant="ghost" size="icon">
                                        <MoreHorizontal class="h-4 w-4" />
                                    </Button>
                                </DropdownMenuTrigger>

                                <DropdownMenuContent align="end">
                                    <DropdownMenuLabel>Actions</DropdownMenuLabel>
                                    <DropdownMenuSeparator />

                                    <DropdownMenuItem as-child v-if="can('view_admin')">
                                        <Link :href="`/admin/teams/${team.id}/edit`">
                                            Edit
                                        </Link>
                                    </DropdownMenuItem>

                                    <DropdownMenuSeparator v-if="can('view_admin')" />

                                    <DropdownMenuItem
                                        v-if="can('view_admin')"
                                        @click="openDeleteDialog(team.id)"
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
                Showing {{ teams.from ?? 0 }} to {{ teams.to ?? 0 }} of {{ teams.total ?? 0 }} teams
            </div>

            <div class="flex items-center gap-2 flex-wrap">
                <Button
                    size="sm"
                    variant="outline"
                    :disabled="teams.current_page === 1"
                    @click="goToPage(teams.current_page - 1)"
                >
                    Previous
                </Button>

                <Button
                    v-for="page in pagesToShow"
                    :key="page"
                    size="sm"
                    :variant="page === teams.current_page ? 'default' : 'outline'"
                    @click="goToPage(page)"
                >
                    {{ page }}
                </Button>

                <Button
                    size="sm"
                    variant="outline"
                    :disabled="teams.current_page === teams.last_page"
                    @click="goToPage(teams.current_page + 1)"
                >
                    Next
                </Button>
            </div>
        </div>

        <!-- Delete Dialog -->
        <AlertDialog :open="deleteDialogOpen" @update:open="deleteDialogOpen = $event">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Delete Team?</AlertDialogTitle>
                    <AlertDialogDescription>
                        This action cannot be undone. This will permanently delete this team.
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

const { can } = useAuth()

// Backend-provided table data, filters,
// sorting state, and column configuration.
const props = defineProps({
    teams: Object,
    columns: Array,
    visibleColumns: Array,
    columnOrder: Array,
    filters: Object,
    sort: String,
    direction: String,
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

// Delete confirmation dialog state and selected team ID.
const deleteDialogOpen = ref(false)
const teamToDelete = ref(null)

// Returns the active/visible table columns
// in the correct user-defined order.
const activeColumns = computed(() =>
    settingsForm.columnOrder
        .filter(key => settingsForm.visibleColumns.includes(key))
        .map(key => props.columns.find(col => col.key === key))
        .filter(Boolean)
)

// Returns all column definitions
// in the current display order.
const orderedColumnDefinitions = computed(() =>
    settingsForm.columnOrder
        .map(key => props.columns.find(col => col.key === key))
        .filter(Boolean)
)

// Generates a compact pagination range
// centered around the current page.
const pagesToShow = computed(() => {
    const current = props.teams.current_page ?? 1
    const last = props.teams.last_page ?? 1

    const start = Math.max(current - 2, 1)
    const end = Math.min(current + 2, last)

    return Array.from({ length: end - start + 1 }, (_, i) => start + i)
})

/**
 * Applies the current search filter
 * while preserving sorting state.
 */
function applyFilters() {
    router.get('/admin/teams', {
        search: filterForm.search,
        sort: props.sort,
        direction: props.direction,
    }, { preserveState: true, replace: true })
}

/**
 * Clears the active search filter
 * and reloads the default results.
 */
function resetFilters() {
    filterForm.search = ''

    router.get('/admin/teams', {}, { preserveState: true, replace: true })
}

/**
 * Updates table sorting.
 * Clicking the same column toggles asc/desc.
 *
 * @param {string} column
 */
function sortBy(column) {
    let next = 'asc'

    if (props.sort === column && props.direction === 'asc') {
        next = 'desc'
    }

    router.get('/admin/teams', {
        search: filterForm.search,
        sort: column,
        direction: next,
    }, { preserveState: true, replace: true })
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
    router.get('/admin/teams', {
        page,
        search: filterForm.search,
        sort: props.sort,
        direction: props.direction,
    }, { preserveState: true, replace: true })
}

/**
 * Returns the display label for a column key.
 *
 * @param {string} key
 * @returns {string}
 */
function getColumnLabel(key) {
    return props.columns.find(col => col.key === key)?.label ?? key
}

/**
 * Moves a column one position left
 * in the display order.
 *
 * @param {number} i
 */
function moveColumnLeft(i) {
    if (i === 0) return

    [settingsForm.columnOrder[i - 1], settingsForm.columnOrder[i]] =
    [settingsForm.columnOrder[i], settingsForm.columnOrder[i - 1]]
}

/**
 * Moves a column one position right
 * in the display order.
 *
 * @param {number} i
 */
function moveColumnRight(i) {
    if (i === settingsForm.columnOrder.length - 1) return

    [settingsForm.columnOrder[i + 1], settingsForm.columnOrder[i]] =
    [settingsForm.columnOrder[i], settingsForm.columnOrder[i + 1]]
}

/**
 * Saves the current visible columns
 * and column ordering preferences.
 */
function saveColumnPreferences() {
    router.post('/admin/teams/preferences', {
        visible_columns: settingsForm.visibleColumns,
        column_order: settingsForm.columnOrder,
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
    router.delete('/admin/teams/preferences')
}

/**
 * Opens the delete confirmation dialog
 * for the selected team.
 *
 * @param {number|string} id
 */
function openDeleteDialog(id) {
    teamToDelete.value = id
    deleteDialogOpen.value = true
}

/**
 * Deletes the selected team
 * and resets dialog state afterward.
 */
function confirmDelete() {
    router.delete(`/admin/teams/${teamToDelete.value}`, {
        onFinish: () => {
            deleteDialogOpen.value = false
            teamToDelete.value = null
        },
    })
}

/**
 * Formats table cell values for display.
 * Empty values are replaced with a placeholder.
 *
 * @param {Object} row
 * @param {string} key
 * @returns {string}
 */
function formatCell(row, key) {
    return row[key] ?? '—'
}

/**
 * Builds the CSV export URL using the current
 * filters and column settings, then redirects
 * the browser to the export endpoint.
 */
function exportCsv() {
    const params = new URLSearchParams()

    if (filterForm.search) {
        params.append('search', filterForm.search)
    }

    settingsForm.visibleColumns.forEach(col =>
        params.append('visible_columns[]', col)
    )

    settingsForm.columnOrder.forEach(col =>
        params.append('column_order[]', col)
    )

    window.location.href = `/admin/teams/export/csv?${params.toString()}`
}
</script>