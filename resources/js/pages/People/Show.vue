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

                <Link :href="`/people/${person.id}/edit`">
                    <Button>Edit Person</Button>
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
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import DetailItem from '@/components/DetailItem.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table'

const props = defineProps({
    person: {
        type: Object,
        required: true,
    },
})

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
</script>