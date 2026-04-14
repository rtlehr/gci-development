<script setup>
import { computed, reactive } from 'vue'
import { Link, router } from '@inertiajs/vue3'
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

const props = defineProps({
    users: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({
            search: '',
        }),
    },
})

const filterForm = reactive({
    search: props.filters?.search ?? '',
})

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

function applyFilters() {
    router.get('/admin/users', {
        search: filterForm.search,
    }, {
        preserveState: true,
        replace: true,
    })
}

function resetFilters() {
    filterForm.search = ''

    router.get('/admin/users', {}, {
        preserveState: true,
        replace: true,
    })
}

function goToPage(page) {
    router.get('/admin/users', {
        page,
        search: filterForm.search,
    }, {
        preserveState: true,
        replace: true,
    })
}

function fullName(user) {
    const first = user.person?.first_name ?? ''
    const last = user.person?.last_name ?? ''
    const name = `${first} ${last}`.trim()

    return name || '—'
}
</script>

<template>
    <div class="p-6 space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold">User Permissions</h1>
                <p class="text-sm text-muted-foreground mt-1">
                    View users and manage their permission access.
                </p>
            </div>
        </div>

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
                        <TableHead>AIN Number</TableHead>
                        <TableHead>Name</TableHead>
                        <TableHead>Email</TableHead>
                        <TableHead>Permissions</TableHead>
                        <TableHead class="text-right">Actions</TableHead>
                    </TableRow>
                </TableHeader>

                <TableBody>
                    <TableRow v-if="!users?.data?.length">
                        <TableCell colspan="5" class="text-center py-8 text-muted-foreground">
                            No users found.
                        </TableCell>
                    </TableRow>

                    <TableRow
                        v-for="user in users.data"
                        :key="user.id"
                        class="hover:bg-muted/50"
                    >
                        <TableCell>
                            {{ user.person?.person_code || '—' }}
                        </TableCell>

                        <TableCell>
                            {{ fullName(user) }}
                        </TableCell>

                        <TableCell>
                            {{ user.email || '—' }}
                        </TableCell>

                        <TableCell>
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