<template>
    <div class="p-6 space-y-6">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-semibold">People</h1>

            <Link href="/people/create">
                <Button>Add Person</Button>
            </Link>
        </div>

        <div class="border rounded-xl p-4 bg-background">
            <form @submit.prevent="applyFilters" class="flex flex-col md:flex-row gap-4 md:items-end">
                <div class="flex-1 space-y-2">
                    <Label for="search">Search</Label>
                    <Input
                        id="search"
                        v-model="filterForm.search"
                        placeholder="Search by code, name, company, phone, email..."
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
                        <TableHead @click="sortBy('id')" class="cursor-pointer select-none">
                            <div class="flex items-center gap-2">
                                <span>ID</span>
                                <component
                                    :is="getSortIcon('id')"
                                    class="h-4 w-4"
                                    :class="sort === 'id' ? 'text-foreground' : 'text-muted-foreground'"
                                />
                            </div>
                        </TableHead>

                        <TableHead @click="sortBy('person_code')" class="cursor-pointer select-none">
                            <div class="flex items-center gap-2">
                                <span>Code</span>
                                <component
                                    :is="getSortIcon('person_code')"
                                    class="h-4 w-4"
                                    :class="sort === 'person_code' ? 'text-foreground' : 'text-muted-foreground'"
                                />
                            </div>
                        </TableHead>

                        <TableHead @click="sortBy('first_name')" class="cursor-pointer select-none">
                            <div class="flex items-center gap-2">
                                <span>First Name</span>
                                <component
                                    :is="getSortIcon('first_name')"
                                    class="h-4 w-4"
                                    :class="sort === 'first_name' ? 'text-foreground' : 'text-muted-foreground'"
                                />
                            </div>
                        </TableHead>

                        <TableHead @click="sortBy('last_name')" class="cursor-pointer select-none">
                            <div class="flex items-center gap-2">
                                <span>Last Name</span>
                                <component
                                    :is="getSortIcon('last_name')"
                                    class="h-4 w-4"
                                    :class="sort === 'last_name' ? 'text-foreground' : 'text-muted-foreground'"
                                />
                            </div>
                        </TableHead>

                        <TableHead @click="sortBy('company_name')" class="cursor-pointer select-none">
                            <div class="flex items-center gap-2">
                                <span>Company</span>
                                <component
                                    :is="getSortIcon('company_name')"
                                    class="h-4 w-4"
                                    :class="sort === 'company_name' ? 'text-foreground' : 'text-muted-foreground'"
                                />
                            </div>
                        </TableHead>

                        <TableHead @click="sortBy('email')" class="cursor-pointer select-none">
                            <div class="flex items-center gap-2">
                                <span>Email</span>
                                <component
                                    :is="getSortIcon('email')"
                                    class="h-4 w-4"
                                    :class="sort === 'email' ? 'text-foreground' : 'text-muted-foreground'"
                                />
                            </div>
                        </TableHead>

                        <TableHead @click="sortBy('employment_status')" class="cursor-pointer select-none">
                            <div class="flex items-center gap-2">
                                <span>Status</span>
                                <component
                                    :is="getSortIcon('employment_status')"
                                    class="h-4 w-4"
                                    :class="sort === 'employment_status' ? 'text-foreground' : 'text-muted-foreground'"
                                />
                            </div>
                        </TableHead>

                        <TableHead class="text-right">Actions</TableHead>
                    </TableRow>
                </TableHeader>

                <TableBody>
                    <TableRow v-if="!people?.data?.length">
                        <TableCell colspan="8" class="text-center py-8 text-muted-foreground">
                            No people found.
                        </TableCell>
                    </TableRow>

                    <TableRow
                        v-for="person in people.data"
                        :key="person.id"
                        class="hover:bg-muted/50"
                    >
                        <TableCell>{{ person.id }}</TableCell>
                        <TableCell>{{ person.person_code || '—' }}</TableCell>
                        <TableCell>{{ person.first_name || '—' }}</TableCell>
                        <TableCell class="font-medium">{{ person.last_name || '—' }}</TableCell>
                        <TableCell>{{ person.company_name || '—' }}</TableCell>
                        <TableCell>{{ person.email || '—' }}</TableCell>
                        <TableCell>{{ person.employment_status || '—' }}</TableCell>

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
                                        <Link :href="`/people/${person.id}`">
                                            View
                                        </Link>
                                    </DropdownMenuItem>

                                    <DropdownMenuItem as-child>
                                        <Link :href="`/people/${person.id}/edit`">
                                            Edit
                                        </Link>
                                    </DropdownMenuItem>

                                    <DropdownMenuSeparator />

                                    <DropdownMenuItem
                                        @click="openDeleteDialog(person.id)"
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
                Showing {{ people.from ?? 0 }} to {{ people.to ?? 0 }} of {{ people.total ?? 0 }} people
            </div>

            <div class="flex items-center gap-2 flex-wrap">
                <Button
                    size="sm"
                    variant="outline"
                    :disabled="people.current_page === 1"
                    @click="goToPage(people.current_page - 1)"
                >
                    Previous
                </Button>

                <Button
                    v-for="page in pagesToShow"
                    :key="page"
                    size="sm"
                    :variant="page === people.current_page ? 'default' : 'outline'"
                    @click="goToPage(page)"
                >
                    {{ page }}
                </Button>

                <Button
                    size="sm"
                    variant="outline"
                    :disabled="people.current_page === people.last_page"
                    @click="goToPage(people.current_page + 1)"
                >
                    Next
                </Button>
            </div>
        </div>

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
    people: {
        type: Object,
        required: true,
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

const filterForm = reactive({
    search: props.filters?.search ?? '',
})

const deleteDialogOpen = ref(false)
const personToDelete = ref(null)

function applyFilters() {
    router.get('/people', {
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

    router.get('/people', {
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

    router.get('/people', {
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
    router.get('/people', {
        page,
        search: filterForm.search,
        sort: props.sort,
        direction: props.direction,
    }, {
        preserveState: true,
        replace: true,
    })
}

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

function openDeleteDialog(id) {
    personToDelete.value = id
    deleteDialogOpen.value = true
}

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
</script>