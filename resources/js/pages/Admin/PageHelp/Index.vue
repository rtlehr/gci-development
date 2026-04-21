<template>
    <div class="space-y-6 p-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold">Page Help</h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    Manage help content shown in the help panel.
                </p>
            </div>

            <Link href="/admin/page-help/create">
                <Button>Create Help Page</Button>
            </Link>
        </div>

        <div class="rounded-xl border bg-background p-4">
            <form @submit.prevent="applyFilters" class="flex gap-2">
                <Input
                    v-model="filterForm.search"
                    placeholder="Search by key or title..."
                />
                <Button type="submit">Search</Button>
                <Button type="button" variant="outline" @click="resetFilters">
                    Reset
                </Button>
            </form>
        </div>

        <div class="overflow-hidden rounded-xl border bg-background">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Help Key</TableHead>
                        <TableHead>Title</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead class="text-right">Actions</TableHead>
                    </TableRow>
                </TableHeader>

                <TableBody>
                    <TableRow v-if="!helpPages?.data?.length">
                        <TableCell colspan="4" class="py-8 text-center text-muted-foreground">
                            No help pages found.
                        </TableCell>
                    </TableRow>

                    <TableRow
                        v-for="item in helpPages.data"
                        :key="item.id"
                    >
                        <TableCell>{{ item.help_key }}</TableCell>
                        <TableCell>{{ item.title }}</TableCell>
                        <TableCell>{{ item.is_active ? 'Active' : 'Inactive' }}</TableCell>
                        <TableCell class="text-right">
                            <div class="flex justify-end gap-2">
                                <Link :href="`/admin/page-help/${item.id}/edit`">
                                    <Button variant="outline" size="sm">Edit</Button>
                                </Link>

                                <Button
                                    variant="outline"
                                    size="sm"
                                    class="text-red-600"
                                    @click="deleteItem(item.id)"
                                >
                                    Delete
                                </Button>
                            </div>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>
    </div>
</template>

<script setup>
import { reactive } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table'

const props = defineProps({
    helpPages: {
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

function applyFilters() {
    router.get('/admin/page-help', {
        search: filterForm.search,
    }, {
        preserveState: true,
        replace: true,
    })
}

function resetFilters() {
    filterForm.search = ''

    router.get('/admin/page-help', {}, {
        preserveState: true,
        replace: true,
    })
}

function deleteItem(id) {
    if (!confirm('Delete this help page?')) return

    router.delete(`/admin/page-help/${id}`, {
        preserveScroll: true,
    })
}
</script>