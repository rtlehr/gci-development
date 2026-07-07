<script setup>
import { computed, reactive, ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import ColumnSettings from '@/Components/Lists/ColumnSettings.vue'

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
    <div class="p-6 space-y-6">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-semibold">User Permissions</h1>

            <div class="flex gap-2">
                <Button variant="outline" @click="showColumnSettings = true">
                    Column Settings
                </Button>

                <!--
                <Button variant="outline" @click="exportCsv">
                    Export CSV
                </Button>
                -->
            </div>
        </div>

        <ColumnSettings
            v-model:open="showColumnSettings"
            :columns="columnsForSettings"
            :default-columns="defaultColumnsForSettings"
            @update:columns="updateColumnSettings"
            @save="saveColumnPreferences"
            @reset="resetColumnSettingsLocally"
            @reset-defaults="resetPreferencesOnServer"
        />

        <div class="border rounded-xl p-4 bg-background">
            <form @submit.prevent="applyFilters" class="flex flex-col md:flex-row gap-4 md:items-end">
                <div class="flex-1 space-y-2">
                    <Label for="search">Search</Label>
                    <Input
                        id="search"
                        v-model="filterForm.search"
                        placeholder="Search by AIN number, first name, last name, or email..."
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
                            <Link :href="`/admin/users/${user.id}/permissions`">
                                <Button variant="outline" size="sm">
                                    Edit Permissions
                                </Button>
                            </Link>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

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
    </div>
</template>
