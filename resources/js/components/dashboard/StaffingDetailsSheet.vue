<script setup lang="ts">
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import {
    CalendarDays,
    CheckCircle2,
    Circle,
    Clock3,
    ExternalLink,
    UserRound,
    Workflow,
} from 'lucide-vue-next'
import StatusBadge from '@/components/data/StatusBadge.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Separator } from '@/components/ui/separator'
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet'

type WorkflowStep = {
    id: number
    name: string
    step_order: number
    status_code: string | null
    requested_at: string | null
    scheduled_at: string | null
    completed_at: string | null
    notes: string | null
    comments: string | null
    has_event: boolean
}

type PersonSummary = {
    id: number
    person_code: string | null
    name: string
    first_name: string | null
    alternate_first_name: string | null
    preferred_name: string | null
    last_name: string | null
    alternate_last_name: string | null
    company_name: string | null
    employment_status: string | null
}

type WorkflowCandidate = {
    candidate_id: number
    candidate_code: string | null
    candidate_status: string | null
    scheduled_start_date: string | null
    person: PersonSummary | null
    workflow_id: number | null
    workflow_name: string | null
    current_step: string
    current_step_number: number
    step_count: number
    steps: WorkflowStep[]
}

type StaffingPosition = {
    id: number
    position_code: string | null
    title: string | null
    level: number | null
    team_name: string | null
    project_team_name: string | null
    location: string | null
    building: string | null
    created_at: string | null
    closed_at: string | null
    staffing_label: string
    current_person: PersonSummary | null
    employer: string | null
    actual_start_date: string | null
    departure_date: string | null
    assignment_status: string | null
    scheduled_start_date: string | null
    last_updated: string | null
    workflow_candidates: WorkflowCandidate[]
}

const props = defineProps<{
    open: boolean
    position: StaffingPosition | null
}>()

const emit = defineEmits<{
    'update:open': [value: boolean]
}>()

const hasWorkflow = computed(() => Boolean(props.position?.workflow_candidates?.length))

