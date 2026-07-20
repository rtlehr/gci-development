<template>
    <div class="p-6 space-y-6">
        <ListToolbar
            title="Organizations"
            description="Manage organization hierarchy and parent relationships."
            create-label="Add Organization"
            create-href="/admin/organizations/create"
            :can-create="can(Permissions.ADMIN)"
            :can-export="true"
            :is-downloading="isDownloading"
            @open-column-settings="showColumnSettings = true"
            @export="exportCsv"
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
            search-placeholder="Search name or full path..."
            @apply="applyFilters"
            @reset="resetFilters"
        />

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
                    <TableRow v-if="!organizations?.data?.length">
                        <TableCell :colspan="activeColumns.length + 1" class="text-center py-8 text-muted-foreground">
                            No organizations found.
                        </TableCell>
                    </TableRow>

                    <TableRow
                        v-for="organization in organizations.data"
                        :key="organization.id"
                        class="hover:bg-muted/50"
                    >
                        <TableCell
                            v-for="col in activeColumns"
                            :key="col.key"
                        >
                            {{ formatCell(organization, col.key) }}
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

                                    <DropdownMenuItem as-child v-if="can(Permissions.ADMIN)">
                                        <Link :href="`/admin/organizations/${organization.id}/edit`">
                                            Edit
                                        </Link>
                                    </DropdownMenuItem>

                                    <DropdownMenuSeparator v-if="can(Permissions.ADMIN)" />

                                    <DropdownMenuItem
                                        v-if="can(Permissions.ADMIN) && organization.id !== 1"
                                        @click="openDeleteDialog(organization.id)"
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

        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="text-sm text-muted-foreground">
                Showing {{ organizations.from ?? 0 }} to {{ organizations.to ?? 0 }} of {{ organizations.total ?? 0 }} organizations
            </div>

            <div class="flex items-center gap-2 flex-wrap">
                <Button
                    size="sm"
                    variant="outline"
                    :disabled="organizations.current_page === 1"
                    @click="goToPage(organizations.current_page - 1)"
                >
                    Previous
                </Button>

                <Button
                    v-for="page in pagesToShow"
                    :key="page"
                    size="sm"
                    :variant="page === organizations.current_page ? 'default' : 'outline'"
                    @click="goToPage(page)"
                >
                    {{ page }}
                </Button>

                <Button
                    size="sm"
                    variant="outline"
                    :disabled="organizations.current_page === organizations.last_page"
                    @click="goToPage(organizations.current_page + 1)"
                >
                    Next
                </Button>
            </div>
        </div>

        <AlertDialog :open="deleteDialogOpen" @update:open="deleteDialogOpen = $event">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Delete Organization?</AlertDialogTitle>
                    <AlertDialogDescription>
                        This action cannot be undone. The root organization cannot be deleted.
                        If this organization has child organizations, update those relationships first.
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
import ColumnSettings from '@/components/Lists/ColumnSettings.vue'
import ListToolbar from '@/components/Lists/ListToolbar.vue'
import ListFilters from '@/components/Lists/ListFilters.vue'

import {
    ArrowDown,
    ArrowUp,
    ArrowUpDown,
    MoreHorizontal,
} from 'lucide-vue-next'

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
} = useFileDownload()

const props = defineProps({
    organizations: {
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
    visibleColumns: [...(props.visibleColumns ?? [])],
    columnOrder: [...(props.columnOrder ?? [])],
})

const deleteDialogOpen = ref(false)
const organizationToDelete = ref(null)

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
    const current = props.organizations.current_page ?? 1
    const last = props.organizations.last_page ?? 1

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

function getFilterPayload() {
    return {
        search: filterForm.search,
    }
}

function getExportPayload() {
    return {
        ...getFilterPayload(),
        visible_columns: settingsForm.visibleColumns,
        column_order: settingsForm.columnOrder,
    }
}

function applyFilters() {
    router.get('/admin/organizations', {
        ...getFilterPayload(),
        sort: props.sort,
        direction: props.direction,
    }, {
        preserveState: true,
        replace: true,
    })
}

function resetFilters() {
    filterForm.search = ''

    router.get('/admin/organizations', {
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

    router.get('/admin/organizations', {
        ...getFilterPayload(),
        sort: column,
        direction: nextDirection,
    }, {
        preserveState: true,
        replace: true,
    })
}

function getSortIcon(column) {
    if (props.sort !== column) return ArrowUpDown

    return props.direction === 'asc'
        ? ArrowUp
        : ArrowDown
}

function goToPage(page) {
    router.get('/admin/organizations', {
        page,
        ...getFilterPayload(),
        sort: props.sort,
        direction: props.direction,
    }, {
        preserveState: true,
        replace: true,
    })
}

function saveColumnPreferences(updatedColumns = columnsForSettings.value) {
    updateColumnSettings(updatedColumns)

    router.post('/admin/organizations/preferences', {
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
    settingsForm.visibleColumns = [...(props.visibleColumns ?? [])]
    settingsForm.columnOrder = [...(props.columnOrder ?? [])]
}

function resetPreferencesOnServer() {
    router.delete('/admin/organizations/preferences', {
        preserveScroll: true,
    })
}

function openDeleteDialog(id) {
    organizationToDelete.value = id
    deleteDialogOpen.value = true
}

function confirmDelete() {
    if (!organizationToDelete.value) return

    router.delete(`/admin/organizations/${organizationToDelete.value}`, {
        preserveScroll: true,

        onFinish: () => {
            deleteDialogOpen.value = false
            organizationToDelete.value = null
        },
    })
}

function formatCell(row, key) {
    const value = row[key]

    if (value === null || value === undefined || value === '') {
        return '—'
    }

    return value
}

function exportCsv() {
    downloadFile(
        '/admin/organizations/export/csv',
        getExportPayload()
    )
}
</script>
