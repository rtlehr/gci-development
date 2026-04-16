<script setup>
import { computed, reactive, ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
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

const props = defineProps({
    roles: {
        type: Object,
        required: true,
    },
    columns: {
        type: Array,
        default: () => [
            { key: 'label', label: 'Label', sortable: true },
            { key: 'name', label: 'Name', sortable: true },
            { key: 'description', label: 'Description', sortable: false },
            { key: 'permissions_count', label: 'Permission Count', sortable: true },
        ],
    },
    visibleColumns: {
        type: Array,
        default: () => ['label', 'name', 'description', 'permissions_count'],
    },
    columnOrder: {
        type: Array,
        default: () => ['label', 'name', 'description', 'permissions_count'],
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
const roleToDelete = ref(null)

const activeColumns = computed(() => {
    return settingsForm.columnOrder
        .filter((key) => settingsForm.visibleColumns.includes(key))
        .map((key) => props.columns.find((col) => col.key === key))
        .filter(Boolean)
})

const orderedColumnDefinitions = computed(() => {
    return settingsForm.columnOrder
        .map((key) => props.columns.find((col) => col.key === key))
        .filter(Boolean)
})

const pagesToShow = computed(() => {
    const current = props.roles.current_page ?? 1
    const last = props.roles.last_page ?? 1

    const start = Math.max(current - 2, 1)
    const end = Math.min(current + 2, last)

    const pages = []
    for (let i = start; i <= end; i++) {
        pages.push(i)
    }

    return pages
})

function applyFilters() {
    router.get('/admin/roles', {
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

    router.get('/admin/roles', {
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

    router.get('/admin/roles', {
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
    router.get('/admin/roles', {
        page,
        search: filterForm.search,
        sort: props.sort,
        direction: props.direction,
    }, {
        preserveState: true,
        replace: true,
    })
}

function getColumnLabel(key) {
    return props.columns.find((col) => col.key === key)?.label ?? key
}

function moveColumnLeft(index) {
    if (index <= 0) return

    const temp = settingsForm.columnOrder[index - 1]
    settingsForm.columnOrder[index - 1] = settingsForm.columnOrder[index]
    settingsForm.columnOrder[index] = temp
}

function moveColumnRight(index) {
    if (index >= settingsForm.columnOrder.length - 1) return

    const temp = settingsForm.columnOrder[index + 1]
    settingsForm.columnOrder[index + 1] = settingsForm.columnOrder[index]
    settingsForm.columnOrder[index] = temp
}

function saveColumnPreferences() {
    router.post('/admin/roles/preferences', {
        visible_columns: settingsForm.visibleColumns,
        column_order: settingsForm.columnOrder,
    }, {
        preserveScroll: true,
    })
}

function resetColumnSettingsLocally() {
    settingsForm.visibleColumns = [...(props.visibleColumns ?? [])]
    settingsForm.columnOrder = [...(props.columnOrder ?? [])]
}

function resetPreferencesOnServer() {
    router.delete('/admin/roles/preferences', {
        preserveScroll: true,
    })
}

function openDeleteDialog(id) {
    roleToDelete.value = id
    deleteDialogOpen.value = true
}

function confirmDelete() {
    if (!roleToDelete.value) return

    router.delete(`/admin/roles/${roleToDelete.value}`, {
        preserveScroll: true,
        onFinish: () => {
            deleteDialogOpen.value = false
            roleToDelete.value = null
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

    window.location.href = `/admin/roles/export/csv?${params.toString()}`
}
</script>

<template>
    <div class="p-6 space-y-6">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-semibold">Roles</h1>

            <div class="flex gap-2">
                <Button variant="outline" @click="showColumnSettings = !showColumnSettings">
                    {{ showColumnSettings ? 'Hide Column Settings' : 'Column Settings' }}
                </Button>

                <Button variant="outline" @click="exportCsv">
                    Export CSV
                </Button>

                <Link href="/admin/roles/create">
                    <Button>Create Role</Button>
                </Link>
            </div>
        </div>

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

        <div class="border rounded-xl p-4 bg-background">
            <form @submit.prevent="applyFilters" class="flex flex-col md:flex-row gap-4 md:items-end">
                <div class="flex-1 space-y-2">
                    <Label for="search">Search</Label>
                    <Input
                        id="search"
                        v-model="filterForm.search"
                        placeholder="Search roles..."
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
                    <TableRow v-if="!roles?.data?.length">
                        <TableCell :colspan="activeColumns.length + 1" class="text-center py-8 text-muted-foreground">
                            No roles found.
                        </TableCell>
                    </TableRow>

                    <TableRow
                        v-for="role in roles.data"
                        :key="role.id"
                        class="hover:bg-muted/50"
                    >
                        <TableCell
                            v-for="col in activeColumns"
                            :key="col.key"
                        >
                            {{ formatCell(role, col.key) }}
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

                                    <DropdownMenuItem as-child>
                                        <Link :href="`/admin/roles/${role.id}/edit`">
                                            Edit
                                        </Link>
                                    </DropdownMenuItem>

                                    <DropdownMenuSeparator />

                                    <DropdownMenuItem
                                        @click="openDeleteDialog(role.id)"
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
                Showing {{ roles.from ?? 0 }} to {{ roles.to ?? 0 }} of {{ roles.total ?? 0 }} roles
            </div>

            <div class="flex items-center gap-2 flex-wrap">
                <Button
                    size="sm"
                    variant="outline"
                    :disabled="roles.current_page === 1"
                    @click="goToPage(roles.current_page - 1)"
                >
                    Previous
                </Button>

                <Button
                    v-for="page in pagesToShow"
                    :key="page"
                    size="sm"
                    :variant="page === roles.current_page ? 'default' : 'outline'"
                    @click="goToPage(page)"
                >
                    {{ page }}
                </Button>

                <Button
                    size="sm"
                    variant="outline"
                    :disabled="roles.current_page === roles.last_page"
                    @click="goToPage(roles.current_page + 1)"
                >
                    Next
                </Button>
            </div>
        </div>

        <AlertDialog :open="deleteDialogOpen" @update:open="deleteDialogOpen = $event">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Delete Role?</AlertDialogTitle>
                    <AlertDialogDescription>
                        This action cannot be undone. This will permanently delete the role.
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