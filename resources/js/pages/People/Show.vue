<template>
    <div class="p-6 space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold">
                    {{ fullName(person) }}
                </h1>
                <p class="text-sm text-muted-foreground mt-1">
                    Person Code: {{ person.person_code || '—' }}
                </p>
            </div>

            <div class="flex gap-2">
                <Link href="/people">
                    <Button variant="outline">Back to List</Button>
                </Link>

                <Link :href="`/people/${person.id}/edit`" v-if="can('view_admin')">
                    <Button>Edit Person</Button>
                </Link>

                <Link :href="`/position-assignments/create?person_id=${person.id}`" v-if="can('view_admin')">
                    <Button variant="outline">Add Assignment</Button>
                </Link>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <Card>
                <CardHeader>
                    <CardTitle>Person Information</CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <DetailItem label="First Name" :value="person.first_name" />
                        <DetailItem label="Last Name" :value="person.last_name" />
                        <DetailItem label="Person Code" :value="person.person_code" />
                        <DetailItem label="Company Name" :value="person.company_name" />
                        <DetailItem label="Cell Phone" :value="person.cell_phone" />
                        <DetailItem label="Email" :value="person.email" />
                        <DetailItem label="Employment Status" :value="person.employment_status" />
                        <DetailItem label="Created" :value="formatDate(person.created_at)" />
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Current Assignments</CardTitle>
                </CardHeader>
                <CardContent>
                    <div v-if="activeAssignments.length" class="space-y-3">
                        <div
                            v-for="assignment in activeAssignments"
                            :key="assignment.id"
                            class="border rounded-lg p-4"
                        >
                            <div class="flex items-start justify-between gap-4">
                                <div class="min-w-0">
                                    <div class="font-medium">
                                        {{ assignment.position?.job_title || 'Unnamed Position' }}
                                    </div>
                                    <div class="text-sm text-muted-foreground mt-1">
                                        Code: {{ assignment.position?.position_code || '—' }}
                                    </div>
                                    <div class="text-sm text-muted-foreground">
                                        Status: {{ assignment.assignment_status || '—' }}
                                    </div>
                                    <div class="text-sm text-muted-foreground">
                                        Type: {{ assignment.assignment_type || '—' }}
                                    </div>
                                    <div class="text-sm text-muted-foreground">
                                        Start Date: {{ formatDate(assignment.start_date) }}
                                    </div>
                                </div>

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
                                            <Link :href="`/position-assignments/${assignment.id}/edit?return_to=/people/${person.id}`">
                                                Edit
                                            </Link>
                                        </DropdownMenuItem>

                                        <DropdownMenuSeparator />

                                        <DropdownMenuItem
                                            @click="openDeleteDialog(assignment.id)"
                                            class="text-red-600 focus:text-red-600"
                                        >
                                            Delete
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </div>
                        </div>
                    </div>

                    <div v-else class="text-sm text-muted-foreground">  
                        No active assignments found.
                    </div>
                </CardContent>
            </Card>
        </div>

        <Card>
            <CardHeader>
                <CardTitle>Notes</CardTitle>
            </CardHeader>
            <CardContent>
                <p class="text-sm whitespace-pre-line">
                    {{ person.notes || 'No notes available.' }}
                </p>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle>Assignment History</CardTitle>
            </CardHeader>
            <CardContent>
                <div v-if="person.assignments?.length">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Position</TableHead>
                                <TableHead>Position Code</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>Type</TableHead>
                                <TableHead>Start Date</TableHead>
                                <TableHead>End Date</TableHead>
                                <TableHead class="text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>

                        <TableBody>
                            <TableRow
                                v-for="assignment in person.assignments"
                                :key="assignment.id"
                            >
                                <TableCell>
                                    {{ assignment.position?.job_title || '—' }}
                                </TableCell>
                                <TableCell>
                                    {{ assignment.position?.position_code || '—' }}
                                </TableCell>
                                <TableCell>
                                    {{ assignment.assignment_status || '—' }}
                                </TableCell>
                                <TableCell>
                                    {{ assignment.assignment_type || '—' }}
                                </TableCell>
                                <TableCell>
                                    {{ formatDate(assignment.start_date) }}
                                </TableCell>
                                <TableCell>
                                    {{ formatDate(assignment.end_date) }}
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
                                                <Link :href="`/position-assignments/${assignment.id}/edit?return_to=/people/${person.id}`">
                                                    Edit
                                                </Link>
                                            </DropdownMenuItem>

                                            <DropdownMenuSeparator />

                                            <DropdownMenuItem
                                                @click="openDeleteDialog(assignment.id)"
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

                <div v-else class="text-sm text-muted-foreground">
                    No assignment history found.
                </div>
            </CardContent>
        </Card>

        <AlertDialog :open="deleteDialogOpen" @update:open="deleteDialogOpen = $event">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Delete Assignment?</AlertDialogTitle>
                    <AlertDialogDescription>
                        This action cannot be undone. This will permanently delete the assignment.
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
import { Link, router } from '@inertiajs/vue3'
import { MoreHorizontal } from 'lucide-vue-next'
import { computed, ref } from 'vue'
import { useAuth } from '@/composables/useAuth'
import DetailItem from '@/components/DetailItem.vue'
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
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
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
    person: {
        type: Object,
        required: true,
    },
})

const deleteDialogOpen = ref(false)
const assignmentToDelete = ref(null)

const activeAssignments = computed(() => {
    if (!props.person.assignments) return []

    return props.person.assignments.filter((assignment) => {
        const status = String(assignment.assignment_status || '').toLowerCase()
        return status === 'active' || !assignment.end_date
    })
})

function fullName(person) {
    const first = person.first_name ?? ''
    const last = person.last_name ?? ''
    return `${first} ${last}`.trim() || 'Person Details'
}

function formatDate(value) {
    if (!value) return '—'

    const date = new Date(value)

    if (Number.isNaN(date.getTime())) return value

    return date.toLocaleDateString()
}

function openDeleteDialog(id) {
    assignmentToDelete.value = id
    deleteDialogOpen.value = true
}

function confirmDelete() {
    if (!assignmentToDelete.value) return

    router.delete(`/position-assignments/${assignmentToDelete.value}`, {
        data: {
            return_to: `/people/${props.person.id}`,
        },
        preserveScroll: true,
        onFinish: () => {
            deleteDialogOpen.value = false
            assignmentToDelete.value = null
        },
    })
}
</script>