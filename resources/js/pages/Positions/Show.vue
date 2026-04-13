<template>
    <div class="p-6 space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold">
                    {{ position.job_title || 'Position Details' }}
                </h1>
                <p class="text-sm text-muted-foreground mt-1">
                    Position Code: {{ position.position_code || '—' }}
                </p>
            </div>

            <div class="flex gap-2">
                <Link href="/positions">
                    <Button variant="outline">Back to List</Button>
                </Link>

                <Link :href="`/positions/${position.id}/edit`" v-if="can('view_admin')">
                    <Button>Edit Position</Button>
                </Link>

                <Link :href="`/position-assignments/create?position_id=${position.id}`" v-if="can('view_admin')">
                    <Button variant="outline">Add Assignment</Button>
                </Link>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <Card>
                <CardHeader>
                    <CardTitle>Position Information</CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <DetailItem label="Status" :value="position.status" />
                        <DetailItem label="Labor Category" :value="position.labor_category" />
                        <DetailItem label="Level" :value="position.level" />
                        <DetailItem label="Project Team" :value="position.project_team_name" />
                        <DetailItem label="Organization" :value="position.organization_name" />
                        <DetailItem label="Customer Lead" :value="position.customer_lead_name" />
                        <DetailItem label="Customer Created At" :value="formatDate(position.customer_created_at)" />
                        <DetailItem label="Closed At" :value="formatDate(position.closed_at)" />
                        <DetailItem label="Closed Reason" :value="position.closed_reason" />
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
                                        {{ fullName(assignment.person) }}
                                    </div>
                                    <div class="text-sm text-muted-foreground mt-1">
                                        Person Code: {{ assignment.person?.person_code || '—' }}
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
                                            <Link :href="`/position-assignments/${assignment.id}/edit?return_to=/positions/${position.id}`">
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
                    {{ position.notes || 'No notes available.' }}
                </p>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle>Assignment History</CardTitle>
            </CardHeader>
            <CardContent>
                <div v-if="position.assignments?.length">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Person</TableHead>
                                <TableHead>Person Code</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>Type</TableHead>
                                <TableHead>Start Date</TableHead>
                                <TableHead>End Date</TableHead>
                                <TableHead class="text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>

                        <TableBody>
                            <TableRow
                                v-for="assignment in position.assignments"
                                :key="assignment.id"
                            >
                                <TableCell>
                                    {{ fullName(assignment.person) }}
                                </TableCell>
                                <TableCell>
                                    {{ assignment.person?.person_code || '—' }}
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
                                                <Link :href="`/position-assignments/${assignment.id}/edit?return_to=/positions/${position.id}`">
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
import { computed, ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { MoreHorizontal } from 'lucide-vue-next'
import { useAuth } from '@/composables/useAuth'
import DetailItem from '@/components/DetailItem.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
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
    position: {
        type: Object,
        required: true,
    },
})

const deleteDialogOpen = ref(false)
const assignmentToDelete = ref(null)

const activeAssignments = computed(() => {
    if (!props.position.assignments) return []

    return props.position.assignments.filter((assignment) => {
        const status = String(assignment.assignment_status || '').toLowerCase()
        return status === 'active' || !assignment.end_date
    })
})

function formatDate(value) {
    if (!value) return '—'

    const date = new Date(value)

    if (Number.isNaN(date.getTime())) return value

    return date.toLocaleDateString()
}

function fullName(person) {
    if (!person) return '—'

    const first = person.first_name ?? ''
    const last = person.last_name ?? ''

    return `${first} ${last}`.trim() || '—'
}

function openDeleteDialog(id) {
    assignmentToDelete.value = id
    deleteDialogOpen.value = true
}

function confirmDelete() {
    if (!assignmentToDelete.value) return

    router.delete(`/position-assignments/${assignmentToDelete.value}`, {
        data: {
            return_to: `/positions/${props.position.id}`,
        },
        preserveScroll: true,
        onFinish: () => {
            deleteDialogOpen.value = false
            assignmentToDelete.value = null
        },
    })
}
</script>