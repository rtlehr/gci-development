<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import {
    Award,
    BriefcaseBusiness,
    Building2,
    CalendarDays,
    ClipboardList,
    FileClock,
    Flag,
    MapPin,
    MoreHorizontal,
    Pencil,
    Plus,
    ShieldCheck,
    Users,
    Wrench,
} from 'lucide-vue-next'
import { useAuth } from '@/composables/useAuth'
import DetailItem from '@/components/DetailItem.vue'
import PageContainer from '@/components/layout/PageContainer.vue'
import PageHeader from '@/components/layout/PageHeader.vue'
import PositionSectionNavigation, {
    type PositionSection,
} from '@/components/positions/PositionSectionNavigation.vue'
import StatCard from '@/components/data/StatCard.vue'
import StatusBadge from '@/components/data/StatusBadge.vue'
import DetailCard from '@/components/show/DetailCard.vue'
import FlagItem from '@/components/show/FlagItem.vue'
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

type GenericRecord = Record<string, any>

const props = withDefaults(defineProps<{
    position: GenericRecord
    jobTitleSkills?: GenericRecord[]
    jobTitleTasks?: GenericRecord[]
    customSkills?: GenericRecord[]
    customTasks?: GenericRecord[]
    positionCandidates?: GenericRecord[]
    initialSection?: PositionSection
}>(), {
    jobTitleSkills: () => [],
    jobTitleTasks: () => [],
    customSkills: () => [],
    customTasks: () => [],
    positionCandidates: () => [],
    initialSection: 'general',
})

const { can } = useAuth()
const activeSection = ref<PositionSection>(props.initialSection)
const deleteDialogOpen = ref(false)
const assignmentToDelete = ref<number | null>(null)

watch(activeSection, (section) => {
    const url = new URL(window.location.href)
    url.searchParams.set('section', section)
    window.history.replaceState({}, '', url)
})

const activeAssignments = computed(() =>
    (props.position.assignments ?? []).filter((assignment: GenericRecord) => {
        const status = String(assignment.assignment_status ?? '').toLowerCase()

        return status === 'active' || !assignment.end_date
    }),
)

const requiredJobTitleSkills = computed(() =>
    props.jobTitleSkills.filter((skill) => skill.requirement_type !== 'desired'),
)

const desiredJobTitleSkills = computed(() =>
    props.jobTitleSkills.filter((skill) => skill.requirement_type === 'desired'),
)

const requiredCustomSkills = computed(() =>
    props.customSkills.filter((skill) => skill.requirement_type !== 'desired'),
)

const desiredCustomSkills = computed(() =>
    props.customSkills.filter((skill) => skill.requirement_type === 'desired'),
)

const requiredJobTitleTasks = computed(() =>
    props.jobTitleTasks.filter((task) => task.requirement_type !== 'desired'),
)

const desiredJobTitleTasks = computed(() =>
    props.jobTitleTasks.filter((task) => task.requirement_type === 'desired'),
)

const statusTone = computed(() => {
    const status = String(props.position.status ?? '').toLowerCase()

    if (['filled', 'active', 'approved'].includes(status)) {
        return 'success'
    }

    if (['in process', 'pending', 'on hold'].includes(status)) {
        return 'warning'
    }

    if (['closed', 'cancelled', 'canceled'].includes(status)) {
        return 'danger'
    }

    return 'info'
})

const assignmentCount = computed(() => props.position.assignments?.length ?? 0)
const skillCount = computed(
    () => props.jobTitleSkills.length + props.customSkills.length,
)
const taskCount = computed(
    () => props.jobTitleTasks.length + props.customTasks.length,
)

function formatDate(value: unknown): string {
    if (!value) {
        return '—'
    }

    const date = new Date(String(value))

    return Number.isNaN(date.getTime())
        ? String(value)
        : date.toLocaleDateString()
}

function formatDateTime(value: unknown): string {
    if (!value) {
        return '—'
    }

    const date = new Date(String(value))

    return Number.isNaN(date.getTime())
        ? String(value)
        : date.toLocaleString()
}

function organizationName(
    organization: GenericRecord | null | undefined,
): string {
    return organization?.full_path || organization?.name || '—'
}

