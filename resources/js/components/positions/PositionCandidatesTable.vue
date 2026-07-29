<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import {
    ArrowRight,
    UserRound,
    Users,
    Workflow,
} from 'lucide-vue-next'
import CandidateStatusBadge from '@/components/positions/CandidateStatusBadge.vue'
import { Button } from '@/components/ui/button'
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card'
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table'
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip'

type CandidatePerson = {
    id: number
    full_name?: string | null
    email?: string | null
    person_code?: string | null
    primary_phone?: string | null
    primary_phone_extension?: string | null
}

type CandidateWorkflow = {
    id: number
    name?: string | null
    step_name?: string | null
    step_number?: number | null
    step_count?: number | null
    status_code?: string | null
}

export type PositionCandidate = {
    id: number
    candidate_code?: string | null
    status?: string | null
    candidate_fbr?: string | number | null
    submitted_at?: string | null
    scheduled_start_date?: string | null
    person?: CandidatePerson | null
    workflow?: CandidateWorkflow | null
}

const props = withDefaults(defineProps<{
    candidates: PositionCandidate[]
    showHeader?: boolean
    context?: 'admin' | 'portal'
}>(), {
    showHeader: true,
    context: 'admin',
})

function formatDate(value?: string | null): string {
    if (!value) {
        return '—'
    }

    const date = new Date(value)

    return Number.isNaN(date.getTime())
        ? value
        : date.toLocaleDateString(undefined, {
            month: 'short',
            day: 'numeric',
            year: 'numeric',
        })
}

function formatFbr(value?: string | number | null): string {
    if (value === null || value === undefined || value === '') {
        return '—'
    }

    const numericValue = Number(value)

    return Number.isNaN(numericValue)
        ? String(value)
        : numericValue.toLocaleString(undefined, {
            minimumFractionDigits: 0,
            maximumFractionDigits: 2,
        })
}

function fbrClasses(value?: string | number | null): string {
    const score = Number(value)

    if (Number.isNaN(score)) {
        return 'text-muted-foreground'
    }

    if (score >= 90) {
        return 'font-semibold text-emerald-700 dark:text-emerald-300'
    }

    if (score >= 70) {
        return 'font-semibold text-amber-700 dark:text-amber-300'
    }

    return 'font-semibold text-red-700 dark:text-red-300'
}

function personHref(candidate: PositionCandidate): string | null {
    if (!candidate.person?.id) {
        return null
    }

    return props.context === 'portal'
        ? `/portal/people/${candidate.person.id}`
        : `/people/${candidate.person.id}`
}

function candidateHref(candidate: PositionCandidate): string {
    return props.context === 'portal'
        ? `/portal/candidates/${candidate.id}`
        : `/candidates/${candidate.id}`
}

function candidateNameHref(candidate: PositionCandidate): string | null {
    return props.context === 'portal'
        ? candidateHref(candidate)
        : personHref(candidate)
}

function workflowHref(candidate: PositionCandidate): string {
    return candidateHref(candidate)
}
</script>