function formatDate(value: string | null | undefined, includeTime = false): string {
    if (!value) return '—'

    const date = new Date(value.length === 10 ? `${value}T00:00:00` : value)

    if (Number.isNaN(date.getTime())) return value

    return new Intl.DateTimeFormat('en-US', includeTime
        ? { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' }
        : { month: 'short', day: 'numeric', year: 'numeric' },
    ).format(date)
}

function formatLabel(value: string | null | undefined): string {
    if (!value) return '—'

    return value
        .replaceAll('_', ' ')
        .replaceAll('-', ' ')
        .replace(/\b\w/g, (letter) => letter.toUpperCase())
}

function stepState(step: WorkflowStep): 'complete' | 'active' | 'pending' {
    if (step.completed_at) return 'complete'
    if (step.has_event || step.status_code || step.requested_at || step.scheduled_at) return 'active'
    return 'pending'
}

function stepDate(step: WorkflowStep): string | null {
    return step.completed_at ?? step.scheduled_at ?? step.requested_at
}

function staffingTone(label: string): 'success' | 'warning' | 'danger' | 'info' | 'neutral' {
    const value = label.toLowerCase()

    if (value === 'filled') return 'success'
    if (value === 'selected') return 'info'
    if (value === 'departing' || value === 'on-hold') return 'warning'
    if (value === 'vacant') return 'danger'

    return 'neutral'
}
</script>

<template>
    <Sheet :open="open" @update:open="emit('update:open', $event)">
        <SheetContent class="w-full overflow-y-auto sm:max-w-2xl lg:max-w-3xl">
            <template v-if="position">
                <SheetHeader class="pr-6">
                    <div class="flex flex-wrap items-center gap-2">
                        <SheetTitle>
                            {{ position.position_code || 'Position' }} — {{ position.title || 'Untitled Position' }}
                        </SheetTitle>
                        <StatusBadge
                            :label="position.staffing_label"
                            :tone="staffingTone(position.staffing_label)"
                        />
                    </div>
                    <SheetDescription>
                        Staffing details and the Candidate Workflow currently assigned to each candidate.
                    </SheetDescription>
                </SheetHeader>

                <div class="mt-6 space-y-7">
                    <section>
                        <div class="mb-3 flex items-center justify-between gap-3">
                            <h3 class="text-sm font-semibold uppercase tracking-wide text-muted-foreground">
                                Position
                            </h3>
                            <Link :href="`/portal/positions/${position.id}`">
                                <Button variant="outline" size="sm">
                                    Open Position
                                    <ExternalLink class="h-3.5 w-3.5" />
                                </Button>
                            </Link>
                        </div>

                        <dl class="grid gap-x-6 gap-y-4 rounded-xl border bg-muted/20 p-4 sm:grid-cols-2">
                            <div>
                                <dt class="text-xs font-medium text-muted-foreground">ID</dt>
                                <dd class="mt-1 font-medium">{{ position.position_code || '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-muted-foreground">Job Title</dt>
                                <dd class="mt-1 font-medium">{{ position.title || '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-muted-foreground">Level</dt>
                                <dd class="mt-1">{{ position.level ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-muted-foreground">Team Name</dt>
                                <dd class="mt-1">{{ position.team_name || '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-muted-foreground">Project Team</dt>
                                <dd class="mt-1">{{ position.project_team_name || '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-muted-foreground">Location / Building</dt>
                                <dd class="mt-1">
                                    {{ position.location || '—' }}
                                    <template v-if="position.building"> · {{ position.building }}</template>
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-muted-foreground">Created</dt>
                                <dd class="mt-1">{{ formatDate(position.created_at) }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-muted-foreground">Closed</dt>
                                <dd class="mt-1">{{ formatDate(position.closed_at) }}</dd>
                            </div>
                        </dl>
                    </section>

                    <Separator />

                    <section>
                        <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-muted-foreground">
                            Current Staffing
                        </h3>

                        <div class="rounded-xl border p-4">
                            <div v-if="position.current_person" class="flex items-start gap-3">
                                <div class="rounded-lg bg-muted p-2">
                                    <UserRound class="h-5 w-5" />
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="font-semibold">{{ position.current_person.name }}</p>
                                        <Badge v-if="position.assignment_status" variant="outline">
                                            {{ formatLabel(position.assignment_status) }}
                                        </Badge>
                                    </div>
                                    <p class="mt-1 text-sm text-muted-foreground">
                                        {{ position.current_person.company_name || position.employer || 'Employer not listed' }}
                                        <template v-if="position.current_person.employment_status">
                                            · {{ formatLabel(position.current_person.employment_status) }}
                                        </template>
                                    </p>
                                    <div class="mt-3 grid gap-3 text-sm sm:grid-cols-3">
                                        <div>
                                            <p class="text-xs text-muted-foreground">Scheduled Start</p>
                                            <p class="mt-1">{{ formatDate(position.scheduled_start_date) }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-muted-foreground">Actual Start</p>
                                            <p class="mt-1">{{ formatDate(position.actual_start_date) }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-muted-foreground">Departure</p>
                                            <p class="mt-1">{{ formatDate(position.departure_date) }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <p v-else class="text-sm text-muted-foreground">
                                No active position assignment is recorded.
                            </p>
                        </div>
                    </section>

                    <Separator />

                    <section>
                        <div class="mb-4">
                            <h3 class="flex items-center gap-2 text-sm font-semibold uppercase tracking-wide text-muted-foreground">
                                <Workflow class="h-4 w-4" />
                                Candidate Workflow
                            </h3>
                            <p class="mt-1 text-sm text-muted-foreground">
                                Steps are read directly from each candidate's assigned workflow, so the names and number of steps can change by customer.
                            </p>
                        </div>

                        <div v-if="hasWorkflow" class="space-y-5">
                            <article
                                v-for="candidate in position.workflow_candidates"
                                :key="candidate.candidate_id"
                                class="rounded-xl border"
                            >
                                <header class="flex flex-col gap-3 border-b bg-muted/20 p-4 sm:flex-row sm:items-start sm:justify-between">
                                    <div>
                                        <div class="flex flex-wrap items-center gap-2">
                                            <p class="font-semibold">
                                                {{ candidate.person?.name || candidate.candidate_code || `Candidate ${candidate.candidate_id}` }}
                                            </p>
                                            <Badge v-if="candidate.candidate_status" variant="outline">
                                                {{ formatLabel(candidate.candidate_status) }}
                                            </Badge>
                                        </div>
                                        <p class="mt-1 text-sm text-muted-foreground">
                                            {{ candidate.workflow_name || 'No workflow assigned' }}
                                            <template v-if="candidate.workflow_name">
                                                · Current: {{ candidate.current_step }}
                                            </template>
                                        </p>
                                    </div>

                                    <Link :href="`/portal/candidates/${candidate.candidate_id}`">
                                        <Button variant="outline" size="sm">
                                            Open Candidate
                                            <ExternalLink class="h-3.5 w-3.5" />
                                        </Button>
                                    </Link>
                                </header>

                                <div v-if="candidate.steps.length" class="p-4">
                                    <ol class="space-y-0">
                                        <li
                                            v-for="(step, index) in candidate.steps"
                                            :key="step.id"
                                            class="relative flex gap-3 pb-5 last:pb-0"
                                        >
                                            <div
                                                v-if="index < candidate.steps.length - 1"
                                                class="absolute left-[11px] top-6 h-[calc(100%-1rem)] w-px bg-border"
                                            />

                                            <div class="relative z-10 mt-0.5 bg-background">
                                                <CheckCircle2
                                                    v-if="stepState(step) === 'complete'"
                                                    class="h-6 w-6 text-emerald-600"
                                                />
                                                <Clock3
                                                    v-else-if="stepState(step) === 'active'"
                                                    class="h-6 w-6 text-amber-600"
                                                />
                                                <Circle
                                                    v-else
                                                    class="h-6 w-6 text-muted-foreground/50"
                                                />
                                            </div>

                                            <div class="min-w-0 flex-1">
                                                <div class="flex flex-wrap items-center justify-between gap-2">
                                                    <p class="font-medium">
                                                        {{ step.step_order }}. {{ step.name }}
                                                    </p>
                                                    <Badge v-if="step.status_code" variant="outline">
                                                        {{ formatLabel(step.status_code) }}
                                                    </Badge>
                                                </div>

                                                <div v-if="stepDate(step)" class="mt-1 flex items-center gap-1.5 text-xs text-muted-foreground">
                                                    <CalendarDays class="h-3.5 w-3.5" />
                                                    <span v-if="step.completed_at">Completed {{ formatDate(step.completed_at, true) }}</span>
                                                    <span v-else-if="step.scheduled_at">Scheduled {{ formatDate(step.scheduled_at, true) }}</span>
                                                    <span v-else>Requested {{ formatDate(step.requested_at, true) }}</span>
                                                </div>
                                                <p v-else class="mt-1 text-xs text-muted-foreground">Not started</p>

                                                <div v-if="step.notes || step.comments" class="mt-2 rounded-lg bg-muted/40 p-2.5 text-sm">
                                                    <p v-if="step.notes"><span class="font-medium">Notes:</span> {{ step.notes }}</p>
                                                    <p v-if="step.comments" :class="step.notes ? 'mt-1' : ''"><span class="font-medium">Comments:</span> {{ step.comments }}</p>
                                                </div>
                                            </div>
                                        </li>
                                    </ol>
                                </div>

                                <p v-else class="p-4 text-sm text-muted-foreground">
                                    This candidate does not currently have workflow steps configured.
                                </p>
                            </article>
                        </div>

                        <div v-else class="rounded-xl border border-dashed p-6 text-center text-sm text-muted-foreground">
                            No candidate workflow is currently associated with this position.
                        </div>
                    </section>

                    <p class="border-t pt-4 text-xs text-muted-foreground">
                        Last staffing-related update: {{ formatDate(position.last_updated, true) }}
                    </p>
                </div>
            </template>
        </SheetContent>
    </Sheet>
</template>
