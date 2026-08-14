<script setup>
import { computed, reactive, ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import ColumnSettings from '@/components/Lists/ColumnSettings.vue'
import SortableTableHead from '@/components/Lists/SortableTableHead.vue'
import ListRowActions from '@/components/Lists/ListRowActions.vue'
import ListTableShell from '@/components/Lists/ListTableShell.vue'
import PageContainer from '@/components/layout/PageContainer.vue'
import ListToolbar from '@/components/Lists/ListToolbar.vue'
import ListFilters from '@/components/Lists/ListFilters.vue'



import { Button } from '@/components/ui/button'
import { DropdownMenuItem } from '@/components/ui/dropdown-menu'
import { Badge } from '@/components/ui/badge'

import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table'

// Backend-provided user data, filters,
// sorting state, and column configuration.
const props = defineProps({
    users: {
        type: Object,
        required: true,
    },
    columns: {
        type: Array,
        default: () => [
            { key: 'person_code', label: 'AIN Number', sortable: true },
            { key: 'full_name', label: 'Name', sortable: true },
            { key: 'roles', label: 'Role', sortable: false },
            { key: 'permissions', label: 'Permissions', sortable: false },
        ],
    },
    visibleColumns: {
        type: Array,
        default: () => ['person_code', 'full_name', 'roles', 'permissions'],
    },
    columnOrder: {
        type: Array,
        default: () => ['person_code', 'full_name', 'roles', 'permissions'],
    },
    filters: {
        type: Object,
        default: () => ({
            search: '',
        }),
    },
    sort: {
        type: String,
        default: 'person_code',
    },
    direction: {
        type: String,
        default: 'asc',
    },
})

// Controls visibility of the column settings dialog.
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

// Returns the active/visible table columns
// in the correct user-defined order.
const activeColumns = computed(() => {
    return settingsForm.columnOrder
        .filter((key) => settingsForm.visibleColumns.includes(key))
        .map((key) => props.columns.find((col) => col.key === key))
        .filter(Boolean)
})

// Returns the ordered columns with their current visibility.
// Used by the reusable ColumnSettings component.
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

// Returns the default column configuration.
// Used by the reusable ColumnSettings component.
const defaultColumnsForSettings = computed(() => {
    return props.columns.map((column) => ({
        ...column,
        visible: true,
    }))
})

// Generates a compact pagination range
// centered around the current page.
const pagesToShow = computed(() => {
    const current = props.users.current_page ?? 1
    const last = props.users.last_page ?? 1

    const start = Math.max(current - 2, 1)
    const end = Math.min(current + 2, last)

    const pages = []

    for (let i = start; i <= end; i++) {
        pages.push(i)
    }

    return pages
})

/**
 * Applies updated column settings emitted from ColumnSettings.
 *
 * @param {Array} updatedColumns
 */
function updateColumnSettings(updatedColumns) {
    settingsForm.visibleColumns = updatedColumns
        .filter((column) => column.visible !== false)
        .map((column) => column.key)

    settingsForm.columnOrder = updatedColumns.map((column) => column.key)
}

/**
 * Applies the current search filter
 * while preserving sorting state.
 */
function applyFilters() {
    router.get('/admin/users', {
        search: filterForm.search,
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

    router.get('/admin/users', {
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

    router.get('/admin/users', {
        search: filterForm.search,
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
    router.get('/admin/users', {
        page,
        search: filterForm.search,
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
 *
 * @param {Array} updatedColumns
 */
function saveColumnPreferences(updatedColumns = columnsForSettings.value) {
    updateColumnSettings(updatedColumns)

    router.post('/admin/users/preferences', {
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
    router.delete('/admin/users/preferences', {
        preserveScroll: true,
    })
}

/**
 * Builds the user's full display name
 * from the related person record.
 *
 * @param {Object} user
 * @returns {string}
 */
function fullName(user) {
    const first = user.person?.first_name ?? ''
    const last = user.person?.last_name ?? ''
    const name = `${first} ${last}`.trim()

    return name || '—'
}

/**
 * Formats table cell values for display.
 * Applies custom formatting logic to known fields.
 *
 * @param {Object} user
 * @param {string} key
 * @returns {string}
 */
function formatCell(user, key) {
    if (key === 'person_code') {
        return user.person_code || user.person?.person_code || '—'
    }

    if (key === 'full_name') {
        return user.full_name || fullName(user)
    }

    const value = user[key]

    if (value === null || value === undefined || value === '') {
        return '—'
    }

    return value
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

    settingsForm.visibleColumns.forEach((col) => {
        params.append('visible_columns[]', col)
    })

    settingsForm.columnOrder.forEach((col) => {
        params.append('column_order[]', col)
    })

    window.location.href = `/admin/users/export/csv?${params.toString()}`
}
</script>

<template>
    <PageContainer>
        <ListToolbar
            title="User Permissions"
            @open-column-settings="showColumnSettings = true"
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
            search-placeholder="Search by AIN number, first name, last name, or email..."
            @apply="applyFilters"
            @reset="resetFilters"
        />

        <ListTableShell label="User permission results">
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

                        <TableHead class="text-right">Actions</TableHead>
                    </TableRow>
                </TableHeader>

                <TableBody>
                    <TableRow v-if="!users?.data?.length">
                        <TableCell :colspan="activeColumns.length + 1" class="text-center py-8 text-muted-foreground">
                            No users found.
                        </TableCell>
                    </TableRow>

                    <TableRow
                        v-for="user in users.data"
                        :key="user.id"
                        class="hover:bg-muted/50"
                    >
                        <TableCell
                            v-for="col in activeColumns"
                            :key="col.key"
                        >
                            <template v-if="col.key === 'roles'">
                                <div v-if="user.roles?.length" class="flex flex-wrap gap-1">
                                    <Badge
                                        v-for="role in user.roles"
                                        :key="role.id"
                                        variant="default"
                                    >
                                        {{ role.label || role.name }}
                                    </Badge>
                                </div>

                                <span v-else class="text-muted-foreground">—</span>
                            </template>

                            <template v-else-if="col.key === 'permissions'">
                                <div v-if="user.permissions?.length" class="flex flex-wrap gap-1">
                                    <Badge
                                        v-for="permission in user.permissions"
                                        :key="permission.id"
                                        variant="outline"
                                    >
                                        {{ permission.label || permission.name }}
                                    </Badge>
                                </div>

                                <span v-else class="text-muted-foreground">—</span>
                            </template>

                            <template v-else>
                                {{ formatCell(user, col.key) }}
                            </template>
                        </TableCell>

                        <TableCell class="text-right">
                            <ListRowActions :aria-label="`Actions for ${user.full_name || user.email || 'user'}`">
                                <DropdownMenuItem as-child>
                                    <Link :href="`/admin/users/${user.id}/permissions`">Edit Permissions</Link>
                                </DropdownMenuItem>
                            </ListRowActions>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </ListTableShell>

        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="text-sm text-muted-foreground">
                Showing {{ users.from ?? 0 }} to {{ users.to ?? 0 }} of {{ users.total ?? 0 }} users
            </div>

            <div class="flex items-center gap-2 flex-wrap">
                <Button
                    size="sm"
                    variant="outline"
                    :disabled="users.current_page === 1"
                    @click="goToPage(users.current_page - 1)"
                >
                    Previous
                </Button>

                <Button
                    v-for="page in pagesToShow"
                    :key="page"
                    size="sm"
                    :variant="page === users.current_page ? 'default' : 'outline'"
                    @click="goToPage(page)"
                >
                    {{ page }}
                </Button>

                <Button
                    size="sm"
                    variant="outline"
                    :disabled="users.current_page === users.last_page"
                    @click="goToPage(users.current_page + 1)"
                >
                    Next
                </Button>
            </div>
        </div>
    </PageContainer>
</template>
