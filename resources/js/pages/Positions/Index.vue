<template>
    <div class="p-6">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-semibold">Positions</h1>
        </div>

        <!-- Filters -->
        <div class="border rounded-xl p-4 mb-6 bg-background">
            <form @submit.prevent="applyFilters" class="flex flex-col md:flex-row gap-4 md:items-end">
                <div class="flex-1 space-y-2">
                    <Label for="search">Search</Label>
                    <Input
                        id="search"
                        v-model="filterForm.search"
                        placeholder="Search positions..."
                    />
                </div>

                <div class="w-full md:w-[220px] space-y-2">
                    <Label>Status</Label>
                    <select
                        v-model="filterForm.status"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
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
        <div class="border rounded-xl bg-background">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>ID</TableHead>
                        <TableHead>Code</TableHead>
                        <TableHead>Job Title</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead>Labor Category</TableHead>
                        <TableHead>Team</TableHead>
                        <TableHead class="text-right">Actions</TableHead>
                    </TableRow>
                </TableHeader>

                <TableBody>
                    <TableRow v-if="positions.data.length === 0">
                        <TableCell colspan="7" class="text-center py-8 text-muted-foreground">
                            No positions found.
                        </TableCell>
                    </TableRow>

                    <TableRow
                        v-for="position in positions.data"
                        :key="position.id"
                        class="hover:bg-muted/50"
                    >
                        <TableCell>{{ position.id }}</TableCell>

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
                                        <Link :href="`/positions/${position.id}/edit`">
                                            Edit
                                        </Link>
                                    </DropdownMenuItem>

                                    <DropdownMenuItem disabled>
                                        View
                                    </DropdownMenuItem>

                                    <DropdownMenuSeparator />

                                    <DropdownMenuItem disabled>
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
        <div class="flex items-center justify-between mt-6">
            <div class="text-sm text-muted-foreground">
                Showing {{ positions.from ?? 0 }} to {{ positions.to ?? 0 }} of {{ positions.total }}
            </div>

            <div class="flex items-center gap-2">
                <!-- Previous -->
                <Button
                    size="sm"
                    variant="outline"
                    :disabled="positions.current_page === 1"
                    @click="goToPage(positions.current_page - 1)"
                >
                    Previous
                </Button>

                <!-- Pages -->
                <Button
                    v-for="page in pagesToShow"
                    :key="page"
                    size="sm"
                    :variant="page === positions.current_page ? 'default' : 'outline'"
                    @click="goToPage(page)"
                >
                    {{ page }}
                </Button>

                <!-- Next -->
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
    </div>
</template>

<script setup>
import { reactive, computed } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import { MoreHorizontal } from 'lucide-vue-next'

import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Badge } from '@/components/ui/badge'

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
    positions: Object,
    filters: Object,
})

const filterForm = reactive({
    search: props.filters?.search ?? '',
    status: props.filters?.status ?? '',
})

function applyFilters() {
    router.get('/positions', {
        search: filterForm.search,
        status: filterForm.status,
    }, {
        preserveState: true,
        replace: true,
    })
}

function resetFilters() {
    filterForm.search = ''
    filterForm.status = ''

    router.get('/positions', {}, {
        preserveState: true,
        replace: true,
    })
}

function goToPage(page) {
    router.get('/positions', {
        page,
        search: filterForm.search,
        status: filterForm.status,
    }, {
        preserveState: true,
        replace: true,
    })
}

/* Smart pagination (limits number of buttons) */
const pagesToShow = computed(() => {
    const current = props.positions.current_page
    const last = props.positions.last_page

    let start = Math.max(current - 2, 1)
    let end = Math.min(current + 2, last)

    const pages = []
    for (let i = start; i <= end; i++) {
        pages.push(i)
    }

    return pages
})

/* Status styling */
function getStatusClass(status) {
    if (!status) return 'bg-gray-200 text-gray-800'

    const value = status.toLowerCase()

    if (value === 'open') return 'bg-blue-500 text-white'
    if (value === 'in process') return 'bg-yellow-500 text-black'
    if (value === 'closed') return 'bg-green-600 text-white'

    return 'bg-gray-200 text-gray-800'
}
</script>