<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted } from 'vue'
import { useForm } from '@inertiajs/vue3'
import DisplayField from '@/components/forms/DisplayField.vue'
import FormActions from '@/components/forms/FormActions.vue'
import InfoPanel from '@/components/forms/InfoPanel.vue'
import PageContainer from '@/components/layout/PageContainer.vue'
import PageHeader from '@/components/layout/PageHeader.vue'
import PositionFormFields from '@/components/positions/PositionFormFields.vue'

type GenericRecord = Record<string, any>
type OrganizationOption = { id: number; name: string; full_path: string; depth?: number }

const props = withDefaults(defineProps<{
    organizations?: OrganizationOption[]
    jobTitles?: GenericRecord[]
}>(), {
    organizations: () => [],
    jobTitles: () => [],
})

const form = useForm({
    position_code: '',
    status: 'Open',
    job_title_id: null as number | null,
    experience_level: '',
    certifications_required: '',
    training_required: '',
    experience: '',
    is_essential: false,
    travel_required: false,
    high_risk_role: false,
    location: '',
    building: '',
    mission_description: '',
    component: '',
    position_organization_id: null as number | null,
    sponsoring_organization_id: null as number | null,
    funding_organization_id: null as number | null,
})

const selectedJobTitle = computed(() => props.jobTitles.find((item) => Number(item.id) === Number(form.job_title_id)))
const selectedOrganization = computed(() => props.organizations.find((item) => Number(item.id) === Number(form.position_organization_id)))
const laborCategory = computed(() => selectedJobTitle.value && form.experience_level
    ? `${selectedJobTitle.value.name} - ${form.experience_level}`
    : 'Not selected')

function focusFirstError(): void {
    requestAnimationFrame(() => {
        const firstInvalid = document.querySelector<HTMLElement>('[aria-invalid="true"], .border-destructive')
        firstInvalid?.focus()
    })
}

function submit(): void {
    form.post('/positions', { onError: focusFirstError })
}

function handleBeforeUnload(event: BeforeUnloadEvent): void {
    if (!form.isDirty || form.processing) return
    event.preventDefault()
    event.returnValue = ''
}

onMounted(() => window.addEventListener('beforeunload', handleBeforeUnload))
onBeforeUnmount(() => window.removeEventListener('beforeunload', handleBeforeUnload))
</script>

<template>
    <PageContainer>
        <PageHeader
            title="Create Position"
            description="Add a new staffing position and define its requirements, organizations, and operational flags."
            eyebrow="Positions"
            back-href="/positions"
            back-label="Back to Positions"
        />

        <form class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_20rem]" @submit.prevent="submit">
            <div class="grid min-w-0 gap-6">
                <PositionFormFields :form="form" :organizations="organizations" :job-titles="jobTitles" />

                <FormActions
                    cancel-href="/positions"
                    submit-label="Create Position"
                    processing-label="Creating Position…"
                    :processing="form.processing"
                    :dirty="form.isDirty"
                    sticky
                />
            </div>

            <aside class="min-w-0">
                <InfoPanel title="Position Summary" description="This preview updates as you complete the form.">
                    <DisplayField label="Position Code" :value="form.position_code || 'Not entered'" :muted="!form.position_code" />
                    <DisplayField label="Status" :value="form.status" />
                    <DisplayField label="Labor Category" :value="laborCategory" :muted="laborCategory === 'Not selected'" />
                    <DisplayField label="Organization" :value="selectedOrganization?.full_path || selectedOrganization?.name || 'Not selected'" :muted="!selectedOrganization" />
                    <DisplayField label="Location" :value="form.location || 'Not entered'" :muted="!form.location" />
                    <div class="rounded-lg border bg-muted/30 p-4 text-xs leading-5 text-muted-foreground">
                        Fields marked with an asterisk are required. Review the summary before creating the position.
                    </div>
                </InfoPanel>
            </aside>
        </form>
    </PageContainer>
</template>
