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

                <Link v-if="can('view_admin')" :href="`/positions/${position.id}/edit`">
                    <Button>Edit Position</Button>
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
                    <CardTitle>Current Assignment</CardTitle>
                </CardHeader>
                <CardContent>
                    <div v-if="position.current_assignment?.person" class="space-y-3">
                        <DetailItem
                            label="Assigned Person"
                            :value="fullName(position.current_assignment.person)"
                        />
                        <DetailItem
                            label="Assignment Status"
                            :value="position.current_assignment.assignment_status"
                        />
                        <DetailItem
                            label="Assignment Type"
                            :value="position.current_assignment.assignment_type"
                        />
                        <DetailItem
                            label="Start Date"
                            :value="formatDate(position.current_assignment.start_date)"
                        />
                        <DetailItem
                            label="End Date"
                            :value="formatDate(position.current_assignment.end_date)"
                        />
                    </div>

                    <div v-else class="text-sm text-muted-foreground">
                        No current assignment found.
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
                                <TableHead>Status</TableHead>
                                <TableHead>Type</TableHead>
                                <TableHead>Start Date</TableHead>
                                <TableHead>End Date</TableHead>
                            </TableRow>
                        </TableHeader>

                        <TableBody>
                            <TableRow
                                v-for="assignment in position.assignments"
                                :key="assignment.id"
                            >
                                <TableCell>
                                    {{ assignment.person ? fullName(assignment.person) : '—' }}
                                </TableCell>
                                <TableCell>{{ assignment.assignment_status || '—' }}</TableCell>
                                <TableCell>{{ assignment.assignment_type || '—' }}</TableCell>
                                <TableCell>{{ formatDate(assignment.start_date) }}</TableCell>
                                <TableCell>{{ formatDate(assignment.end_date) }}</TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>

                <div v-else class="text-sm text-muted-foreground">
                    No assignment history found.
                </div>
            </CardContent>
        </Card>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import DetailItem from '@/components/DetailItem.vue'
import { Button } from '@/components/ui/button'
import { useAuth } from '@/composables/useAuth'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
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

    return `${first} ${last}`.trim() || person.name || '—'
}
</script>