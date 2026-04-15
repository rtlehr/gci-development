<script setup>
import { computed, reactive, ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { MoreHorizontal } from 'lucide-vue-next'

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
    permissions: {
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

const deleteDialogOpen = ref(false)
const permissionToDelete = ref(null)

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

function applyFilters() {
    router.get('/admin/permissions', {
        search: filterForm.search,
    }, {
        preserveState: true,
        replace: true,
    })
}

function resetFilters() {
    filterForm.search = ''

    router.get('/admin/permissions', {}, {
        preserveState: true,
        replace: true,
    })
}

function goToPage(page) {
    router.get('/admin/permissions', {
        page,
        search: filterForm.search,
    }, {
        preserveState: true,
        replace: true,
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
</script>

<template>
    <div class="p-6 space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold">Permissions</h1>
                <p class="text-sm text-muted-foreground mt-1">
                    Manage permission definitions for the application.
                </p>
            </div>

            <Link href="/admin/permissions/create">
                <Button>Create Permission</Button>
            </Link>
        </div>

        <div class="border rounded-xl p-4 bg-background">
            <form @submit.prevent="applyFilters" class="flex flex-col md:flex-row gap-4 md:items-end">
                <div class="flex-1 space-y-2">
                    <Label for="search">Search</Label>
                    <Input
                        id="search"
                        v-model="filterForm.search"
                        placeholder="Search permissions..."
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
                        <TableHead>Group</TableHead>
                        <TableHead>Label</TableHead>
                        <TableHead>Name</TableHead>
                        <TableHead>Description</TableHead>
                        <TableHead class="text-right">Actions</TableHead>
                    </TableRow>
                </TableHeader>

                <TableBody>
                    <TableRow v-if="!permissions?.data?.length">
                        <TableCell colspan="5" class="text-center py-8 text-muted-foreground">
                            No permissions found.
                        </TableCell>
                    </TableRow>

                    <TableRow
                        v-for="permission in permissions.data"
                        :key="permission.id"
                        class="hover:bg-muted/50"
                    >
                        <TableCell>{{ permission.group_name || '—' }}</TableCell>
                        <TableCell>{{ permission.label || '—' }}</TableCell>
                        <TableCell>{{ permission.name || '—' }}</TableCell>
                        <TableCell>{{ permission.description || '—' }}</TableCell>

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
                                        <Link :href="`/admin/permissions/${permission.id}/edit`">
                                            Edit
                                        </Link>
                                    </DropdownMenuItem>

                                    <DropdownMenuSeparator />

                                    <DropdownMenuItem
                                        @click="openDeleteDialog(permission.id)"
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
                        class="bg-red-600 text-white hover:bg-red-700"
                    >
                        Delete
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </div>
</template>