<script setup lang="ts">
import { computed } from 'vue'
import BooleanField from '@/components/forms/BooleanField.vue'
import FormField from '@/components/forms/FormField.vue'
import FormSection from '@/components/forms/FormSection.vue'
import OrganizationSelect from '@/components/OrganizationSelect.vue'
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'

type GenericRecord = Record<string, any>
type OrganizationOption = { id: number; name: string; full_path: string; depth?: number }

const props = withDefaults(defineProps<{
    form: GenericRecord
    organizations?: OrganizationOption[]
    jobTitles?: GenericRecord[]
    projectManagers?: GenericRecord[]
    extended?: boolean
}>(), {
    organizations: () => [],
    jobTitles: () => [],
    projectManagers: () => [],
    extended: false,
})

const selectedJobTitle = computed(() => props.jobTitles.find((item) => Number(item.id) === Number(props.form.job_title_id)))
const generatedLaborCategory = computed(() => selectedJobTitle.value && props.form.level
    ? `${selectedJobTitle.value.name} - Level ${props.form.level}`
    : '')

const selectClass = (error?: string) => [
    'flex h-10 w-full rounded-md border bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2',
    error ? 'border-destructive' : 'border-input',
]
</script>

<template>
    <FormSection
        title="Core Position Information"
        description="Define the position identifier, status, job title, level, team, and project manager."
    >
        <div class="grid gap-5 md:grid-cols-2">
            <FormField label="Position Code" for-id="position_code" :error="form.errors.position_code" description="Use the approved position or requisition identifier.">
                <template #default="{ describedBy }">
                    <Input id="position_code" v-model="form.position_code" :aria-describedby="describedBy" :aria-invalid="Boolean(form.errors.position_code)" />
                </template>
            </FormField>

            <FormField label="Status" for-id="status" required :error="form.errors.status">
                <template #default="{ describedBy }">
                    <select id="status" v-model="form.status" :class="selectClass(form.errors.status)" :aria-describedby="describedBy" :aria-invalid="Boolean(form.errors.status)">
                        <option value="Open">Open</option>
                        <option value="In Process">In Process</option>
                        <option value="Closed">Closed</option>
                    </select>
                </template>
            </FormField>

            <FormField label="Job Title" for-id="job_title_id" required :error="form.errors.job_title_id">
                <template #default="{ describedBy }">
                    <select id="job_title_id" v-model="form.job_title_id" :class="selectClass(form.errors.job_title_id)" :aria-describedby="describedBy" :aria-invalid="Boolean(form.errors.job_title_id)">
                        <option :value="null">Select Job Title</option>
                        <option v-for="jobTitle in jobTitles" :key="jobTitle.id" :value="jobTitle.id">{{ jobTitle.name }}</option>
                    </select>
                </template>
            </FormField>

            <FormField label="Level" for-id="level" :error="form.errors.level">
                <template #default="{ describedBy }">
                    <select id="level" v-model="form.level" :class="selectClass(form.errors.level)" :aria-describedby="describedBy" :aria-invalid="Boolean(form.errors.level)">
                        <option value="">Select Level</option>
                        <option v-for="level in 5" :key="level" :value="level">Level {{ level }}</option>
                    </select>
                </template>
            </FormField>

            <FormField label="Team Name" for-id="team_name" :error="form.errors.team_name">
                <template #default="{ describedBy }">
                    <Input id="team_name" v-model="form.team_name" :aria-describedby="describedBy" :aria-invalid="Boolean(form.errors.team_name)" />
                </template>
            </FormField>

            <FormField label="Project Manager" for-id="project_manager_user_id" :error="form.errors.project_manager_user_id">
                <template #default="{ describedBy }">
                    <select id="project_manager_user_id" v-model="form.project_manager_user_id" :class="selectClass(form.errors.project_manager_user_id)" :aria-describedby="describedBy" :aria-invalid="Boolean(form.errors.project_manager_user_id)">
                        <option :value="null">Select Project Manager</option>
                        <option v-for="manager in projectManagers" :key="manager.id" :value="manager.id">
                            {{ manager.name }}<template v-if="manager.email"> — {{ manager.email }}</template>
                        </option>
                    </select>
                </template>
            </FormField>

            <FormField class="md:col-span-2" label="Labor Category Preview" for-id="labor_category_preview" description="Generated automatically from the selected job title and level.">
                <Input id="labor_category_preview" :model-value="generatedLaborCategory || 'Select Job Title and Level'" disabled class="bg-muted" />
            </FormField>
        </div>
    </FormSection>

    <FormSection
        title="Requirements and Qualifications"
        description="Document the certifications, training, and experience expected for this position."
    >
        <FormField label="Certifications Required" for-id="certifications_required" :error="form.errors.certifications_required">
            <template #default="{ describedBy }"><Textarea id="certifications_required" v-model="form.certifications_required" rows="4" :aria-describedby="describedBy" /></template>
        </FormField>
        <FormField label="Training Required" for-id="training_required" :error="form.errors.training_required">
            <template #default="{ describedBy }"><Textarea id="training_required" v-model="form.training_required" rows="4" :aria-describedby="describedBy" /></template>
        </FormField>
        <FormField label="Experience" for-id="experience" :error="form.errors.experience">
            <template #default="{ describedBy }"><Textarea id="experience" v-model="form.experience" rows="4" :aria-describedby="describedBy" /></template>
        </FormField>
    </FormSection>

    <FormSection title="Flags and Risk" description="Identify operational requirements that need additional attention.">
        <div class="grid gap-4 md:grid-cols-3">
            <BooleanField id="is_essential" v-model="form.is_essential" label="Essential" description="This position is required for essential operations." />
            <BooleanField id="travel_required" v-model="form.travel_required" label="Travel Required" description="The assigned person may be required to travel." />
            <BooleanField id="high_risk_role" v-model="form.high_risk_role" label="High Risk Role" description="This position carries elevated staffing or mission risk." />
        </div>
    </FormSection>

    <FormSection title="Location and Mission" description="Describe where the position works and the mission it supports.">
        <div class="grid gap-5 md:grid-cols-2">
            <FormField label="Location" for-id="location" :error="form.errors.location"><Input id="location" v-model="form.location" /></FormField>
            <FormField label="Building" for-id="building" :error="form.errors.building"><Input id="building" v-model="form.building" /></FormField>
            <FormField label="Component" for-id="component" :error="form.errors.component" class="md:col-span-2"><Input id="component" v-model="form.component" /></FormField>
            <FormField label="Mission Description" for-id="mission_description" :error="form.errors.mission_description" class="md:col-span-2"><Textarea id="mission_description" v-model="form.mission_description" rows="5" /></FormField>
        </div>
    </FormSection>

    <FormSection title="Organization Information" description="Associate the position with its owning, sponsoring, and funding organizations.">
        <div class="grid gap-5 lg:grid-cols-3">
            <OrganizationSelect v-model="form.position_organization_id" :organizations="organizations" label="Position Org" id="position_organization_id" :error="form.errors.position_organization_id" />
            <OrganizationSelect v-model="form.sponsoring_organization_id" :organizations="organizations" label="Sponsoring Org" id="sponsoring_organization_id" :error="form.errors.sponsoring_organization_id" />
            <OrganizationSelect v-model="form.funding_organization_id" :organizations="organizations" label="Funding Org" id="funding_organization_id" :error="form.errors.funding_organization_id" />
        </div>
    </FormSection>

    <template v-if="extended">
        <FormSection title="Funding Information" description="Capture any funding details that users need when reviewing this position.">
            <FormField label="Funding Information" for-id="funding_info" :error="form.errors.funding_info"><Textarea id="funding_info" v-model="form.funding_info" rows="4" /></FormField>
        </FormSection>

        <FormSection title="Closure Workflow" description="Track requests and dates associated with closing this position.">
            <BooleanField id="request_to_close" v-model="form.request_to_close" label="Request to Close" description="Indicates that the position is being considered for closure." />
            <div class="grid gap-5 md:grid-cols-2">
                <FormField label="Scheduled to Close" for-id="scheduled_to_close" :error="form.errors.scheduled_to_close"><Input id="scheduled_to_close" v-model="form.scheduled_to_close" type="date" /></FormField>
                <FormField label="Close Date" for-id="close_date" :error="form.errors.close_date"><Input id="close_date" v-model="form.close_date" type="date" :disabled="form.status !== 'Closed'" /></FormField>
                <FormField label="Close Reason" for-id="close_reason" :error="form.errors.close_reason" class="md:col-span-2"><Textarea id="close_reason" v-model="form.close_reason" rows="4" :disabled="form.status !== 'Closed'" /></FormField>
            </div>
        </FormSection>

        <FormSection title="Project and Customer Information" description="Record the teams and customer contacts connected to this position.">
            <div class="grid gap-5 md:grid-cols-2">
                <FormField label="Customer Lead Name" for-id="customer_lead_name" :error="form.errors.customer_lead_name"><Input id="customer_lead_name" v-model="form.customer_lead_name" /></FormField>
                <FormField label="Customer Created Date" for-id="customer_created_at" :error="form.errors.customer_created_at"><Input id="customer_created_at" v-model="form.customer_created_at" type="date" /></FormField>
                <FormField label="Notes" for-id="notes" :error="form.errors.notes" class="md:col-span-2"><Textarea id="notes" v-model="form.notes" rows="5" /></FormField>
            </div>
        </FormSection>
    </template>
</template>
