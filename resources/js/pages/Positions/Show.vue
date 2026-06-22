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
                    <Button variant="outline">
                        Back to List
                    </Button>
                </Link>

                <Link
                    :href="`/positions/${position.id}/edit`"
                    v-if="can('view_admin')"
                >
                    <Button>
                        Edit Position
                    </Button>
                </Link>

                <Link
                    :href="`/position-assignments/create?position_id=${position.id}`"
                    v-if="can('view_admin')"
                >
                    <Button variant="outline">
                        Add Assignment
                    </Button>
                </Link>
            </div>
        </div>

        <!-- Position Overview -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Core Position Information -->
            <Card>
                <CardHeader>
                    <CardTitle>
                        Position Information
                    </CardTitle>
                </CardHeader>

                <CardContent>
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
                    <CardTitle>
                        Flags and Risk
                    </CardTitle>
                </CardHeader>

                <CardContent>
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
                <CardTitle>
                    Organizations
                </CardTitle>
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

        <!-- Skills and Tasks -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Skills -->
            <Card>
                <CardHeader>
                    <CardTitle>
                        Skills
                    </CardTitle>
                </CardHeader>

                <CardContent class="space-y-6">
                    <!-- Job Title Skills -->
                    <div>
                        <h3 class="font-medium mb-3">
                            Job Title Skills
                        </h3>

                        <div
                            v-if="jobTitleSkills.length"
                            class="space-y-3"
                        >
                            <div
                                v-for="skill in jobTitleSkills"
                                :key="skill.id"
                                class="border rounded-lg p-3"
                            >
                                <div class="font-medium text-sm">
                                    {{ skill.name }}
                                </div>

                                <p class="text-sm text-muted-foreground mt-1">
                                    {{ skill.description || 'No description provided.' }}
                                </p>
                            </div>
                        </div>

                        <p
                            v-else
                            class="text-sm text-muted-foreground"
                        >
                            No default skills found for this job title.
                        </p>
                    </div>

                    <!-- Custom Position Skills -->
                    <div>
                        <h3 class="font-medium mb-3">
                            Custom Position Skills
                        </h3>

                        <div
                            v-if="customSkills.length"
                            class="space-y-3"
                        >
                            <div
                                v-for="skill in customSkills"
                                :key="skill.id"
                                class="border rounded-lg p-3"
                            >
                                <div class="font-medium text-sm">
                                    {{ skill.name }}
                                </div>

                                <p class="text-sm text-muted-foreground mt-1">
                                    {{ skill.description || 'No description provided.' }}
                                </p>
                            </div>
                        </div>

                        <p
                            v-else
                            class="text-sm text-muted-foreground"
                        >
                            No custom skills have been added to this position.
                        </p>
                    </div>
                </CardContent>
            </Card>

            <!-- Tasks -->
            <Card>
                <CardHeader>
                    <CardTitle>
                        Tasks
                    </CardTitle>
                </CardHeader>

                <CardContent class="space-y-6">
                    <!-- Job Title Tasks -->
                    <div>
                        <h3 class="font-medium mb-3">
                            Job Title Tasks
                        </h3>

                        <div
                            v-if="jobTitleTasks.length"
                            class="space-y-3"
                        >
                            <div
                                v-for="task in jobTitleTasks"
                                :key="task.id"
                                class="border rounded-lg p-3"
                            >
                                <div class="font-medium text-sm">
                                    {{ task.name }}
                                </div>

                                <p class="text-sm text-muted-foreground mt-1">
                                    {{ task.description || 'No description provided.' }}
                                </p>
                            </div>
                        </div>

                        <p
                            v-else
                            class="text-sm text-muted-foreground"
                        >
                            No default tasks found for this job title.
                        </p>
                    </div>

                    <!-- Custom Position Tasks -->
                    <div>
                        <h3 class="font-medium mb-3">
                            Custom Position Tasks
                        </h3>

                        <div
                            v-if="customTasks.length"
                            class="space-y-3"
                        >
                            <div
                                v-for="task in customTasks"
                                :key="task.id"
                                class="border rounded-lg p-3"
                            >
                                <div class="font-medium text-sm">
                                    {{ task.name }}
                                </div>

                                <p class="text-sm text-muted-foreground mt-1">
                                    {{ task.description || 'No description provided.' }}
                                </p>
                            </div>
                        </div>

                        <p
                            v-else
                            class="text-sm text-muted-foreground"
                        >
                            No custom tasks have been added to this position.
                        </p>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Requirements -->
        <Card>
            <CardHeader>
                <CardTitle>
                    Requirements and Qualifications
                </CardTitle>
            </CardHeader>

            <CardContent class="space-y-4">
                <div>
                    <div class="text-sm font-medium text-muted-foreground">
                        Certifications Required
                    </div>

                    <p class="text-sm whitespace-pre-line mt-1">
                        {{ position.certifications_required || 'None provided.' }}
                    </p>
                </div>

                <div>
                    <div class="text-sm font-medium text-muted-foreground">
                        Training Required
                    </div>

                    <p class="text-sm whitespace-pre-line mt-1">
                        {{ position.training_required || 'None provided.' }}
                    </p>
                </div>

                <div>
                    <div class="text-sm font-medium text-muted-foreground">
                        Experience
                    </div>

                    <p class="text-sm whitespace-pre-line mt-1">
                        {{ position.experience || 'None provided.' }}
                    </p>
                </div>
            </CardContent>
        </Card>

        <!-- Mission and Funding -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <Card>
                <CardHeader>
                    <CardTitle>
                        Mission Description
                    </CardTitle>
                </CardHeader>

                <CardContent>
                    <p class="text-sm whitespace-pre-line">
                        {{ position.mission_description || 'No mission description available.' }}
                    </p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>
                        Funding Information
                    </CardTitle>
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
                <CardTitle>
                    Closure Workflow
                </CardTitle>
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
                <CardTitle>
                    Additional Information
                </CardTitle>
            </CardHeader>

            <CardContent class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <DetailItem
                        label="Customer Lead"
                        :value="position.customer_lead_name"
                    />

                    <DetailItem
                        label="Customer Created At"
                        :value="formatDate(position.customer_created_at)"
                    />
                </div>

                <div>
                    <div class="text-sm font-medium text-muted-foreground">
                        Notes
                    </div>

                    <p class="text-sm whitespace-pre-line mt-1">
                        {{ position.notes || 'No notes available.' }}
                    </p>
                </div>
            </CardContent>
        </Card>

        <!-- Current Assignments -->
        <Card>
            <CardHeader>
                <CardTitle>
                    Current Assignments
                </CardTitle>
            </CardHeader>

            <CardContent>
                <div
                    v-if="activeAssignments.length"
                    class="space-y-3"
                >
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
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                    >
                                        <MoreHorizontal class="h-4 w-4" />
                                    </Button>
                                </DropdownMenuTrigger>

                                <DropdownMenuContent align="end">
                                    <DropdownMenuLabel>
                                        Actions
                                    </DropdownMenuLabel>

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

                <div
                    v-else
                    class="text-sm text-muted-foreground"
                >
                    No active assignments found.
                </div>
            </CardContent>
        </Card>

        <!-- Assignment History -->
        <Card>
            <CardHeader>
                <CardTitle>
                    Assignment History
                </CardTitle>
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
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                            >
                                                <MoreHorizontal class="h-4 w-4" />
                                            </Button>
                                        </DropdownMenuTrigger>

                                        <DropdownMenuContent align="end">
                                            <DropdownMenuLabel>
                                                Actions
                                            </DropdownMenuLabel>

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

                <div
                    v-else
                    class="text-sm text-muted-foreground"
                >
                    No assignment history found.
                </div>
            </CardContent>
        </Card>

        <!-- Position Change History -->
        <Card>
            <CardHeader>
                <CardTitle>
                    Position Change History
                </CardTitle>
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
                                <TableCell>
                                    {{ formatDateTime(activity.created_at) }}
                                </TableCell>

                                <TableCell>
                                    {{ activity.user?.name || activity.user?.username || 'System' }}
                                </TableCell>

                                <TableCell>
                                    {{ activity.action || '—' }}
                                </TableCell>

                                <TableCell>
                                    {{ formatFieldName(activity.field_name) }}
                                </TableCell>

                                <TableCell>
                                    {{ activity.old_value || '—' }}
                                </TableCell>

                                <TableCell>
                                    {{ activity.new_value || '—' }}
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>

                <div
                    v-else
                    class="text-sm text-muted-foreground"
                >
                    No position changes have been recorded.
                </div>
            </CardContent>
        </Card>

        <!-- Delete Assignment Dialog -->
        <AlertDialog
            :open="deleteDialogOpen"
            @update:open="deleteDialogOpen = $event"
        >
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>
                        Delete Assignment?
                    </AlertDialogTitle>

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
import { ref } from 'vue'

