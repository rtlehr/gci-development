<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import {
    BadgeCheck,
    Building2,
    BriefcaseBusiness,
    MapPinned,
} from 'lucide-vue-next'
import PortalSectionNav from '@/components/portal/PortalSectionNav.vue'
import PositionFormFields from '@/components/positions/PositionFormFields.vue'
import { Button } from '@/components/ui/button'

type GenericRecord = Record<string, any>
type OrganizationOption = { id: number; name: string; full_path: string; depth?: number }

type CreateSection = 'details' | 'qualifications' | 'mission' | 'organization'

const props = withDefaults(defineProps<{
    organizations?: OrganizationOption[]
    jobTitles?: GenericRecord[]
    projectManagers?: GenericRecord[]
}>(), {
    organizations: () => [],
    jobTitles: () => [],
    projectManagers: () => [],
})

const activeSection = ref<CreateSection>('details')

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
})

const sections = computed(() => [
    {
        id: 'details',
        title: 'Position Details',
        description: 'Identifier, status, title, level, and manager.',
        icon: BriefcaseBusiness,
        complete: Boolean(form.status && form.job_title_id),
    },
    {
        id: 'qualifications',
        title: 'Qualifications',
        description: 'Certifications, training, and experience.',
        icon: BadgeCheck,
        complete: Boolean(form.certifications_required || form.training_required || form.experience),
    },
    {
        id: 'mission',
        title: 'Mission & Location',
        description: 'Operational flags, workplace, and mission.',
        icon: MapPinned,
        complete: Boolean(form.location || form.building || form.mission_description),
    },
    {
        id: 'organization',
        title: 'Organizations',
        description: 'Owning, sponsoring, and funding organizations.',
        icon: Building2,
        complete: Boolean(form.position_organization_id || form.sponsoring_organization_id || form.funding_organization_id),
    },
])

function setActiveSection(value: string): void {
    activeSection.value = value as CreateSection
}

function focusFirstError(): void {
    requestAnimationFrame(() => {
        const firstInvalid = document.querySelector<HTMLElement>('[aria-invalid="true"], .border-destructive')
        firstInvalid?.focus()
    })
}

function submit(): void {
    form.post('/portal/positions', { onError: focusFirstError })
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
            <Link href="/portal/positions">
                <Button variant="outline">Back to List</Button>
            </Link>
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
                    <PositionFormFields
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
                        <Link href="/portal/positions">
                            <Button type="button" variant="outline">Cancel</Button>
                        </Link>
                    </div>
                </div>
            </div>
        </form>
    </div>
</template>
