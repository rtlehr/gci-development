<template>
    <div class="p-6 max-w-3xl">
        <h1 class="text-2xl font-semibold mb-6">Edit Position</h1>

        <div class="border rounded-xl p-6 bg-background">
            <form @submit.prevent="submit" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <Label for="position_code">Position Code</Label>
                        <Input id="position_code" v-model="form.position_code" />
                        <p v-if="form.errors.position_code" class="text-sm text-red-500">
                            {{ form.errors.position_code }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="status">Status</Label>
                        <select
                            id="status"
                            v-model="form.status"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background"
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
                        <Label for="job_title">Job Title</Label>
                        <Input id="job_title" v-model="form.job_title" />
                        <p v-if="form.errors.job_title" class="text-sm text-red-500">
                            {{ form.errors.job_title }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="labor_category">Labor Category</Label>
                        <Input id="labor_category" v-model="form.labor_category" />
                        <p v-if="form.errors.labor_category" class="text-sm text-red-500">
                            {{ form.errors.labor_category }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="level">Level</Label>
                        <Input id="level" type="number" v-model="form.level" />
                        <p v-if="form.errors.level" class="text-sm text-red-500">
                            {{ form.errors.level }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="project_team_name">Project Team Name</Label>
                        <Input id="project_team_name" v-model="form.project_team_name" />
                        <p v-if="form.errors.project_team_name" class="text-sm text-red-500">
                            {{ form.errors.project_team_name }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="organization_name">Organization Name</Label>
                        <Input id="organization_name" v-model="form.organization_name" />
                        <p v-if="form.errors.organization_name" class="text-sm text-red-500">
                            {{ form.errors.organization_name }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="customer_lead_name">Customer Lead Name</Label>
                        <Input id="customer_lead_name" v-model="form.customer_lead_name" />
                        <p v-if="form.errors.customer_lead_name" class="text-sm text-red-500">
                            {{ form.errors.customer_lead_name }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="customer_created_at">Customer Created At</Label>
                        <Input id="customer_created_at" type="date" v-model="form.customer_created_at" />
                        <p v-if="form.errors.customer_created_at" class="text-sm text-red-500">
                            {{ form.errors.customer_created_at }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="closed_at">Closed At</Label>
                        <Input id="closed_at" type="date" v-model="form.closed_at" />
                        <p v-if="form.errors.closed_at" class="text-sm text-red-500">
                            {{ form.errors.closed_at }}
                        </p>
                    </div>
                </div>

                <div class="space-y-2">
                    <Label for="closed_reason">Closed Reason</Label>
                    <Input id="closed_reason" v-model="form.closed_reason" />
                    <p v-if="form.errors.closed_reason" class="text-sm text-red-500">
                        {{ form.errors.closed_reason }}
                    </p>
                </div>

                <div class="space-y-2">
                    <Label for="notes">Notes</Label>
                    <Textarea id="notes" v-model="form.notes" rows="5" />
                    <p v-if="form.errors.notes" class="text-sm text-red-500">
                        {{ form.errors.notes }}
                    </p>
                </div>

                <div class="flex gap-3">
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Saving...' : 'Save Changes' }}
                    </Button>

                    <Link :href="`/positions`">
                        <Button type="button" variant="outline">Cancel</Button>
                    </Link>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'

const props = defineProps({
    position: {
        type: Object,
        required: true,
    },
})

const form = useForm({
    position_code: props.position.position_code ?? '',
    status: props.position.status ?? '',
    labor_category: props.position.labor_category ?? '',
    job_title: props.position.job_title ?? '',
    level: props.position.level ?? null,
    project_team_name: props.position.project_team_name ?? '',
    organization_name: props.position.organization_name ?? '',
    customer_lead_name: props.position.customer_lead_name ?? '',
    customer_created_at: formatDateForInput(props.position.customer_created_at),
    closed_at: formatDateForInput(props.position.closed_at),
    closed_reason: props.position.closed_reason ?? '',
    notes: props.position.notes ?? '',
})

function formatDateForInput(value) {
    if (!value) return ''
    return String(value).slice(0, 10)
}

function submit() {
    form.put(`/positions/${props.position.id}`)
}
</script>