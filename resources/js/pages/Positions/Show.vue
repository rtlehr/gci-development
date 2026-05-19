<template>
    <div class="p-6 space-y-6">

        <!-- Page Header -->
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

        <!-- Position Overview -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            <!-- Core Position Information -->
            <Card>
                <CardHeader>
                    <CardTitle>Position Information</CardTitle>
                </CardHeader>

                <CardContent class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <DetailItem label="Status" :value="position.status" />
                        <DetailItem label="Job Title" :value="position.job_title" />
                        <DetailItem label="Experience Level" :value="position.experience_level" />
                        <DetailItem label="Labor Category" :value="position.labor_category" />
                        <DetailItem label="Component" :value="position.component" />
                        <DetailItem label="Location" :value="position.location" />
                        <DetailItem label="Building" :value="position.building" />
                        <DetailItem label="Project Team" :value="position.project_team_name" />
                    </div>
                </CardContent>
            </Card>

            <!-- Flags and Risk -->
            <Card>
                <CardHeader>
                    <CardTitle>Flags and Risk</CardTitle>
                </CardHeader>

                <CardContent class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <DetailItem label="Essential" :value="yesNo(position.is_essential)" />
                        <DetailItem label="Travel Required" :value="yesNo(position.travel_required)" />
                        <DetailItem label="High Risk Role" :value="yesNo(position.high_risk_role)" />
                        <DetailItem label="Request To Close" :value="yesNo(position.request_to_close)" />
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Organizations -->
        <Card>
            <CardHeader>
                <CardTitle>Organizations</CardTitle>
            </CardHeader>

            <CardContent>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <DetailItem
                        label="Position Org"
                        :value="organizationName(position.position_organization)"
                    />

                    <DetailItem
                        label="Sponsoring Org"
                        :value="organizationName(position.sponsoring_organization)"
                    />

                    <DetailItem
                        label="Funding Org"
                        :value="organizationName(position.funding_organization)"
                    />
                </div>
            </CardContent>
        </Card>

        <!-- Requirements -->
        <Card>
            <CardHeader>
                <CardTitle>Requirements and Qualifications</CardTitle>
            </CardHeader>

            <CardContent class="space-y-4">
                <LongText label="Certifications Required" :value="position.certifications_required" />
                <LongText label="Training Required" :value="position.training_required" />
                <LongText label="Experience" :value="position.experience" />
            </CardContent>
        </Card>

        <!-- Mission and Funding -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <Card>
                <CardHeader>
                    <CardTitle>Mission Description</CardTitle>
                </CardHeader>

                <CardContent>
                    <p class="text-sm whitespace-pre-line">
                        {{ position.mission_description || 'No mission description available.' }}
                    </p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Funding Information</CardTitle>
                </CardHeader>

                <CardContent>
                    <p class="text-sm whitespace-pre-line">
                        {{ position.funding_info || 'No funding information available.' }}
                    </p>
                </CardContent>
            </Card>
        </div>

        <!-- Closure Workflow -->
        <Card>
            <CardHeader>
                <CardTitle>Closure Workflow</CardTitle>
            </CardHeader>

            <CardContent>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <DetailItem label="Request To Close" :value="yesNo(position.request_to_close)" />
                    <DetailItem label="Scheduled To Close" :value="formatDate(position.scheduled_to_close)" />
                    <DetailItem label="Close Date" :value="formatDate(position.close_date)" />
                    <DetailItem label="Close Reason" :value="position.close_reason" />
                </div>
            </CardContent>
        </Card>

        <!-- Additional Information -->
        <Card>
            <CardHeader>
                <CardTitle>Additional Information</CardTitle>
            </CardHeader>

            <CardContent class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <DetailItem label="Customer Lead" :value="position.customer_lead_name" />
                    <DetailItem label="Customer Created At" :value="formatDate(position.customer_created_at)" />
                </div>

                <LongText label="Notes" :value="position.notes" fallback="No notes available." />
            </CardContent>
        </Card>

        <!-- Current Assignments -->
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

                            <AssignmentActions :assignment-id="assignment.id" />
                        </div>
                    </div>
                </div>

                <div v-else class="text-sm text-muted-foreground">
                    No active assignments found.
                </div>
            </CardContent>
        </Card>

        <!-- Assignment History -->
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
                                <TableCell>{{ fullName(assignment.person) }}</TableCell>
                                <TableCell>{{ assignment.person?.person_code || '—' }}</TableCell>
                                <TableCell>{{ assignment.assignment_status || '—' }}</TableCell>
                                <TableCell>{{ assignment.assignment_type || '—' }}</TableCell>
                                <TableCell>{{ formatDate(assignment.start_date) }}</TableCell>
                                <TableCell>{{ formatDate(assignment.end_date) }}</TableCell>

                                <TableCell class="text-right">
                                    <AssignmentActions :assignment-id="assignment.id" />
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

        <!-- Position Activity History -->
        <Card>
            <CardHeader>
                <CardTitle>Position Change History</CardTitle>
            </CardHeader>

            <CardContent>
                <div v-if="position.activities?.length">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Date</TableHead>
                                <TableHead>User</TableHead>
                                <TableHead>Action</TableHead>
                                <TableHead>Field</TableHead>
                                <TableHead>Old Value</TableHead>
                                <TableHead>New Value</TableHead>
                            </TableRow>
                        </TableHeader>

                        <TableBody>
                            <TableRow
                                v-for="activity in position.activities"
                                :key="activity.id"
                            >
                                <TableCell>{{ formatDateTime(activity.created_at) }}</TableCell>
                                <TableCell>{{ activity.user?.name || activity.user?.username || 'System' }}</TableCell>
                                <TableCell>{{ activity.action || '—' }}</TableCell>
                                <TableCell>{{ formatFieldName(activity.field_name) }}</TableCell>
                                <TableCell>{{ activity.old_value || '—' }}</TableCell>
                                <TableCell>{{ activity.new_value || '—' }}</TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>

                <div v-else class="text-sm text-muted-foreground">
                    No position changes have been recorded.
                </div>
            </CardContent>
        </Card>

        <!-- Delete Assignment Dialog -->
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