import {
    Link,
    router,
} from '@inertiajs/vue3'

import { MoreHorizontal } from 'lucide-vue-next'
import { useAuth } from '@/composables/useAuth'

import DetailItem from '@/components/DetailItem.vue'

import { Button } from '@/components/ui/button'

import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card'

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

    jobTitleSkills: {
        type: Array,
        default: () => [],
    },

    jobTitleTasks: {
        type: Array,
        default: () => [],
    },

    customSkills: {
        type: Array,
        default: () => [],
    },

    customTasks: {
        type: Array,
        default: () => [],
    },
})

/*
|--------------------------------------------------------------------------
| Template Data
|--------------------------------------------------------------------------
*/

const position = props.position
const jobTitleSkills = props.jobTitleSkills ?? []
const jobTitleTasks = props.jobTitleTasks ?? []
const customSkills = props.customSkills ?? []
const customTasks = props.customTasks ?? []

/*
|--------------------------------------------------------------------------
| Local State
|--------------------------------------------------------------------------
*/

const deleteDialogOpen = ref(false)
const assignmentToDelete = ref(null)

/*
|--------------------------------------------------------------------------
| Computed-Like Helpers
|--------------------------------------------------------------------------
*/

function activeAssignments() {
    if (!position.assignments) {
        return []
    }

    return position.assignments.filter((assignment) => {
        const status = String(
            assignment.assignment_status || ''
        ).toLowerCase()

        return status === 'active' || !assignment.end_date
    })
}

/*
|--------------------------------------------------------------------------
| Formatting Helpers
|--------------------------------------------------------------------------
*/

function yesNo(value) {
    return value ? 'Yes' : 'No'
}

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

function organizationName(organization) {
    if (!organization) {
        return '—'
    }

    return organization.full_path || organization.name || '—'
}

function formatFieldName(fieldName) {
    if (!fieldName) {
        return '—'
    }

    return fieldName
        .replaceAll('_', ' ')
        .replace(/\b\w/g, (char) => char.toUpperCase())
}

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

function openDeleteDialog(id) {
    assignmentToDelete.value = id
    deleteDialogOpen.value = true
}

function confirmDelete() {
    if (!assignmentToDelete.value) {
        return
    }

    router.delete(`/position-assignments/${assignmentToDelete.value}`, {
        data: {
            return_to: `/positions/${position.id}`,
        },

        preserveScroll: true,

        onFinish: () => {
            deleteDialogOpen.value = false
            assignmentToDelete.value = null
        },
    })
}
</script>