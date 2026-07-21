<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import {
    ArrowRight,
    Building2,
    CircleHelp,
    Clock3,
    UserRound,
    Users,
    Workflow,
} from 'lucide-vue-next'
import StatusBadge from '@/components/data/StatusBadge.vue'
import { Badge } from '@/components/ui/badge'
import {
    Card,
    CardContent,
    CardDescription,
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

type StatusTone = 'success' | 'warning' | 'danger' | 'info' | 'neutral'

type CandidateSummary = {
    id: number
    person_id: number | null
    name: string
    status: string | null
    stage: string
}

type ProjectManagerSummary = {
    id: number | null
    name: string | null
    email: string | null
    person_id: number | null
}

type PmoPosition = {
    id: number
    position_code: string | null
    title: string | null
    status: string | null
    candidates_count: number
    candidate_summaries: CandidateSummary[]
    current_stage: string
    current_stage_count: number
    current_stage_candidate_id: number | null
    days_open: number
    next_action: string
    next_action_tone: StatusTone
    project_manager: ProjectManagerSummary
}

defineProps<{
    positions: PmoPosition[]
}>()

function formatLabel(value: string | null): string {
    if (!value) {
        return 'Unknown'
    }

    return value
        .replaceAll('_', ' ')
        .replace(/\b\w/g, (letter) => letter.toUpperCase())
}

function statusTone(status: string | null): StatusTone {
    const normalized = String(status ?? '').toLowerCase()

    if (['filled', 'active', 'approved'].includes(normalized)) return 'success'
    if (['in process', 'pending', 'on hold'].includes(normalized)) return 'warning'
    if (['closed', 'cancelled', 'canceled'].includes(normalized)) return 'danger'
    if (normalized === 'open') return 'info'

    return 'neutral'
}

function candidateBadgeClass(count: number): string {
    if (count === 0) {
        return 'border-red-200 bg-red-50 text-red-700 dark:border-red-900 dark:bg-red-950 dark:text-red-300'
    }

    if (count <= 2) {
        return 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-300'
    }

    return 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-300'
}

function daysOpenClass(days: number): string {
    if (days < 30) {
        return 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-300'
    }

    if (days <= 90) {
        return 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-300'
    }

    return 'border-red-200 bg-red-50 text-red-700 dark:border-red-900 dark:bg-red-950 dark:text-red-300'
}

function currentStageHref(position: PmoPosition): string {
    return position.current_stage_candidate_id
        ? `/candidates/${position.current_stage_candidate_id}`
        : `/positions/${position.id}?section=candidates`
}
</script>

<template>
    <Card>
        <CardHeader class="border-b">
            <div class="flex items-start gap-3">
                <div class="rounded-lg border bg-muted/40 p-2">
                    <Building2 class="h-5 w-5" aria-hidden="true" />
                </div>

                <div>
                    <CardTitle>All Positions — PMO Overview</CardTitle>
                    <CardDescription class="mt-1">
                        Organization-wide position status, ownership, candidates, and workflow activity.
                    </CardDescription>
                </div>
            </div>
        </CardHeader>

        <CardContent class="p-0">
            <div
                v-if="positions.length === 0"
                class="m-5 rounded-lg border border-dashed p-8 text-center"
            >
                <Building2
                    class="mx-auto h-8 w-8 text-muted-foreground"
                    aria-hidden="true"
                />
                <p class="mt-3 text-sm font-medium">
                    No positions are available.
                </p>
            </div>

            <TooltipProvider
                v-else
                :delay-duration="250"
            >
                <div class="overflow-x-auto">
                    <Table class="min-w-[1380px]">
                        <TableHeader>
                            <TableRow>
                                <TableHead>Position Code</TableHead>
                                <TableHead>Position Title</TableHead>
                                <TableHead>Project Manager</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead class="text-right">Candidates</TableHead>
                                <TableHead>Current Stage</TableHead>
                                <TableHead>Days Open</TableHead>
                                <TableHead>Next Action</TableHead>
                            </TableRow>
                        </TableHeader>

                        <TableBody>
                            <TableRow
                                v-for="position in positions"
                                :key="position.id"
                            >
                                <TableCell class="font-medium">
                                    {{ position.position_code || '—' }}
                                </TableCell>

                                <TableCell>
                                    <Link
                                        :href="`/positions/${position.id}`"
                                        class="group inline-flex items-center gap-1.5 font-medium text-primary underline-offset-4 hover:underline"
                                    >
                                        {{ position.title || 'Untitled Position' }}
                                        <ArrowRight
                                            class="h-3.5 w-3.5 transition-transform group-hover:translate-x-0.5"
                                            aria-hidden="true"
                                        />
                                    </Link>
                                </TableCell>

                                <TableCell>
                                    <Link
                                        v-if="position.project_manager.person_id"
                                        :href="`/people/${position.project_manager.person_id}`"
                                        class="group inline-flex items-center gap-1.5 font-medium text-primary underline-offset-4 hover:underline"
                                    >
                                        <UserRound class="h-3.5 w-3.5" aria-hidden="true" />
                                        {{ position.project_manager.name }}
                                        <ArrowRight
                                            class="h-3.5 w-3.5 transition-transform group-hover:translate-x-0.5"
                                            aria-hidden="true"
                                        />
                                    </Link>

                                    <div
                                        v-else-if="position.project_manager.name"
                                        class="space-y-0.5"
                                    >
                                        <div class="inline-flex items-center gap-1.5 font-medium">
                                            <UserRound class="h-3.5 w-3.5" aria-hidden="true" />
                                            {{ position.project_manager.name }}
                                        </div>
                                        <div
                                            v-if="position.project_manager.email"
                                            class="text-xs text-muted-foreground"
                                        >
                                            {{ position.project_manager.email }}
                                        </div>
                                    </div>

                                    <StatusBadge
                                        v-else
                                        label="Unassigned"
                                        tone="warning"
                                    />
                                </TableCell>

                                <TableCell>
                                    <StatusBadge
                                        :label="formatLabel(position.status)"
                                        :tone="statusTone(position.status)"
                                    />
                                </TableCell>

                                <TableCell class="text-right">
                                    <Tooltip>
                                        <TooltipTrigger as-child>
                                            <Link
                                                :href="`/positions/${position.id}?section=candidates`"
                                                class="inline-flex rounded-full focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                                            >
                                                <Badge
                                                    variant="outline"
                                                    :class="['gap-1.5 font-medium transition hover:opacity-80', candidateBadgeClass(position.candidates_count)]"
                                                >
                                                    <Users class="h-3.5 w-3.5" aria-hidden="true" />
                                                    {{ position.candidates_count }}
                                                    <CircleHelp
                                                        class="h-3 w-3 opacity-70"
                                                        aria-hidden="true"
                                                    />
                                                </Badge>
                                            </Link>
                                        </TooltipTrigger>

                                        <TooltipContent
                                            side="left"
                                            class="w-72 p-0"
                                        >
                                            <div class="border-b px-3 py-2">
                                                <p class="font-semibold">
                                                    Position Candidates
                                                </p>
                                                <p class="text-xs text-muted-foreground">
                                                    Click to open the Candidates tab.
                                                </p>
                                            </div>

                                            <div
                                                v-if="position.candidate_summaries.length"
                                                class="space-y-1 p-2"
                                            >
                                                <div
                                                    v-for="candidate in position.candidate_summaries"
                                                    :key="candidate.id"
                                                    class="flex items-start gap-2 rounded-md px-2 py-1.5"
                                                >
                                                    <UserRound
                                                        class="mt-0.5 h-3.5 w-3.5 shrink-0"
                                                        aria-hidden="true"
                                                    />
                                                    <div class="min-w-0">
                                                        <p class="truncate font-medium">
                                                            {{ candidate.name }}
                                                        </p>
                                                        <p class="truncate text-xs text-muted-foreground">
                                                            {{ candidate.stage }}
                                                            <template v-if="candidate.status">
                                                                · {{ formatLabel(candidate.status) }}
                                                            </template>
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>

                                            <p
                                                v-else
                                                class="p-3 text-sm text-muted-foreground"
                                            >
                                                No candidates assigned.
                                            </p>
                                        </TooltipContent>
                                    </Tooltip>
                                </TableCell>

                                <TableCell>
                                    <Link
                                        :href="currentStageHref(position)"
                                        class="group inline-flex items-center gap-1.5 font-medium text-primary underline-offset-4 hover:underline"
                                    >
                                        <Workflow class="h-3.5 w-3.5" aria-hidden="true" />
                                        <span>
                                            {{ position.current_stage }}
                                            <span
                                                v-if="position.current_stage_count > 1"
                                                class="text-muted-foreground"
                                            >
                                                ({{ position.current_stage_count }})
                                            </span>
                                        </span>
                                        <ArrowRight
                                            class="h-3.5 w-3.5 transition-transform group-hover:translate-x-0.5"
                                            aria-hidden="true"
                                        />
                                    </Link>
                                </TableCell>

                                <TableCell>
                                    <Badge
                                        variant="outline"
                                        :class="['gap-1.5 font-medium', daysOpenClass(position.days_open)]"
                                    >
                                        <Clock3 class="h-3.5 w-3.5" aria-hidden="true" />
                                        {{ position.days_open }}
                                    </Badge>
                                </TableCell>

                                <TableCell>
                                    <StatusBadge
                                        :label="position.next_action"
                                        :tone="position.next_action_tone"
                                        :dot="false"
                                    />
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </TooltipProvider>
        </CardContent>
    </Card>
</template>