/*
|--------------------------------------------------------------------------
| Props
|--------------------------------------------------------------------------
*/

const props = defineProps({
    position: {
        type: Object,
        required: true,
    },
})

/*
|--------------------------------------------------------------------------
| Local State
|--------------------------------------------------------------------------
*/

const deleteDialogOpen = ref(false)
const assignmentToDelete = ref(null)

/*
|--------------------------------------------------------------------------
| Computed Values
|--------------------------------------------------------------------------
*/

/**
 * Creates a short alias so the template can use position directly.
 */
const position = computed(() => props.position)

/**
 * Filters assignments to show only active/current assignments.
 */
const activeAssignments = computed(() => {
    if (!props.position.assignments) {
        return []
    }

    return props.position.assignments.filter((assignment) => {
        const status = String(
            assignment.assignment_status || ''
        ).toLowerCase()

        return status === 'active' || !assignment.end_date
    })
})

/*
|--------------------------------------------------------------------------
| Formatting Helpers
|--------------------------------------------------------------------------
*/

/**
 * Converts boolean values into Yes/No display text.
 *
 * @param {boolean|number|string|null} value
 * @returns {string}
 */
function yesNo(value) {
    return value ? 'Yes' : 'No'
}

/**
 * Formats a date value into a localized readable date string.
 *
 * @param {string|null} value
 * @returns {string}
 */
function formatDate(value) {
    if (!value) {
        return '—'
    }

    const date = new Date(value)

    if (Number.isNaN(date.getTime())) {
        return value
    }

    return date.toLocaleDateString()
}

/**
 * Formats a date/time value into a localized readable date and time.
 *
 * @param {string|null} value
 * @returns {string}
 */
function formatDateTime(value) {
    if (!value) {
        return '—'
    }

    const date = new Date(value)

    if (Number.isNaN(date.getTime())) {
        return value
    }

    return date.toLocaleString()
}

/**
 * Returns a readable organization name.
 *
 * Uses full_path first because your organization dropdown is hierarchical.
 *
 * @param {Object|null} organization
 * @returns {string}
 */
function organizationName(organization) {
    if (!organization) {
        return '—'
    }

    return organization.full_path || organization.name || '—'
}

/**
 * Converts snake_case field names into readable labels.
 *
 * @param {string|null} fieldName
 * @returns {string}
 */
function formatFieldName(fieldName) {
    if (!fieldName) {
        return '—'
    }

    return fieldName
        .replaceAll('_', ' ')
        .replace(/\b\w/g, (char) => char.toUpperCase())
}

/**
 * Builds a person's full display name.
 *
 * @param {Object|null} person
 * @returns {string}
 */
function fullName(person) {
    if (!person) {
        return '—'
    }

    const first = person.first_name ?? ''
    const last = person.last_name ?? ''

    return `${first} ${last}`.trim() || '—'
}

/*
|--------------------------------------------------------------------------
| Assignment Actions
|--------------------------------------------------------------------------
*/

/**
 * Opens the delete confirmation dialog for the selected assignment.
 *
 * @param {number|string} id
 */
function openDeleteDialog(id) {
    assignmentToDelete.value = id
    deleteDialogOpen.value = true
}

/**
 * Deletes the selected assignment and returns the user to the current page.
 */
function confirmDelete() {
    if (!assignmentToDelete.value) {
        return
    }

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

/*
|--------------------------------------------------------------------------
| Local Helper Components
|--------------------------------------------------------------------------
*/

const LongText = {
    props: {
        label: {
            type: String,
            required: true,
        },
        value: {
            type: String,
            default: '',
        },
        fallback: {
            type: String,
            default: 'None provided.',
        },
    },

    template: `
        <div>
            <div class="text-sm font-medium text-muted-foreground">
                {{ label }}
            </div>

            <p class="text-sm whitespace-pre-line mt-1">
                {{ value || fallback }}
            </p>
        </div>
    `,
}

const AssignmentActions = {
    components: {
        Button,
        DropdownMenu,
        DropdownMenuContent,
        DropdownMenuItem,
        DropdownMenuLabel,
        DropdownMenuSeparator,
        DropdownMenuTrigger,
        Link,
        MoreHorizontal,
    },

    props: {
        assignmentId: {
            type: [Number, String],
            required: true,
        },
    },

    template: `
        <DropdownMenu>
            <DropdownMenuTrigger as-child>
                <Button variant="ghost" size="icon">
                    <MoreHorizontal class="h-4 w-4" />
                </Button>
            </DropdownMenuTrigger>

            <DropdownMenuContent align="end">
                <DropdownMenuLabel>
                    Actions
                </DropdownMenuLabel>

                <DropdownMenuSeparator />

                <DropdownMenuItem as-child>
                    <Link :href="\`/position-assignments/\${assignmentId}/edit?return_to=/positions/${props.position.id}\`">
                        Edit
                    </Link>
                </DropdownMenuItem>

                <DropdownMenuSeparator />

                <DropdownMenuItem
                    class="text-red-600 focus:text-red-600"
                    @click="$parent.openDeleteDialog(assignmentId)"
                >
                    Delete
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    `,
}
</script>