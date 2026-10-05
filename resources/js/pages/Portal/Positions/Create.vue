<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import {
    BadgeCheck,
    Building2,
    BriefcaseBusiness,
    MapPinned,
    ListPlus,
} from 'lucide-vue-next'
import CustomFieldsPanel from '@/components/custom-fields/CustomFieldsPanel.vue'
import PortalSectionNav from '@/components/portal/PortalSectionNav.vue'
import PositionFormFields from '@/components/positions/PositionFormFields.vue'
import { Button } from '@/components/ui/button'

type GenericRecord = Record<string, any>
type OrganizationOption = { id: number; name: string; full_path: string; depth?: number }

type CreateSection = 'details' | 'qualifications' | 'mission' | 'organization' | 'other'

const props = withDefaults(defineProps<{
    organizations?: OrganizationOption[]
    jobTitles?: GenericRecord[]
    projectManagers?: GenericRecord[]
    customFields?: GenericRecord[]
}>(), {
    organizations: () => [],
    jobTitles: () => [],
    projectManagers: () => [],
    customFields: () => [],
})

const activeSection = ref<CreateSection>('details')
const validationMessage = ref('')

const form = useForm({
    position_code: '',
    status: 'Open',
    job_title_id: null as number | null,
    level: '' as number | '',
    team_name: '',
    project_manager_user_id: null as number | null,
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
    custom_fields: {} as Record<string, any>,
})

const detailsError = computed(() => [
    'position_code',
    'status',
    'job_title_id',
    'level',
    'team_name',
    'project_manager_user_id',
].some((key) => Boolean(form.errors[key])))

const qualificationsError = computed(() => Object.keys(form.errors).some((key) =>
    ['certifications_required', 'training_required', 'experience'].includes(key)
))

const missionError = computed(() => Object.keys(form.errors).some((key) =>
    ['is_essential', 'travel_required', 'high_risk_role', 'location', 'building', 'mission_description', 'component'].includes(key)
))

const organizationError = computed(() => Object.keys(form.errors).some((key) =>
    ['position_organization_id', 'sponsoring_organization_id', 'funding_organization_id'].includes(key)
))

const otherError = computed(() => Object.keys(form.errors).some((key) => key.startsWith('custom_fields')))

const sections = computed(() => [
    {
        id: 'details',
        title: 'Position Details',
        description: 'Identifier, status, title, level, and manager.',
        icon: BriefcaseBusiness,
        complete: Boolean(form.position_code?.trim() && form.status && form.job_title_id) && !detailsError.value,
        error: detailsError.value,
    },
    {
        id: 'qualifications',
        title: 'Qualifications',
        description: 'Certifications, training, and experience.',
        icon: BadgeCheck,
        complete: Boolean(form.certifications_required || form.training_required || form.experience) && !qualificationsError.value,
        error: qualificationsError.value,
    },
    {
        id: 'mission',
        title: 'Mission & Location',
        description: 'Operational flags, workplace, and mission.',
        icon: MapPinned,
        complete: Boolean(form.location || form.building || form.mission_description) && !missionError.value,
        error: missionError.value,
    },
    {
        id: 'organization',
        title: 'Organizations',
        description: 'Owning, sponsoring, and funding organizations.',
        icon: Building2,
        complete: Boolean(form.position_organization_id || form.sponsoring_organization_id || form.funding_organization_id) && !organizationError.value,
        error: organizationError.value,
    },
    {
        id: 'other',
        title: 'Other Information',
        description: 'Installation-specific fields.',
        icon: ListPlus,
        complete: Object.values(form.custom_fields).some((value) => Array.isArray(value) ? value.length > 0 : Boolean(value)) && !otherError.value,
        error: otherError.value,
    },
])

function setActiveSection(value: string): void {
    activeSection.value = value as CreateSection
}

function firstSectionWithError(): CreateSection | null {
    if (detailsError.value) return 'details'
    if (qualificationsError.value) return 'qualifications'
    if (missionError.value) return 'mission'
    if (organizationError.value) return 'organization'
    if (otherError.value) return 'other'
    return null
}

async function showFirstErrorSection(): Promise<void> {
    const section = firstSectionWithError()
    if (section) activeSection.value = section

    validationMessage.value = 'The section marked with a red warning icon needs attention.'

    await nextTick()
    requestAnimationFrame(() => {
        const firstInvalid = document.querySelector<HTMLElement>('[aria-invalid="true"], .border-destructive')
        firstInvalid?.focus()
    })
}

function validate(): boolean {
    form.clearErrors()
    validationMessage.value = ''
    let hasError = false

    if (!form.position_code?.trim()) {
        form.setError('position_code', 'Position Code is required.')
        hasError = true
    }

    if (!form.status) {
        form.setError('status', 'Status is required.')
        hasError = true
    }

    if (!form.job_title_id) {
        form.setError('job_title_id', 'Job Title is required.')
        hasError = true
    }

    props.customFields
        .filter((field) => Boolean(field.is_required))
        .forEach((field) => {
            const value = form.custom_fields[String(field.id)] ?? form.custom_fields[field.id]
            const missing = Array.isArray(value)
                ? value.length === 0
                : value === undefined || value === null || String(value).trim() === ''

            if (missing) {
                form.setError(`custom_fields.${field.id}`, `${field.name} is required.`)
                hasError = true
            }
        })

    if (hasError) void showFirstErrorSection()

    return !hasError
}

function submit(): void {
    if (!validate()) return

    form.post('/portal/positions', {
        onError: () => void showFirstErrorSection(),
    })
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
    <div class="mx-auto w-full max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold">Create Position</h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    Add a new staffing position and define its requirements, organizations, and operational details.
                </p>
            </div>
            <Button as-child variant="outline"><Link href="/portal/positions">Back to List</Link></Button>
        </div>

        <form @submit.prevent="submit">
            <div class="grid gap-6 lg:grid-cols-[270px_minmax(0,1fr)]">
                <PortalSectionNav
                    title="Position sections"
                    aria-label="Position sections"
                    :sections="sections"
                    :active-section="activeSection"
                    @update:active-section="setActiveSection"
                />

                <div class="min-w-0 space-y-6">
                    <div
                        v-if="validationMessage"
                        class="rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800"
                        role="alert"
                    >
                        <strong>Please complete the required information.</strong> {{ validationMessage }}
                    </div>

                    <CustomFieldsPanel
                        v-if="activeSection === 'other'"
                        v-model="form.custom_fields"
                        :fields="customFields"
                        :errors="form.errors"
                    />

                    <PositionFormFields
                        v-else
                        :form="form"
                        :organizations="organizations"
                        :job-titles="jobTitles"
                        :project-managers="projectManagers"
                        :active-section="activeSection"
                    />

                    <div class="flex gap-3 border-t pt-5">
                        <Button type="submit" :disabled="form.processing">
                            {{ form.processing ? 'Creating…' : 'Create Position' }}
                        </Button>
                        <Button as-child variant="outline"><Link href="/portal/positions">Cancel</Link></Button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</template>
