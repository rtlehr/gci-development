<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import { BriefcaseBusiness, Users } from 'lucide-vue-next'
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

type StatusTone = 'success' | 'warning' | 'danger' | 'info' | 'neutral'

type AssignedPosition = {
    id: number
    position_code: string | null
    title: string | null
    status: string | null
    candidates_count: number
}

defineProps<{
    positions: AssignedPosition[]
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
    const normalizedStatus = String(status ?? '').toLowerCase()

    if (['filled', 'active', 'approved'].includes(normalizedStatus)) {
        return 'success'
    }

    if (['in process', 'pending', 'on hold'].includes(normalizedStatus)) {
        return 'warning'
    }

    if (['closed', 'cancelled', 'canceled'].includes(normalizedStatus)) {
        return 'danger'
    }

    if (normalizedStatus === 'open') {
        return 'info'
    }

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
</script>

<template>
    <Card>
        <CardHeader class="border-b">
            <div class="flex items-start gap-3">
                <div class="rounded-lg border bg-muted/40 p-2">
                    <BriefcaseBusiness class="h-5 w-5" aria-hidden="true" />
                </div>

                <div>
                    <CardTitle>My Assigned Positions</CardTitle>
                    <CardDescription class="mt-1">
                        Positions where you are assigned as the project manager.
                    </CardDescription>
                </div>
            </div>
        </CardHeader>

        <CardContent class="p-0">
            <div
                v-if="positions.length === 0"
                class="m-5 rounded-lg border border-dashed p-8 text-center"
            >
                <BriefcaseBusiness
                    class="mx-auto h-8 w-8 text-muted-foreground"
                    aria-hidden="true"
                />
                <p class="mt-3 text-sm font-medium">
                    No positions are currently assigned to you.
                </p>
                <p class="mt-1 text-sm text-muted-foreground">
                    Positions will appear here when you are selected as their project manager.
                </p>
            </div>

            <div v-else class="overflow-x-auto">
                <Table class="min-w-[700px]">
                    <TableHeader>
                        <TableRow>
                            <TableHead>Position Code</TableHead>
                            <TableHead>Position Title</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead class="text-right">
                                Candidates
                            </TableHead>
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
                                    class="font-medium text-primary underline-offset-4 hover:underline"
                                >
                                    {{ position.title || 'Untitled Position' }}
                                </Link>
                            </TableCell>

                            <TableCell>
                                <StatusBadge
                                    :label="formatLabel(position.status)"
                                    :tone="statusTone(position.status)"
                                />
                            </TableCell>

                            <TableCell class="text-right">
                                <Link
                                    :href="`/positions/${position.id}?section=candidates`"
                                    class="inline-flex rounded-full focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                                    :aria-label="`View ${position.candidates_count} candidates for ${position.position_code || position.title || 'position'}`"
                                >
                                    <Badge
                                        variant="outline"
                                        :class="['gap-1.5 font-medium transition hover:opacity-80', candidateBadgeClass(position.candidates_count)]"
                                    >
                                        <Users class="h-3.5 w-3.5" aria-hidden="true" />
                                        {{ position.candidates_count }}
                                    </Badge>
                                </Link>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
        </CardContent>
    </Card>
</template>
