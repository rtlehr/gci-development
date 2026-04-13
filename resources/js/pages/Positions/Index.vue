<template>
    <div class="p-6 space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-semibold">Positions</h1>

            <Link href="/positions/create" v-if="can('view_admin')">
                <Button>Create Position</Button>
            </Link>
        </div>

        <!-- Filters -->
        <div class="border rounded-xl p-4 bg-background">
            <form @submit.prevent="applyFilters" class="flex flex-col md:flex-row gap-4 md:items-end">
                <div class="flex-1 space-y-2">
                    <Label for="search">Search</Label>
                    <Input
                        id="search"
                        v-model="filterForm.search"
                        placeholder="Search by code, title, labor category, team..."
                    />
                </div>

                <div class="w-full md:w-[220px] space-y-2">
                    <Label for="status-filter">Status</Label>
                    <select
                        id="status-filter"
                        v-model="filterForm.status"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background"
                    >
                        <option value="">All Statuses</option>
                        <option value="Open">Open</option>
                        <option value="In Process">In Process</option>
                        <option value="Closed">Closed</option>
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
                        <!--<TableHead>ID</TableHead>-->

                        <TableHead @click="sortBy('position_code')" class="cursor-pointer select-none">
                            <div class="flex items-center gap-2">
                                <span>PID</span>
                                <component
                                    :is="getSortIcon('position_code')"
                                    class="h-4 w-4"
                                    :class="sort === 'position_code' ? 'text-foreground' : 'text-muted-foreground'"
                                />
                            </div>
                        </TableHead>

                        <TableHead @click="sortBy('job_title')" class="cursor-pointer select-none">
                            <div class="flex items-center gap-2">
                                <span>Job Title</span>
                                <component
                                    :is="getSortIcon('job_title')"
                                    class="h-4 w-4"
                                    :class="sort === 'job_title' ? 'text-foreground' : 'text-muted-foreground'"
                                />
                            </div>
                        </TableHead>

                        <TableHead @click="sortBy('status')" class="cursor-pointer select-none">
                            <div class="flex items-center gap-2">
                                <span>Status</span>
                                <component
                                    :is="getSortIcon('status')"
                                    class="h-4 w-4"
                                    :class="sort === 'status' ? 'text-foreground' : 'text-muted-foreground'"
                                />
                            </div>
                        </TableHead>

                        <TableHead @click="sortBy('labor_category')" class="cursor-pointer select-none">
                            <div class="flex items-center gap-2">
                                <span>Labor Category</span>
                                <component
                                    :is="getSortIcon('labor_category')"
                                    class="h-4 w-4"
                                    :class="sort === 'labor_category' ? 'text-foreground' : 'text-muted-foreground'"
                                />
                            </div>
                        </TableHead>

                        <TableHead @click="sortBy('project_team_name')" class="cursor-pointer select-none">
                            <div class="flex items-center gap-2">
                                <span>Team</span>
                                <component
                                    :is="getSortIcon('project_team_name')"
                                    class="h-4 w-4"
                                    :class="sort === 'project_team_name' ? 'text-foreground' : 'text-muted-foreground'"
                                />
                            </div>
                        </TableHead>

                        <TableHead class="text-right">Actions</TableHead>
                    </TableRow>
                </TableHeader>

                <TableBody>
                    <TableRow v-if="!positions?.data?.length">
                        <TableCell colspan="7" class="text-center py-8 text-muted-foreground">
                            No positions found.
                        </TableCell>
                    </TableRow>

                    <TableRow
                        v-for="position in positions.data"
                        :key="position.id"
                        class="hover:bg-muted/50"
                    >
                        <!--<TableCell>{{ position.id }}</TableCell>-->

                        <TableCell>{{ position.position_code || '—' }}</TableCell>

                        <TableCell class="font-medium">
                            {{ position.job_title || 'Untitled' }}
                        </TableCell>

                        <TableCell>
                            <Badge :class="getStatusClass(position.status)">
                                {{ position.status || 'Unknown' }}
                            </Badge>
                        </TableCell>

                        <TableCell>{{ position.labor_category || '—' }}</TableCell>

                        <TableCell>{{ position.project_team_name || '—' }}</TableCell>

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
                                        <Link :href="`/positions/${position.id}`">
                                            View
                                        </Link>
                                    </DropdownMenuItem>

                                    <DropdownMenuItem v-if="can('view_admin')" as-child>
                                        <Link :href="`/positions/${position.id}/edit`">
                                            Edit
                                        </Link>
                                    </DropdownMenuItem>

                                    <DropdownMenuSeparator />

                                    <DropdownMenuItem
                                        v-if="can('view_admin')"
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
        </div>

        <!-- Pagination -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="text-sm text-muted-foreground">
                Showing {{ positions.from ?? 0 }} to {{ positions.to ?? 0 }} of {{ positions.total ?? 0 }} positions
            </div>

            <div class="flex items-center gap-2 flex-wrap">
                <Button
                    size="sm"
                    variant="outline"
                    :disabled="positions.current_page === 1"
                    @click="goToPage(positions.current_page - 1)"
                >
                    Previous
                </Button>

                <Button
                    v-for="page in pagesToShow"
                    :key="page"
                    size="sm"
                    :variant="page === positions.current_page ? 'default' : 'outline'"
                    @click="goToPage(page)"
                >
                    {{ page }}
                </Button>

                <Button
                    size="sm"
                    variant="outline"
                    :disabled="positions.current_page === positions.last_page"
                    @click="goToPage(positions.current_page + 1)"
                >
                    Next
                </Button>
            </div>
        </div>

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

const { can } = useAuth()

const props = defineProps({
    positions: {
        type: Object,
        required: true,
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
})

const filterForm = reactive({
    search: props.filters?.search ?? '',
    status: props.filters?.status ?? '',
})

const deleteDialogOpen = ref(false)
const positionToDelete = ref(null)

function applyFilters() {
    router.get('/positions', {
        search: filterForm.search,
        status: filterForm.status,
        sort: props.sort,
        direction: props.direction,
    }, {
        preserveState: true,
        replace: true,
    })
}

function resetFilters() {
    filterForm.search = ''
    filterForm.status = ''

    router.get('/positions', {
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

    router.get('/positions', {
        search: filterForm.search,
        status: filterForm.status,
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
    router.get('/positions', {
        page,
        search: filterForm.search,
        status: filterForm.status,
        sort: props.sort,
        direction: props.direction,
    }, {
        preserveState: true,
        replace: true,
    })
}

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

function openDeleteDialog(id) {
    positionToDelete.value = id
    deleteDialogOpen.value = true
}

function confirmDelete() {
    if (!positionToDelete.value) return

    router.delete(`/positions/${positionToDelete.value}`, {
        preserveScroll: true,
        onFinish: () => {
            deleteDialogOpen.value = false
            positionToDelete.value = null
        },
    })
}

function getStatusClass(status) {
    if (!status) return 'bg-gray-200 text-gray-800 hover:bg-gray-200'

    const value = String(status).toLowerCase()

    if (value === 'open') return 'bg-blue-500 text-white hover:bg-blue-500'
    if (value === 'in process') return 'bg-yellow-500 text-black hover:bg-yellow-500'
    if (value === 'closed') return 'bg-green-600 text-white hover:bg-green-600'

    return 'bg-gray-200 text-gray-800 hover:bg-gray-200'
}
</script>