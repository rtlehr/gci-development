<template>
    <div class="p-6 max-w-4xl space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold">Create Position</h1>
                <p class="text-sm text-muted-foreground mt-1">
                    Add a new position record.
                </p>
            </div>

            <Link href="/positions">
                <Button variant="outline">Back to List</Button>
            </Link>
        </div>

        <div class="border rounded-xl p-6 bg-background">
            <form @submit.prevent="submit" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <Label for="position_code">Position Code</Label>
                        <Input
                            id="position_code"
                            v-model="form.position_code"
                            :class="form.errors.position_code ? 'border-red-500' : ''"
                        />
                        <p v-if="form.errors.position_code" class="text-sm text-red-500">
                            {{ form.errors.position_code }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="status">
                            Status <span class="text-red-500">*</span>
                        </Label>
                        <select
                            id="status"
                            v-model="form.status"
                            :class="[
                                'flex h-10 w-full rounded-md border bg-background px-3 py-2 text-sm',
                                form.errors.status ? 'border-red-500' : 'border-input'
                            ]"
                        >
                            <option value="">Select a status</option>
                            <option value="Open">Open</option>
                            <option value="In Process">In Process</option>
                            <option value="Closed">Closed</option>
                        </select>
                        <p v-if="form.errors.status" class="text-sm text-red-500">
                            {{ form.errors.status }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="job_title">
                            Job Title <span class="text-red-500">*</span>
                        </Label>
                        <Input
                            id="job_title"
                            v-model="form.job_title"
                            :class="form.errors.job_title ? 'border-red-500' : ''"
                        />
                        <p v-if="form.errors.job_title" class="text-sm text-red-500">
                            {{ form.errors.job_title }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="labor_category">Labor Category</Label>
                        <Input
                            id="labor_category"
                            v-model="form.labor_category"
                            :class="form.errors.labor_category ? 'border-red-500' : ''"
                        />
                        <p v-if="form.errors.labor_category" class="text-sm text-red-500">
                            {{ form.errors.labor_category }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="level">Level</Label>
                        <Input
                            id="level"
                            type="number"
                            v-model="form.level"
                            :class="form.errors.level ? 'border-red-500' : ''"
                        />
                        <p v-if="form.errors.level" class="text-sm text-red-500">
                            {{ form.errors.level }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="project_team_name">Project Team Name</Label>
                        <Input
                            id="project_team_name"
                            v-model="form.project_team_name"
                            :class="form.errors.project_team_name ? 'border-red-500' : ''"
                        />
                        <p v-if="form.errors.project_team_name" class="text-sm text-red-500">
                            {{ form.errors.project_team_name }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <OrganizationSelect
                            v-model="form.organization_id"
                            :organizations="props.organizations"
                            label="Organization"
                            id="organization_id"
                            :error="form.errors.organization_id"
                        />
                    </div>

                    <div class="space-y-2">
                        <Label for="customer_lead_name">Customer Lead Name</Label>
                        <Input
                            id="customer_lead_name"
                            v-model="form.customer_lead_name"
                            :class="form.errors.customer_lead_name ? 'border-red-500' : ''"
                        />
                        <p v-if="form.errors.customer_lead_name" class="text-sm text-red-500">
                            {{ form.errors.customer_lead_name }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="customer_created_at">Customer Created At</Label>
                        <Input
                            id="customer_created_at"
                            type="date"
                            v-model="form.customer_created_at"
                            :class="form.errors.customer_created_at ? 'border-red-500' : ''"
                        />
                        <p v-if="form.errors.customer_created_at" class="text-sm text-red-500">
                            {{ form.errors.customer_created_at }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="closed_at">
                            Closed At
                            <span v-if="form.status === 'Closed'" class="text-red-500">*</span>
                        </Label>
                        <Input
                            id="closed_at"
                            type="date"
                            v-model="form.closed_at"
                            :disabled="form.status !== 'Closed'"
                            :class="form.errors.closed_at ? 'border-red-500' : ''"
                        />
                        <p v-if="form.errors.closed_at" class="text-sm text-red-500">
                            {{ form.errors.closed_at }}
                        </p>
                    </div>
                </div>

                <div class="space-y-2">
                    <Label for="closed_reason">
                        Closed Reason
                        <span v-if="form.status === 'Closed'" class="text-red-500">*</span>
                    </Label>
                    <Input
                        id="closed_reason"
                        v-model="form.closed_reason"
                        :disabled="form.status !== 'Closed'"
                        :class="form.errors.closed_reason ? 'border-red-500' : ''"
                    />
                    <p v-if="form.errors.closed_reason" class="text-sm text-red-500">
                        {{ form.errors.closed_reason }}
                    </p>
                </div>

                <div class="space-y-2">
                    <Label for="notes">Notes</Label>
                    <Textarea
                        id="notes"
                        v-model="form.notes"
                        rows="5"
                        :class="form.errors.notes ? 'border-red-500' : ''"
                    />
                    <p v-if="form.errors.notes" class="text-sm text-red-500">
                        {{ form.errors.notes }}
                    </p>
                </div>

                <div class="flex gap-3">
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Saving...' : 'Create Position' }}
                    </Button>

                    <Link href="/positions">
                        <Button type="button" variant="outline">Cancel</Button>
                    </Link>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { watch } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import OrganizationSelect from '@/components/OrganizationSelect.vue'

function getTodayDate() {
    const d = new Date()
    const offset = d.getTimezoneOffset()
    const local = new Date(d.getTime() - offset * 60 * 1000)
    return local.toISOString().split('T')[0]
}

const props = defineProps({
    organizations: {
        type: Array,
        default: () => [],
    },
})

const form = useForm({
    position_code: '',
    status: 'Open',
    labor_category: '',
    job_title: '',
    level: '',
    project_team_name: '',
    organization_id: null,
    customer_lead_name: '',
    customer_created_at: getTodayDate(),
    closed_at: '',
    closed_reason: '',
    notes: '',
})

watch(
    () => form.status,
    (newStatus) => {
        if (newStatus !== 'Closed') {
            form.closed_at = ''
            form.closed_reason = ''
        }
    }
)

function submit() {
    form.clearErrors()

    let hasError = false

    if (!form.job_title || form.job_title.trim() === '') {
        form.setError('job_title', 'Job title is required.')
        hasError = true
    }

    if (!form.status || form.status.trim() === '') {
        form.setError('status', 'Status is required.')
        hasError = true
    }

    if (form.status === 'Closed') {
        if (!form.closed_at) {
            form.setError('closed_at', 'Closed date is required when status is Closed.')
            hasError = true
        }

        if (!form.closed_reason || form.closed_reason.trim() === '') {
            form.setError('closed_reason', 'Closed reason is required when status is Closed.')
            hasError = true
        }
    }

    if (hasError) return

    form.post('/positions')
}
</script>