<template>
    <Card>
        <CardHeader
            v-if="showHeader"
            class="border-b"
        >
            <CardTitle class="flex items-center gap-2 text-lg">
                <span>Position Candidates</span>
                <span class="text-sm font-normal text-muted-foreground">
                    ({{ candidates.length }})
                </span>
            </CardTitle>
        </CardHeader>

        <CardContent class="p-0">
            <div
                v-if="candidates.length"
                class="overflow-x-auto"
            >
                <Table class="min-w-[1180px]">
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
                            <TableHead class="w-[110px] text-right">
                                Actions
                            </TableHead>
                        </TableRow>
                    </TableHeader>

                    <TableBody>
                        <TableRow
                            v-for="candidate in candidates"
                            :key="candidate.id"
                        >
                            <TableCell>
                                <div class="space-y-1">
                                    <Link
                                        v-if="candidateNameHref(candidate)"
                                        :href="candidateNameHref(candidate)!"
                                        class="font-medium text-primary underline-offset-4 hover:underline"
                                    >
                                        {{ candidate.person?.full_name || 'Unknown candidate' }}
                                    </Link>

                                    <span
                                        v-else
                                        class="font-medium"
                                    >
                                        {{ candidate.person?.full_name || 'Unknown candidate' }}
                                    </span>

                                    <div
                                        v-if="candidate.person?.email"
                                        class="text-xs text-muted-foreground"
                                    >
                                        {{ candidate.person.email }}
                                    </div>

                                    <div
                                        v-if="candidate.person?.primary_phone"
                                        class="text-xs text-muted-foreground"
                                    >
                                        {{ candidate.person.primary_phone }}
                                        <span v-if="candidate.person.primary_phone_extension">
                                            ext. {{ candidate.person.primary_phone_extension }}
                                        </span>
                                    </div>
                                </div>
                            </TableCell>

                            <TableCell>
                                {{ candidate.candidate_code || '—' }}
                            </TableCell>

                            <TableCell>
                                <CandidateStatusBadge :status="candidate.status" />
                            </TableCell>

                            <TableCell>
                                <Link
                                    :href="workflowHref(candidate)"
                                    class="group inline-flex max-w-[240px] items-start gap-2 text-primary underline-offset-4 hover:underline"
                                >
                                    <Workflow
                                        class="mt-0.5 h-4 w-4 shrink-0"
                                        aria-hidden="true"
                                    />

                                    <span>
                                        <span class="font-medium">
                                            {{ candidate.workflow?.name || 'View workflow' }}
                                        </span>

                                        <span
                                            v-if="candidate.workflow?.step_number && candidate.workflow?.step_count"
                                            class="mt-1 block text-xs text-muted-foreground"
                                        >
                                            Step {{ candidate.workflow.step_number }}
                                            of {{ candidate.workflow.step_count }}
                                        </span>
                                    </span>
                                </Link>
                            </TableCell>

                            <TableCell>
                                <Link
                                    :href="workflowHref(candidate)"
                                    class="inline-flex items-center gap-1 font-medium text-primary underline-offset-4 hover:underline"
                                >
                                    {{ candidate.workflow?.step_name || 'Not started' }}
                                    <ArrowRight
                                        class="h-3.5 w-3.5"
                                        aria-hidden="true"
                                    />
                                </Link>
                            </TableCell>

                            <TableCell>
                                {{ formatDate(candidate.submitted_at) }}
                            </TableCell>

                            <TableCell>
                                {{ formatDate(candidate.scheduled_start_date) }}
                            </TableCell>

                            <TableCell class="text-right">
                                <span :class="fbrClasses(candidate.candidate_fbr)">
                                    {{ formatFbr(candidate.candidate_fbr) }}
                                </span>
                            </TableCell>

                            <TableCell class="text-right">
                                <TooltipProvider>
                                    <div class="flex justify-end gap-1">
                                        <Tooltip v-if="personHref(candidate)">
                                            <TooltipTrigger as-child>
                                                <Button
                                                    as-child
                                                    variant="ghost"
                                                    size="icon"
                                                >
                                                    <Link
                                                        :href="personHref(candidate)!"
                                                        aria-label="View person"
                                                    >
                                                        <UserRound class="h-4 w-4" />
                                                    </Link>
                                                </Button>
                                            </TooltipTrigger>

                                            <TooltipContent>
                                                View person
                                            </TooltipContent>
                                        </Tooltip>

                                        <Tooltip>
                                            <TooltipTrigger as-child>
                                                <Button
                                                    as-child
                                                    variant="ghost"
                                                    size="icon"
                                                >
                                                    <Link
                                                        :href="workflowHref(candidate)"
                                                        aria-label="Open workflow"
                                                    >
                                                        <Workflow class="h-4 w-4" />
                                                    </Link>
                                                </Button>
                                            </TooltipTrigger>

                                            <TooltipContent>
                                                Open workflow
                                            </TooltipContent>
                                        </Tooltip>
                                    </div>
                                </TooltipProvider>
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
                    aria-hidden="true"
                />

                <h3 class="mt-4 font-semibold">
                    No candidates assigned
                </h3>

                <p class="mx-auto mt-1 max-w-md text-sm text-muted-foreground">
                    No candidates have been assigned to this position.
                    Candidates can be added from the Position Edit page.
                </p>
            </div>
        </CardContent>
    </Card>
</template>
