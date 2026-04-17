<template>
    <div class="p-6 space-y-6">
        <div class="rounded-2xl border bg-background p-6 shadow-sm">
            <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                <div class="space-y-2">
                    <div class="flex flex-wrap items-center gap-2">
                        <h1 class="text-2xl font-semibold">
                            Candidate Details
                        </h1>

                        <span
                            class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-medium"
                            :class="statusBadgeClass(candidate.status)"
                        >
                            {{ formatStatus(candidate.status) }}
                        </span>
                    </div>

                    <div class="text-sm text-muted-foreground space-y-1">
                        <div>
                            <span class="font-medium text-foreground">Candidate Code:</span>
                            {{ candidate.candidate_code || '—' }}
                        </div>

                        <div>
                            <span class="font-medium text-foreground">ID:</span>
                            {{ candidate.id }}
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2">
                    <Link href="/candidates">
                        <Button variant="outline">Back to Candidates</Button>
                    </Link>

                    <Link
                        v-if="can('edit_candidates') || can('view_admin')"
                        :href="`/candidates/${candidate.id}/edit`"
                    >
                        <Button>Edit Candidate</Button>
                    </Link>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
            <div class="xl:col-span-2 rounded-2xl border bg-background p-6 shadow-sm space-y-4">
                <div>
                    <h2 class="text-lg font-semibold">Candidate Information</h2>
                    <p class="text-sm text-muted-foreground">
                        Core candidate record details.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <DetailItem label="Candidate Code" :value="candidate.candidate_code" />
                    <DetailItem label="Status" :value="formatStatus(candidate.status)" />
                    <DetailItem label="Candidate FBR" :value="formatMoney(candidate.candidate_fbr)" />
                    <DetailItem label="Submitted At" :value="formatDateTime(candidate.submitted_at)" />
                    <DetailItem label="Submitted By" :value="candidate.submitted_by?.full_name" />
                    <DetailItem label="Scheduled Start Date" :value="formatDate(candidate.scheduled_start_date)" />
                </div>
            </div>

            <div class="rounded-2xl border bg-background p-6 shadow-sm space-y-4">
                <div>
                    <h2 class="text-lg font-semibold">Quick Summary</h2>
                    <p class="text-sm text-muted-foreground">
                        Key related records.
                    </p>
                </div>

                <div class="space-y-4">
                    <div class="rounded-xl border p-4">
                        <div class="text-sm font-medium text-muted-foreground mb-1">Person</div>
                        <div class="font-medium">
                            {{ candidate.person?.full_name || '—' }}
                        </div>
                        <div class="text-sm text-muted-foreground">
                            {{ candidate.person?.person_code || '—' }}
                        </div>
                    </div>

                    <div class="rounded-xl border p-4">
                        <div class="text-sm font-medium text-muted-foreground mb-1">Position</div>
                        <div class="font-medium">
                            {{ candidate.position?.job_title || '—' }}
                        </div>
                        <div class="text-sm text-muted-foreground">
                            {{ candidate.position?.position_code || '—' }}
                        </div>
                    </div>

                    <div class="rounded-xl border p-4">
                        <div class="text-sm font-medium text-muted-foreground mb-1">
                            Workflow Steps
                        </div>
                        <div class="font-medium">
                            {{ candidate.step_events?.length ?? 0 }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
            <div class="rounded-2xl border bg-background p-6 shadow-sm space-y-4">
                <div>
                    <h2 class="text-lg font-semibold">Person</h2>
                    <p class="text-sm text-muted-foreground">
                        Candidate’s linked person record.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <DetailItem label="Full Name" :value="candidate.person?.full_name" />
                    <DetailItem label="Person Code" :value="candidate.person?.person_code" />
                    <DetailItem label="Email" :value="candidate.person?.email" />
                </div>

                <div v-if="candidate.person?.id" class="pt-2">
                    <Link :href="`/people/${candidate.person.id}`">
                        <Button variant="outline" size="sm">View Person</Button>
                    </Link>
                </div>
            </div>

            <div class="rounded-2xl border bg-background p-6 shadow-sm space-y-4">
                <div>
                    <h2 class="text-lg font-semibold">Position</h2>
                    <p class="text-sm text-muted-foreground">
                        Candidate’s linked position record.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <DetailItem label="Job Title" :value="candidate.position?.job_title" />
                    <DetailItem label="Position Code" :value="candidate.position?.position_code" />
                    <DetailItem label="Position Status" :value="candidate.position?.status" />
                </div>

                <div v-if="candidate.position?.id" class="pt-2">
                    <Link :href="`/positions/${candidate.position.id}`">
                        <Button variant="outline" size="sm">View Position</Button>
                    </Link>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border bg-background p-6 shadow-sm space-y-6">
            <div>
                <h2 class="text-lg font-semibold">Workflow Steps</h2>
                <p class="text-sm text-muted-foreground">
                    Full workflow with completed and not-yet-started steps.
                </p>
            </div>

            <div v-if="!candidate.step_events?.length" class="rounded-xl border p-6 text-center text-muted-foreground">
                No workflow steps available.
            </div>

            <div v-else class="space-y-4">
                <div
                    v-for="event in sortedStepEvents"
                    :key="event.workflow_step_id"
                    class="rounded-xl border p-5 space-y-4"
                >
                    <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="text-base font-semibold">
                                    {{ event.workflow_step?.name || 'Workflow Step' }}
                                </h3>

                                <span
                                    class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-medium"
                                    :class="event.has_event ? 'border-green-300 bg-green-50 text-green-700' : 'border-slate-300 bg-slate-50 text-slate-600'"
                                >
                                    {{ event.has_event ? (event.status_code ? formatStatus(event.status_code) : 'Started') : 'Not Started' }}
                                </span>
                            </div>

                            <div class="mt-1 text-sm text-muted-foreground">
                                Code: {{ event.workflow_step?.code || '—' }}
                            </div>
                        </div>

                        <div class="text-sm text-muted-foreground">
                            Step {{ event.workflow_step?.step_order ?? '—' }}
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                        <DetailItem
                            v-if="event.workflow_step?.allows_requested_at"
                            label="Requested"
                            :value="formatDateTime(event.requested_at)"
                        />
                        <DetailItem
                            v-if="event.workflow_step?.allows_scheduled_at"
                            label="Scheduled"
                            :value="formatDateTime(event.scheduled_at)"
                        />
                        <DetailItem
                            v-if="event.workflow_step?.allows_completed_at"
                            label="Completed"
                            :value="formatDateTime(event.completed_at)"
                        />
                        <DetailItem
                            label="Performed By"
                            :value="event.performed_by?.full_name"
                        />
                    </div>

                    <div
                        v-if="event.workflow_step?.allows_notes || event.workflow_step?.allows_comments"
                        class="grid grid-cols-1 xl:grid-cols-2 gap-4"
                    >
                        <div v-if="event.workflow_step?.allows_notes" class="rounded-xl border p-4">
                            <div class="mb-2 text-sm font-medium text-muted-foreground">
                                Notes
                            </div>
                            <div class="whitespace-pre-wrap text-sm">
                                {{ hasText(event.notes) ? event.notes : '—' }}
                            </div>
                        </div>

                        <div v-if="event.workflow_step?.allows_comments" class="rounded-xl border p-4">
                            <div class="mb-2 text-sm font-medium text-muted-foreground">
                                Comments
                            </div>
                            <div class="whitespace-pre-wrap text-sm">
                                {{ hasText(event.comments) ? event.comments : '—' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, h } from 'vue'
import { Link } from '@inertiajs/vue3'
import { useAuth } from '@/composables/useAuth'
import { Button } from '@/components/ui/button'

const { can } = useAuth()

const props = defineProps({
    candidate: {
        type: Object,
        required: true,
    },
})

const sortedStepEvents = computed(() => {
    return [...(props.candidate.step_events ?? [])].sort((a, b) => {
        const aOrder = a.workflow_step?.step_order ?? 9999
        const bOrder = b.workflow_step?.step_order ?? 9999
        return aOrder - bOrder
    })
})

function formatStatus(value) {
    if (!value) return '—'

    return String(value)
        .replaceAll('_', ' ')
        .replace(/\b\w/g, (char) => char.toUpperCase())
}

function formatDate(value) {
    if (!value) return '—'

    const date = new Date(value)
    if (Number.isNaN(date.getTime())) return value

    return date.toLocaleDateString()
}

function formatDateTime(value) {
    if (!value) return '—'

    const date = new Date(value)
    if (Number.isNaN(date.getTime())) return value

    return date.toLocaleString()
}

function formatMoney(value) {
    if (value === null || value === undefined || value === '') return '—'

    const number = Number(value)
    if (Number.isNaN(number)) return value

    return new Intl.NumberFormat(undefined, {
        style: 'currency',
        currency: 'USD',
    }).format(number)
}

function statusBadgeClass(status) {
    switch (status) {
        case 'submitted':
            return 'border-slate-300 bg-slate-50 text-slate-700'
        case 'selected':
            return 'border-blue-300 bg-blue-50 text-blue-700'
        case 'approved':
            return 'border-green-300 bg-green-50 text-green-700'
        case 'assigned':
            return 'border-purple-300 bg-purple-50 text-purple-700'
        default:
            return 'border-border bg-muted text-foreground'
    }
}

function hasText(value) {
    return String(value ?? '').trim().length > 0
}

const DetailItem = {
    props: {
        label: {
            type: String,
            required: true,
        },
        value: {
            type: [String, Number, null],
            default: '—',
        },
    },
    render() {
        return h('div', { class: 'space-y-1' }, [
            h('div', { class: 'text-sm font-medium text-muted-foreground' }, this.label),
            h('div', { class: 'text-sm' }, this.value || '—'),
        ])
    },
}
</script>