function formatFieldName(fieldName: string | null | undefined): string {
    if (!fieldName) {
        return '—'
    }

    return fieldName
        .replaceAll('_', ' ')
        .replace(/\b\w/g, (character) => character.toUpperCase())
}

function fullName(person: GenericRecord | null | undefined): string {
    if (!person) {
        return '—'
    }

    return `${person.first_name ?? ''} ${person.last_name ?? ''}`.trim() || '—'
}

function openDeleteDialog(id: number): void {
    assignmentToDelete.value = id
    deleteDialogOpen.value = true
}

function confirmDelete(): void {
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
</script>

<template>
    <PageContainer size="wide">
        <PageHeader
            :title="position.job_title || 'Position Details'"
            :description="`Position code ${position.position_code || 'not assigned'}`"
            eyebrow="Position"
            back-href="/positions"
            back-label="Back to positions"
        >
            <template #meta>
                <div class="flex flex-wrap items-center gap-2 pt-1">
                    <StatusBadge
                        :label="position.status || 'Unknown'"
                        :tone="statusTone"
                    />

                    <span
                        v-if="position.location"
                        class="inline-flex items-center gap-1 text-sm text-muted-foreground"
                    >
                        <MapPin
                            class="h-4 w-4"
                            aria-hidden="true"
                        />
                        {{ position.location }}
                    </span>

                    <span
                        v-if="position.component"
                        class="inline-flex items-center gap-1 text-sm text-muted-foreground"
                    >
                        <Building2
                            class="h-4 w-4"
                            aria-hidden="true"
                        />
                        {{ position.component }}
                    </span>
                </div>
            </template>

            <template #actions>
                <Link
                    v-if="can('view_admin')"
                    :href="`/position-assignments/create?position_id=${position.id}`"
                >
                    <Button variant="outline">
                        <Plus class="mr-2 h-4 w-4" />
                        Add Assignment
                    </Button>
                </Link>

                <Link
                    v-if="can('view_admin')"
                    :href="`/positions/${position.id}/edit?section=${activeSection}`"
                >
                    <Button>
                        <Pencil class="mr-2 h-4 w-4" />
                        Edit Position
                    </Button>
                </Link>
            </template>
        </PageHeader>

        <div class="space-y-6">
            <PositionSectionNavigation
                v-model="activeSection"
                :candidate-count="positionCandidates.length"
            />

            <template v-if="activeSection === 'general'">
                <section
                    class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4"
                    aria-label="Position summary"
                >
                    <StatCard
                        label="Active assignments"
                        :value="activeAssignments.length"
                        description="Currently assigned people"
                        :icon="Users"
                    />

                    <StatCard
                        label="Skills"
                        :value="skillCount"
                        description="Default and custom skills"
                        :icon="Wrench"
                    />

                    <StatCard
                        label="Tasks"
                        :value="taskCount"
                        description="Default and custom tasks"
                        :icon="ClipboardList"
                    />

                    <StatCard
                        label="Assignment history"
                        :value="assignmentCount"
                        description="All recorded assignments"
                        :icon="FileClock"
                    />
                </section>

                <section class="grid gap-6 xl:grid-cols-3">
                    <DetailCard
                        title="Position information"
                        description="Core staffing and classification details"
                        :icon="BriefcaseBusiness"
                        class="xl:col-span-2"
                    >
                        <div class="grid gap-x-8 gap-y-5 sm:grid-cols-2 lg:grid-cols-3">
                            <DetailItem
                                label="Position Code"
                                :value="position.position_code"
                            />
                            <DetailItem
                                label="Status"
                                :value="position.status"
                            />
                            <DetailItem
                                label="Job Title"
                                :value="position.job_title"
                            />
                            <DetailItem
                                label="Experience Level"
                                :value="position.experience_level"
                            />
                            <DetailItem
                                label="Labor Category"
                                :value="position.labor_category"
                            />
                            <DetailItem
                                label="Team Name"
                                :value="position.team_name"
                            />
                            <DetailItem
                                label="Project Manager"
                                :value="position.project_manager_name"
                            />
                            <DetailItem
                                label="Component"
                                :value="position.component"
                            />
                            <DetailItem
                                label="Location"
                                :value="position.location"
                            />
                            <DetailItem
                                label="Building"
                                :value="position.building"
                            />
                            <DetailItem
                                label="Project Team"
                                :value="position.project_team_name"
                            />
                            <DetailItem
                                label="Customer Lead"
                                :value="position.customer_lead_name"
                            />
                            <DetailItem
                                label="Customer Created"
                                :value="formatDate(position.customer_created_at)"
                            />
                        </div>
                    </DetailCard>

                    <DetailCard
                        title="Flags and risk"
                        description="Operational requirements and closure indicators"
                        :icon="Flag"
                    >
                        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-1">
                            <FlagItem
                                label="Essential position"
                                :active="Boolean(position.is_essential)"
                            />
                            <FlagItem
                                label="Travel required"
                                :active="Boolean(position.travel_required)"
                            />
                            <FlagItem
                                label="High risk role"
                                :active="Boolean(position.high_risk_role)"
                            />
                            <FlagItem
                                label="Requested to close"
                                :active="Boolean(position.request_to_close)"
                            />
                        </div>
                    </DetailCard>
                </section>

                <DetailCard
                    title="Organizations"
                    description="Ownership, sponsorship, and funding relationships"
                    :icon="Building2"
                >
                    <div class="grid gap-x-8 gap-y-5 md:grid-cols-3">
                        <DetailItem
                            label="Position Organization"
                            :value="organizationName(position.position_organization)"
                        />
                        <DetailItem
                            label="Sponsoring Organization"
                            :value="organizationName(position.sponsoring_organization)"
                        />
                        <DetailItem
                            label="Funding Organization"
                            :value="organizationName(position.funding_organization)"
                        />
                    </div>
                </DetailCard>

                <section class="grid gap-6 xl:grid-cols-2">
                    <DetailCard
                        title="Mission description"
                        :icon="BriefcaseBusiness"
                    >
                        <p class="whitespace-pre-line text-sm leading-6">
                            {{ position.mission_description || 'No mission description available.' }}
                        </p>
                    </DetailCard>

                    <DetailCard
                        title="Funding information"
                        :icon="Building2"
                    >
                        <p class="whitespace-pre-line text-sm leading-6">
                            {{ position.funding_info || 'No funding information available.' }}
                        </p>
                    </DetailCard>
                </section>

                <section class="grid gap-6 xl:grid-cols-2">
                    <DetailCard
                        title="Closure workflow"
                        :icon="CalendarDays"
                    >
                        <div class="grid gap-x-8 gap-y-5 sm:grid-cols-2">
                            <DetailItem
                                label="Requested To Close"
                                :value="position.request_to_close ? 'Yes' : 'No'"
                            />
                            <DetailItem
                                label="Scheduled To Close"
                                :value="formatDate(position.scheduled_to_close)"
                            />
                            <DetailItem
                                label="Close Date"
                                :value="formatDate(position.close_date)"
                            />
                            <DetailItem
                                label="Close Reason"
                                :value="position.close_reason"
                            />
                        </div>
                    </DetailCard>

                    <DetailCard
                        title="Additional information"
                        :icon="ClipboardList"
                    >
                        <div class="space-y-5">
                            <div class="grid gap-x-8 gap-y-5 sm:grid-cols-2">
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
                                <h3 class="text-sm font-medium text-muted-foreground">
                                    Notes
                                </h3>
                                <p class="mt-1 whitespace-pre-line text-sm leading-6">
                                    {{ position.notes || 'No notes available.' }}
                                </p>
                            </div>
                        </div>
                    </DetailCard>
                </section>

                <Card>
                    <CardHeader class="border-b">
                        <CardTitle class="text-lg">
                            Position change history
                        </CardTitle>
                    </CardHeader>

                    <CardContent class="p-0">
                        <div
                            v-if="position.activities?.length"
                            class="overflow-x-auto"
                        >
                            <Table class="min-w-[1000px]">
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
                                        <TableCell class="whitespace-nowrap">
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
                                        <TableCell class="max-w-xs whitespace-normal">
                                            {{ activity.old_value || '—' }}
                                        </TableCell>
                                        <TableCell class="max-w-xs whitespace-normal">
                                            {{ activity.new_value || '—' }}
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>

                        <p
                            v-else
                            class="p-8 text-center text-sm text-muted-foreground"
                        >
                            No position changes have been recorded.
                        </p>
                    </CardContent>
                </Card>
            </template>

            <template v-else-if="activeSection === 'requirements'">
                <DetailCard
                    title="Skills"
                    description="Default job-title skills and position-specific additions"
                    :icon="Award"
                >
                    <div class="space-y-6">
                        <div>
                            <h3 class="text-sm font-semibold">
                                Default Skills
                            </h3>

                            <div
                                v-if="jobTitleSkills.length"
                                class="mt-3 grid gap-4 lg:grid-cols-2"
                            >
                                <div class="rounded-lg border bg-muted/30 p-4">
                                    <h4 class="text-sm font-semibold">
                                        Required Skills ({{ requiredJobTitleSkills.length }})
                                    </h4>

                                    <ol
                                        v-if="requiredJobTitleSkills.length"
                                        class="mt-3 list-decimal space-y-3 pl-6 text-sm"
                                    >
                                        <li
                                            v-for="skill in requiredJobTitleSkills"
                                            :key="skill.id"
                                            class="pl-1"
                                        >
                                            <p class="font-medium">
                                                {{ skill.name }}
                                            </p>
                                            <p
                                                v-if="skill.description"
                                                class="mt-1 text-xs text-muted-foreground"
                                            >
                                                {{ skill.description }}
                                            </p>
                                        </li>
                                    </ol>

                                    <p
                                        v-else
                                        class="mt-3 text-sm text-muted-foreground"
                                    >
                                        No required skills are assigned.
                                    </p>
                                </div>

                                <div class="rounded-lg border bg-muted/30 p-4">
                                    <h4 class="text-sm font-semibold">
                                        Desired Skills ({{ desiredJobTitleSkills.length }})
                                    </h4>

                                    <ol
                                        v-if="desiredJobTitleSkills.length"
                                        class="mt-3 list-decimal space-y-3 pl-6 text-sm"
                                    >
                                        <li
                                            v-for="skill in desiredJobTitleSkills"
                                            :key="skill.id"
                                            class="pl-1"
                                        >
                                            <p class="font-medium">
                                                {{ skill.name }}
                                            </p>
                                            <p
                                                v-if="skill.description"
                                                class="mt-1 text-xs text-muted-foreground"
                                            >
                                                {{ skill.description }}
                                            </p>
                                        </li>
                                    </ol>

                                    <p
                                        v-else
                                        class="mt-3 text-sm text-muted-foreground"
                                    >
                                        No desired skills are assigned.
                                    </p>
                                </div>
                            </div>

                            <p
                                v-else
                                class="mt-2 text-sm text-muted-foreground"
                            >
                                No default skills are assigned to this job title.
                            </p>
                        </div>

                        <div class="border-t pt-5">
                            <h3 class="text-sm font-semibold">
                                Custom Position Skills
                            </h3>

                            <div
                                v-if="customSkills.length"
                                class="mt-3 grid gap-4 lg:grid-cols-2"
                            >
                                <div class="rounded-lg border p-4">
                                    <h4 class="text-sm font-semibold">
                                        Required Skills ({{ requiredCustomSkills.length }})
                                    </h4>

                                    <ol
                                        v-if="requiredCustomSkills.length"
                                        class="mt-3 list-decimal space-y-3 pl-6 text-sm"
                                    >
                                        <li
                                            v-for="skill in requiredCustomSkills"
                                            :key="skill.id"
                                            class="pl-1"
                                        >
                                            <p class="font-medium">
                                                {{ skill.name }}
                                            </p>
                                            <p
                                                v-if="skill.description"
                                                class="mt-1 text-xs text-muted-foreground"
                                            >
                                                {{ skill.description }}
                                            </p>
                                        </li>
                                    </ol>

                                    <p
                                        v-else
                                        class="mt-3 text-sm text-muted-foreground"
                                    >
                                        No required custom skills have been added.
                                    </p>
                                </div>

                                <div class="rounded-lg border p-4">
                                    <h4 class="text-sm font-semibold">
                                        Desired Skills ({{ desiredCustomSkills.length }})
                                    </h4>

                                    <ol
                                        v-if="desiredCustomSkills.length"
                                        class="mt-3 list-decimal space-y-3 pl-6 text-sm"
                                    >
                                        <li
                                            v-for="skill in desiredCustomSkills"
                                            :key="skill.id"
                                            class="pl-1"
                                        >
                                            <p class="font-medium">
                                                {{ skill.name }}
                                            </p>
                                            <p
                                                v-if="skill.description"
                                                class="mt-1 text-xs text-muted-foreground"
                                            >
                                                {{ skill.description }}
                                            </p>
                                        </li>
                                    </ol>

                                    <p
                                        v-else
                                        class="mt-3 text-sm text-muted-foreground"
                                    >
                                        No desired custom skills have been added.
                                    </p>
                                </div>
                            </div>

                            <p
                                v-else
                                class="mt-2 text-sm text-muted-foreground"
                            >
                                No custom skills have been added.
                            </p>
                        </div>
                    </div>
                </DetailCard>

                <DetailCard
                    title="Tasks"
                    description="Default job-title tasks and position-specific responsibilities"
                    :icon="ClipboardList"
                >
                    <div class="space-y-6">
                        <div>
                            <h3 class="text-sm font-semibold">
                                Default Tasks
                            </h3>

                            <div
                                v-if="jobTitleTasks.length"
                                class="mt-3 grid gap-4 lg:grid-cols-2"
                            >
                                <div class="rounded-lg border bg-muted/30 p-4">
                                    <h4 class="text-sm font-semibold">
                                        Required Tasks ({{ requiredJobTitleTasks.length }})
                                    </h4>

                                    <ol
                                        v-if="requiredJobTitleTasks.length"
                                        class="mt-3 list-decimal space-y-3 pl-6 text-sm"
                                    >
                                        <li
                                            v-for="task in requiredJobTitleTasks"
                                            :key="task.id"
                                            class="pl-1"
                                        >
                                            <p class="font-medium">
                                                {{ task.name }}
                                            </p>
                                            <p
                                                v-if="task.description"
                                                class="mt-1 text-xs text-muted-foreground"
                                            >
                                                {{ task.description }}
                                            </p>
                                        </li>
                                    </ol>

                                    <p
                                        v-else
                                        class="mt-3 text-sm text-muted-foreground"
                                    >
                                        No required tasks are assigned.
                                    </p>
                                </div>

                                <div class="rounded-lg border bg-muted/30 p-4">
                                    <h4 class="text-sm font-semibold">
                                        Desired Tasks ({{ desiredJobTitleTasks.length }})
                                    </h4>

                                    <ol
                                        v-if="desiredJobTitleTasks.length"
                                        class="mt-3 list-decimal space-y-3 pl-6 text-sm"
                                    >
                                        <li
                                            v-for="task in desiredJobTitleTasks"
                                            :key="task.id"
                                            class="pl-1"
                                        >
                                            <p class="font-medium">
                                                {{ task.name }}
                                            </p>
                                            <p
                                                v-if="task.description"
                                                class="mt-1 text-xs text-muted-foreground"
                                            >
                                                {{ task.description }}
                                            </p>
                                        </li>
                                    </ol>

                                    <p
                                        v-else
                                        class="mt-3 text-sm text-muted-foreground"
                                    >
                                        No desired tasks are assigned.
                                    </p>
                                </div>
                            </div>

                            <p
                                v-else
                                class="mt-2 text-sm text-muted-foreground"
                            >
                                No default tasks are assigned to this job title.
                            </p>
                        </div>

                        <div class="border-t pt-5">
                            <h3 class="text-sm font-semibold">
                                Custom Position Tasks ({{ customTasks.length }})
                            </h3>

                            <ol
                                v-if="customTasks.length"
                                class="mt-3 list-decimal space-y-3 pl-6 text-sm"
                            >
                                <li
                                    v-for="task in customTasks"
                                    :key="task.id"
                                    class="pl-1"
                                >
                                    <div class="rounded-lg border p-4">
                                        <p class="font-medium">
                                            {{ task.name }}
                                        </p>
                                        <p
                                            v-if="task.description"
                                            class="mt-1 text-xs text-muted-foreground"
                                        >
                                            {{ task.description }}
                                        </p>
                                    </div>
                                </li>
                            </ol>

                            <p
                                v-else
                                class="mt-2 text-sm text-muted-foreground"
                            >
                                No custom tasks have been added.
                            </p>
                        </div>
                    </div>
                </DetailCard>

                <DetailCard
                    title="Requirements and qualifications"
                    :icon="ShieldCheck"
                >
                    <div class="space-y-5">
                        <div>
                            <h3 class="text-sm font-medium text-muted-foreground">
                                Certifications Required
                            </h3>
                            <p class="mt-1 whitespace-pre-line text-sm">
                                {{ position.certifications_required || 'None provided.' }}
                            </p>
                        </div>

                        <div>
                            <h3 class="text-sm font-medium text-muted-foreground">
                                Training Required
                            </h3>
                            <p class="mt-1 whitespace-pre-line text-sm">
                                {{ position.training_required || 'None provided.' }}
                            </p>
                        </div>

                        <div>
                            <h3 class="text-sm font-medium text-muted-foreground">
                                Experience
                            </h3>
                            <p class="mt-1 whitespace-pre-line text-sm">
                                {{ position.experience || 'None provided.' }}
                            </p>
                        </div>
                    </div>
                </DetailCard>
            </template>

            <template v-else-if="activeSection === 'candidates'">
                <Card>
                    <CardHeader class="border-b">
                        <CardTitle class="text-lg">
                            Position Candidates
                        </CardTitle>
                    </CardHeader>

                    <CardContent class="p-0">
                        <div
                            v-if="positionCandidates.length"
                            class="overflow-x-auto"
                        >
                            <Table class="min-w-[1100px]">
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Candidate</TableHead>
                                        <TableHead>Candidate Code</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead>Workflow</TableHead>
                                        <TableHead>Current Step</TableHead>
                                        <TableHead>Submitted</TableHead>
                                        <TableHead>Scheduled Start</TableHead>
                                        <TableHead class="text-right">
                                            FBR
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>

                                <TableBody>
                                    <TableRow
                                        v-for="candidate in positionCandidates"
                                        :key="candidate.id"
                                    >
                                        <TableCell>
                                            <div class="font-medium">
                                                {{ candidate.person?.full_name || 'Unknown candidate' }}
                                            </div>

                                            <div
                                                v-if="candidate.person?.email"
                                                class="mt-1 text-xs text-muted-foreground"
                                            >
                                                {{ candidate.person.email }}
                                            </div>
                                        </TableCell>

                                        <TableCell>
                                            {{ candidate.candidate_code || '—' }}
                                        </TableCell>

                                        <TableCell>
                                            <StatusBadge
                                                :label="candidate.status || 'Unknown'"
                                                tone="info"
                                            />
                                        </TableCell>

                                        <TableCell>
                                            {{ candidate.workflow?.name || '—' }}
                                        </TableCell>

                                        <TableCell>
                                            <div>
                                                {{ candidate.workflow?.step_name || 'Not started' }}
                                            </div>

                                            <div
                                                v-if="candidate.workflow?.step_number && candidate.workflow?.step_count"
                                                class="mt-1 text-xs text-muted-foreground"
                                            >
                                                Step {{ candidate.workflow.step_number }}
                                                of {{ candidate.workflow.step_count }}
                                            </div>
                                        </TableCell>

                                        <TableCell>
                                            {{ formatDate(candidate.submitted_at) }}
                                        </TableCell>

                                        <TableCell>
                                            {{ formatDate(candidate.scheduled_start_date) }}
                                        </TableCell>

                                        <TableCell class="text-right">
                                            {{ candidate.candidate_fbr ?? '—' }}
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>

                        <div
                            v-else
                            class="p-10 text-center"
                        >
                            <Users
                                class="mx-auto h-10 w-10 text-muted-foreground/60"
                            />

                            <h3 class="mt-4 font-semibold">
                                No candidates assigned
                            </h3>

                            <p class="mt-1 text-sm text-muted-foreground">
                                This position does not currently have any candidates.
                            </p>
                        </div>
                    </CardContent>
                </Card>
            </template>
        </div>

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
                        This action cannot be undone. This will permanently delete
                        the assignment.
                    </AlertDialogDescription>
                </AlertDialogHeader>

                <AlertDialogFooter>
                    <AlertDialogCancel @click="deleteDialogOpen = false">
                        Cancel
                    </AlertDialogCancel>

                    <AlertDialogAction
                        class="bg-red-600 text-white hover:bg-red-700"
                        @click="confirmDelete"
                    >
                        Delete
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </PageContainer>
</template>
