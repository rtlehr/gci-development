<script setup>
import { computed, reactive, ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import ColumnSettings from '@/components/Lists/ColumnSettings.vue'
import ListToolbar from '@/components/Lists/ListToolbar.vue'
import ListFilters from '@/components/Lists/ListFilters.vue'
import ListTableShell from '@/components/Lists/ListTableShell.vue'
import PageContainer from '@/components/layout/PageContainer.vue'

import {
    ArrowDown,
    ArrowUp,
    ArrowUpDown,
    MoreHorizontal,
    Lock,
} from 'lucide-vue-next'

import { Button } from '@/components/ui/button'
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

const props = defineProps({
    permissions: {
        type: Object,
        required: true,
    },
    columns: {
        type: Array,
        default: () => [
            { key: 'group_name', label: 'Group', sortable: true },
            { key: 'label', label: 'Label', sortable: true },
            { key: 'name', label: 'Name', sortable: true },
            { key: 'description', label: 'Description', sortable: false },
        ],
    },
    visibleColumns: {
        type: Array,
        default: () => ['group_name', 'label', 'name', 'description'],
    },
    columnOrder: {
        type: Array,
        default: () => ['group_name', 'label', 'name', 'description'],
    },
    filters: {
        type: Object,
        default: () => ({
            search: '',
        }),
    },
    sort: {
        type: String,
        default: 'name',
    },
    direction: {
        type: String,
        default: 'asc',
    },
})

const showColumnSettings = ref(false)

const filterForm = reactive({
    search: props.filters?.search ?? '',
})

const settingsForm = reactive({
    visibleColumns: [...props.visibleColumns],
    columnOrder: [...props.columnOrder],
})

const deleteDialogOpen = ref(false)
const permissionToDelete = ref(null)

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
    const current = props.permissions.current_page ?? 1
    const last = props.permissions.last_page ?? 1

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

function applyFilters() {
    router.get('/admin/permissions', {
        search: filterForm.search,
        sort: props.sort,
        direction: props.direction,
    }, {
        preserveState: true,
        replace: true,
    })
}

function resetFilters() {
    filterForm.search = ''

    router.get('/admin/permissions', {
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

    router.get('/admin/permissions', {
        search: filterForm.search,
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
    router.get('/admin/permissions', {
        page,
        search: filterForm.search,
        sort: props.sort,
        direction: props.direction,
    }, {
        preserveState: true,
        replace: true,
    })
}

function saveColumnPreferences(updatedColumns = columnsForSettings.value) {
    updateColumnSettings(updatedColumns)

    router.post('/admin/permissions/preferences', {
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
    router.delete('/admin/permissions/preferences', {
        preserveScroll: true,
    })
}

function openDeleteDialog(id) {
    permissionToDelete.value = id
    deleteDialogOpen.value = true
}

function confirmDelete() {
    if (!permissionToDelete.value) return

    router.delete(`/admin/permissions/${permissionToDelete.value}`, {
        preserveScroll: true,
        onFinish: () => {
            deleteDialogOpen.value = false
            permissionToDelete.value = null
        },
    })
}

function formatCell(row, key) {
    const value = row[key]

    if (!value) return '—'

    return value
}

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

    window.location.href = `/admin/permissions/export/csv?${params.toString()}`
}
</script>

<template>
    <PageContainer>
        <ListToolbar
            title="Permissions"
            create-label="Create Permission"
            create-href="/admin/permissions/create"
            :can-create="true"
            :can-export="false"
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
            search-placeholder="Search permissions..."
            @apply="applyFilters"
            @reset="resetFilters"
        />

        <ListTableShell label="Permission results">
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
                    <TableRow v-if="!permissions?.data?.length">
                        <TableCell :colspan="activeColumns.length + 1" class="text-center py-8 text-muted-foreground">
                            No permissions found.
                        </TableCell>
                    </TableRow>

                    <TableRow
                        v-for="permission in permissions.data"
                        :key="permission.id"
                        class="hover:bg-muted/50"
                    >
                        <TableCell
                            v-for="col in activeColumns"
                            :key="col.key"
                        >
                            <template v-if="col.key === 'name'">
                                <div class="flex items-center gap-2">
                                    <span>{{ formatCell(permission, col.key) }}</span>
                                    <Badge v-if="permission.is_locked" variant="outline">
                                        Locked
                                    </Badge>
                                </div>
                            </template>

                            <template v-else>
                                {{ formatCell(permission, col.key) }}
                            </template>
                        </TableCell>

                        <TableCell class="text-right">
                            <div v-if="permission.is_locked" class="flex justify-end">
                                <div class="inline-flex items-center justify-center h-9 w-9 rounded-md border text-muted-foreground cursor-not-allowed">
                                    <Lock class="h-4 w-4" />
                                </div>
                            </div>

                            <DropdownMenu v-else>
                                <DropdownMenuTrigger as-child>
                                    <Button variant="ghost" size="icon">
                                        <MoreHorizontal class="h-4 w-4" />
                                    </Button>
                                </DropdownMenuTrigger>

                                <DropdownMenuContent align="end">
                                    <DropdownMenuLabel>Actions</DropdownMenuLabel>
                                    <DropdownMenuSeparator />

                                    <DropdownMenuItem as-child>
                                        <Link :href="`/admin/permissions/${permission.id}/edit`">
                                            Edit
                                        </Link>
                                    </DropdownMenuItem>

                                    <DropdownMenuSeparator />

                                    <DropdownMenuItem
                                        @click="openDeleteDialog(permission.id)"
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

        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="text-sm text-muted-foreground">
                Showing {{ permissions.from ?? 0 }} to {{ permissions.to ?? 0 }} of {{ permissions.total ?? 0 }} permissions
            </div>

            <div class="flex items-center gap-2 flex-wrap">
                <Button
                    size="sm"
                    variant="outline"
                    :disabled="permissions.current_page === 1"
                    @click="goToPage(permissions.current_page - 1)"
                >
                    Previous
                </Button>

                <Button
                    v-for="page in pagesToShow"
                    :key="page"
                    size="sm"
                    :variant="page === permissions.current_page ? 'default' : 'outline'"
                    @click="goToPage(page)"
                >
                    {{ page }}
                </Button>

                <Button
                    size="sm"
                    variant="outline"
                    :disabled="permissions.current_page === permissions.last_page"
                    @click="goToPage(permissions.current_page + 1)"
                >
                    Next
                </Button>
            </div>
        </div>

        <AlertDialog :open="deleteDialogOpen" @update:open="deleteDialogOpen = $event">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Delete Permission?</AlertDialogTitle>
                    <AlertDialogDescription>
                        This action cannot be undone. This will permanently delete the permission.
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