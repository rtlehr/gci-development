<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { Link, router, useForm } from '@inertiajs/vue3'
import { BadgeCheck, BriefcaseBusiness, Building2, ClipboardList, ListPlus, MapPinned, Settings2, Trash2, Users } from 'lucide-vue-next'
import ConfirmActionDialog from '@/components/ConfirmActionDialog.vue'
import CustomFieldsPanel from '@/components/custom-fields/CustomFieldsPanel.vue'
import FormField from '@/components/forms/FormField.vue'
import FormSection from '@/components/forms/FormSection.vue'
import PositionCandidatesPanel from '@/components/positions/PositionCandidatesPanel.vue'
import PositionFormFields from '@/components/positions/PositionFormFields.vue'
import PortalSectionNav from '@/components/portal/PortalSectionNav.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'

type GenericRecord = Record<string, any>
type OrganizationOption = { id: number; name: string; full_path: string; depth?: number }

const props = withDefaults(defineProps<{
    position: GenericRecord
    organizations?: OrganizationOption[]
    jobTitles?: GenericRecord[]
    projectManagers?: GenericRecord[]
    jobTitleSkills?: GenericRecord[]
    jobTitleTasks?: GenericRecord[]
    candidateOptions?: GenericRecord[]
    positionCandidates?: GenericRecord[]
    workflows?: GenericRecord[]
    initialSection?: string
    customFields?: GenericRecord[]
    customFieldValues?: Record<string, any>
}>(), {
    organizations: () => [],
    jobTitles: () => [],
    projectManagers: () => [],
    jobTitleSkills: () => [],
    jobTitleTasks: () => [],
    candidateOptions: () => [],
    positionCandidates: () => [],
    workflows: () => [],
    initialSection: 'general',
    customFields: () => [],
    customFieldValues: () => ({}),
})


type PositionEditSection = 'details' | 'qualifications' | 'mission' | 'organization' | 'other' | 'operations' | 'requirements' | 'candidates'

const normalizedInitialSection = props.initialSection === 'general' ? 'details' : props.initialSection
const activeSection = ref<PositionEditSection>((normalizedInitialSection as PositionEditSection) || 'details')
const deleteDialogOpen = ref(false)
const pendingCustomDelete = ref<{ type: 'skill' | 'task'; id: number } | null>(null)

watch(activeSection, (section) => {
    const url = new URL(window.location.href)
    url.searchParams.set('section', section)
    window.history.replaceState({}, '', url)
})

const formatDateForInput = (value: unknown): string => value ? String(value).slice(0, 10) : ''
const form = useForm({
    position_code: props.position.position_code ?? '',
    status: props.position.status ?? 'Open',
    job_title_id: props.position.job_title_id ?? null,
    level: props.position.level ?? '',
    team_name: props.position.team_name ?? '',
    project_manager_user_id: props.position.project_manager_user_id ?? null,
    certifications_required: props.position.certifications_required ?? '',
    training_required: props.position.training_required ?? '',
    experience: props.position.experience ?? '',
    is_essential: Boolean(props.position.is_essential),
    travel_required: Boolean(props.position.travel_required),
    high_risk_role: Boolean(props.position.high_risk_role),
    location: props.position.location ?? '',
    building: props.position.building ?? '',
    mission_description: props.position.mission_description ?? '',
    component: props.position.component ?? '',
    position_organization_id: props.position.position_organization_id ?? null,
    sponsoring_organization_id: props.position.sponsoring_organization_id ?? null,
    funding_organization_id: props.position.funding_organization_id ?? null,
    funding_info: props.position.funding_info ?? '',
    request_to_close: Boolean(props.position.request_to_close),
    scheduled_to_close: formatDateForInput(props.position.scheduled_to_close),
    close_date: formatDateForInput(props.position.close_date),
    close_reason: props.position.close_reason ?? '',
    project_team_name: props.position.project_team_name ?? '',
    customer_lead_name: props.position.customer_lead_name ?? '',
    customer_created_at: formatDateForInput(props.position.customer_created_at),
    notes: props.position.notes ?? '',
    custom_fields: { ...props.customFieldValues },
})

const customSkillForm = useForm({ name: '', description: '', requirement_type: 'required' })
const customTaskForm = useForm({ name: '', description: '' })

const customSkills = computed(() => props.position.custom_skills ?? [])
const customTasks = computed(() => props.position.custom_tasks ?? [])

const requiredJobTitleSkills = computed(() =>
    props.jobTitleSkills.filter((skill) => skill.requirement_type !== 'desired'),
)

const desiredJobTitleSkills = computed(() =>
    props.jobTitleSkills.filter((skill) => skill.requirement_type === 'desired'),
)

const requiredJobTitleTasks = computed(() =>
    props.jobTitleTasks.filter((task) => task.requirement_type !== 'desired'),
)

const desiredJobTitleTasks = computed(() =>
    props.jobTitleTasks.filter((task) => task.requirement_type === 'desired'),
)

const requiredCustomSkills = computed(() =>
    customSkills.value.filter((skill: GenericRecord) => skill.requirement_type !== 'desired'),
)

const desiredCustomSkills = computed(() =>
    customSkills.value.filter((skill: GenericRecord) => skill.requirement_type === 'desired'),
)


const sections = computed(() => [
    { id: 'details', title: 'Position Details', description: 'Identifier, status, title, level, and manager.', icon: BriefcaseBusiness, complete: Boolean(form.status && form.job_title_id) },
    { id: 'qualifications', title: 'Qualifications', description: 'Certifications, training, and experience.', icon: BadgeCheck, complete: Boolean(form.certifications_required || form.training_required || form.experience) },
    { id: 'mission', title: 'Mission & Location', description: 'Operational flags, workplace, and mission.', icon: MapPinned, complete: Boolean(form.location || form.building || form.mission_description) },
    { id: 'organization', title: 'Organizations', description: 'Owning, sponsoring, and funding organizations.', icon: Building2, complete: Boolean(form.position_organization_id || form.sponsoring_organization_id || form.funding_organization_id) },
    { id: 'other', title: 'Other Information', description: 'Installation-specific fields.', icon: ListPlus, complete: Object.values(form.custom_fields).some((value) => Array.isArray(value) ? value.length > 0 : Boolean(value)) },
    { id: 'operations', title: 'Operations', description: 'Funding, closure, customer, and internal details.', icon: Settings2, complete: Boolean(form.funding_info || form.customer_lead_name || form.notes) },
    { id: 'requirements', title: 'Skills & Tasks', description: 'Job-title defaults and position-specific requirements.', icon: ClipboardList, complete: Boolean(props.jobTitleSkills.length || props.jobTitleTasks.length || customSkills.value.length || customTasks.value.length) },
    { id: 'candidates', title: 'Candidates', description: 'Connected candidates and workflow activity.', icon: Users, badge: props.positionCandidates.length, complete: Boolean(props.positionCandidates.length) },
])

const selectedJobTitle = computed(() => props.jobTitles.find((item) => Number(item.id) === Number(form.job_title_id)))
watch(() => form.status, (status) => {
    if (status !== 'Closed') {
        form.close_date = ''
        form.close_reason = ''
    }
})

function setActiveSection(value: string): void {
    activeSection.value = value as PositionEditSection
}

function focusFirstError(): void {
    requestAnimationFrame(() => document.querySelector<HTMLElement>('[aria-invalid="true"], .border-destructive')?.focus())
}

function submit(): void {
    form.put(`/portal/positions/${props.position.id}`, { preserveScroll: true, onError: focusFirstError })
}

function submitCustomSkill(): void {
    customSkillForm.post(`/portal/positions/${props.position.id}/custom-skills`, {
        preserveScroll: true,
        onSuccess: () => customSkillForm.reset(),
    })
}

function submitCustomTask(): void {
    customTaskForm.post(`/portal/positions/${props.position.id}/custom-tasks`, {
        preserveScroll: true,
        onSuccess: () => customTaskForm.reset(),
    })
}

function deleteCustomSkill(id: number): void {
    pendingCustomDelete.value = { type: 'skill', id }
    deleteDialogOpen.value = true
}

function deleteCustomTask(id: number): void {
    pendingCustomDelete.value = { type: 'task', id }
    deleteDialogOpen.value = true
}

function confirmCustomDelete(): void {
    if (!pendingCustomDelete.value) return

    const { type, id } = pendingCustomDelete.value
    const segment = type === 'skill' ? 'custom-skills' : 'custom-tasks'

    router.delete(`/portal/positions/${props.position.id}/${segment}/${id}`, {
        preserveScroll: true,
        onFinish: () => {
            deleteDialogOpen.value = false
            pendingCustomDelete.value = null
        },
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
                <h1 class="text-2xl font-semibold">Edit {{ position.position_code || 'Position' }}</h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    Manage position details, requirements, and connected candidates.
                </p>
            </div>
            <Link :href="`/portal/positions/${position.id}`">
                <Button variant="outline">Back to Position</Button>
            </Link>
        </div>

        <div class="grid gap-6 lg:grid-cols-[270px_minmax(0,1fr)]">
                <PortalSectionNav
                    title="Position sections"
                    aria-label="Position sections"
                    :sections="sections"
                    :active-section="activeSection"
                    @update:active-section="setActiveSection"
                />

                <div class="min-w-0 space-y-6">
            <PositionCandidatesPanel
                v-if="activeSection === 'candidates'"
                :position-id="position.id"
                base-path="/portal/positions"
                :candidate-options="candidateOptions"
                :candidates="positionCandidates"
                :workflows="workflows"
            />

            <form
                v-else
                class="min-w-0 space-y-6"
                @submit.prevent="submit"
            >
                    <CustomFieldsPanel
                        v-if="activeSection === 'other'"
                        v-model="form.custom_fields"
                        :fields="customFields"
                        :errors="form.errors"
                    />

                    <PositionFormFields
                        v-else-if="!['requirements', 'candidates'].includes(activeSection)"
                        :form="form"
                        :organizations="organizations"
                        :job-titles="jobTitles"
                        :project-managers="projectManagers"
                        :active-section="activeSection"
                        extended
                    />

                    <template v-if="activeSection === 'requirements'">
                        <FormSection
                            title="Skills"
                            description="Review default job-title skills and maintain position-specific skills."
                        >
                            <div>
                                <h3 class="text-sm font-semibold">
                                    Default Skills
                                </h3>

                                <div
                                    v-if="jobTitleSkills.length"
                                    class="mt-3 grid gap-4 lg:grid-cols-2"
                                >
                                    <div class="rounded-lg border bg-muted/30 p-4">
                                        <h4 class="text-sm font-semibold">
                                            Required Skills ({{ requiredJobTitleSkills.length }})
                                        </h4>

                                        <ol
                                            v-if="requiredJobTitleSkills.length"
                                            class="mt-3 list-decimal space-y-3 pl-6 text-sm"
                                        >
                                            <li
                                                v-for="skill in requiredJobTitleSkills"
                                                :key="skill.id"
                                                class="pl-1"
                                            >
                                                <p class="font-medium">
                                                    {{ skill.name }}
                                                </p>
                                                <p
                                                    v-if="skill.description"
                                                    class="mt-1 text-xs text-muted-foreground"
                                                >
                                                    {{ skill.description }}
                                                </p>
                                            </li>
                                        </ol>

                                        <p
                                            v-else
                                            class="mt-3 text-sm text-muted-foreground"
                                        >
                                            No required skills are assigned.
                                        </p>
                                    </div>

                                    <div class="rounded-lg border bg-muted/30 p-4">
                                        <h4 class="text-sm font-semibold">
                                            Desired Skills ({{ desiredJobTitleSkills.length }})
                                        </h4>

                                        <ol
                                            v-if="desiredJobTitleSkills.length"
                                            class="mt-3 list-decimal space-y-3 pl-6 text-sm"
                                        >
                                            <li
                                                v-for="skill in desiredJobTitleSkills"
                                                :key="skill.id"
                                                class="pl-1"
                                            >
                                                <p class="font-medium">
                                                    {{ skill.name }}
                                                </p>
                                                <p
                                                    v-if="skill.description"
                                                    class="mt-1 text-xs text-muted-foreground"
                                                >
                                                    {{ skill.description }}
                                                </p>
                                            </li>
                                        </ol>

                                        <p
                                            v-else
                                            class="mt-3 text-sm text-muted-foreground"
                                        >
                                            No desired skills are assigned.
                                        </p>
                                    </div>
                                </div>

                                <p
                                    v-else
                                    class="mt-2 text-sm text-muted-foreground"
                                >
                                    No default skills are assigned to the selected job title.
                                </p>
                            </div>

                            <div class="border-t pt-5">
                                <h3 class="text-sm font-semibold">
                                    Add Custom Skill
                                </h3>

                                <div class="mt-3 grid gap-4 md:grid-cols-2">
                                    <FormField
                                        label="Skill Name"
                                        for-id="custom_skill_name"
                                        :error="customSkillForm.errors.name"
                                    >
                                        <Input
                                            id="custom_skill_name"
                                            v-model="customSkillForm.name"
                                        />
                                    </FormField>

                                    <FormField
                                        label="Requirement"
                                        for-id="custom_skill_requirement_type"
                                        :error="customSkillForm.errors.requirement_type"
                                    >
                                        <select
                                            id="custom_skill_requirement_type"
                                            v-model="customSkillForm.requirement_type"
                                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                        >
                                            <option value="required">
                                                Required
                                            </option>
                                            <option value="desired">
                                                Desired
                                            </option>
                                        </select>
                                    </FormField>

                                    <FormField
                                        class="md:col-span-2"
                                        label="Description"
                                        for-id="custom_skill_description"
                                        :error="customSkillForm.errors.description"
                                    >
                                        <Textarea
                                            id="custom_skill_description"
                                            v-model="customSkillForm.description"
                                            rows="3"
                                        />
                                    </FormField>
                                </div>

                                <Button
                                    type="button"
                                    class="mt-4"
                                    :disabled="customSkillForm.processing"
                                    @click="submitCustomSkill"
                                >
                                    {{ customSkillForm.processing ? 'Adding…' : 'Add Custom Skill' }}
                                </Button>
                            </div>

                            <div
                                v-if="customSkills.length"
                                class="border-t pt-5"
                            >
                                <h3 class="text-sm font-semibold">
                                    Existing Custom Skills
                                </h3>

                                <div class="mt-3 grid gap-4 lg:grid-cols-2">
                                    <div class="rounded-lg border p-4">
                                        <h4 class="text-sm font-semibold">
                                            Required Skills ({{ requiredCustomSkills.length }})
                                        </h4>

                                        <ol
                                            v-if="requiredCustomSkills.length"
                                            class="mt-3 list-decimal space-y-3 pl-6 text-sm"
                                        >
                                            <li
                                                v-for="skill in requiredCustomSkills"
                                                :key="skill.id"
                                                class="pl-1"
                                            >
                                                <div class="flex items-start justify-between gap-4">
                                                    <div class="min-w-0">
                                                        <p class="font-medium">
                                                            {{ skill.name }}
                                                        </p>
                                                        <p
                                                            v-if="skill.description"
                                                            class="mt-1 text-xs text-muted-foreground"
                                                        >
                                                            {{ skill.description }}
                                                        </p>
                                                    </div>

                                                    <Button
                                                        type="button"
                                                        variant="ghost"
                                                        size="icon"
                                                        aria-label="Delete custom skill"
                                                        @click="deleteCustomSkill(skill.id)"
                                                    >
                                                        <Trash2 class="h-4 w-4" />
                                                    </Button>
                                                </div>
                                            </li>
                                        </ol>

                                        <p
                                            v-else
                                            class="mt-3 text-sm text-muted-foreground"
                                        >
                                            No required custom skills have been added.
                                        </p>
                                    </div>

                                    <div class="rounded-lg border p-4">
                                        <h4 class="text-sm font-semibold">
                                            Desired Skills ({{ desiredCustomSkills.length }})
                                        </h4>

                                        <ol
                                            v-if="desiredCustomSkills.length"
                                            class="mt-3 list-decimal space-y-3 pl-6 text-sm"
                                        >
                                            <li
                                                v-for="skill in desiredCustomSkills"
                                                :key="skill.id"
                                                class="pl-1"
                                            >
                                                <div class="flex items-start justify-between gap-4">
                                                    <div class="min-w-0">
                                                        <p class="font-medium">
                                                            {{ skill.name }}
                                                        </p>
                                                        <p
                                                            v-if="skill.description"
                                                            class="mt-1 text-xs text-muted-foreground"
                                                        >
                                                            {{ skill.description }}
                                                        </p>
                                                    </div>

                                                    <Button
                                                        type="button"
                                                        variant="ghost"
                                                        size="icon"
                                                        aria-label="Delete custom skill"
                                                        @click="deleteCustomSkill(skill.id)"
                                                    >
                                                        <Trash2 class="h-4 w-4" />
                                                    </Button>
                                                </div>
                                            </li>
                                        </ol>

                                        <p
                                            v-else
                                            class="mt-3 text-sm text-muted-foreground"
                                        >
                                            No desired custom skills have been added.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </FormSection>

                        <FormSection
                            title="Tasks"
                            description="Review default job-title tasks and maintain position-specific tasks."
                        >
                            <div>
                                <h3 class="text-sm font-semibold">
                                    Default Tasks
                                </h3>

                                <div
                                    v-if="jobTitleTasks.length"
                                    class="mt-3 grid gap-4 lg:grid-cols-2"
                                >
                                    <div class="rounded-lg border bg-muted/30 p-4">
                                        <h4 class="text-sm font-semibold">
                                            Required Tasks ({{ requiredJobTitleTasks.length }})
                                        </h4>

                                        <ol
                                            v-if="requiredJobTitleTasks.length"
                                            class="mt-3 list-decimal space-y-3 pl-6 text-sm"
                                        >
                                            <li
                                                v-for="task in requiredJobTitleTasks"
                                                :key="task.id"
                                                class="pl-1"
                                            >
                                                <p class="font-medium">
                                                    {{ task.name }}
                                                </p>
                                                <p
                                                    v-if="task.description"
                                                    class="mt-1 text-xs text-muted-foreground"
                                                >
                                                    {{ task.description }}
                                                </p>
                                            </li>
                                        </ol>

                                        <p
                                            v-else
                                            class="mt-3 text-sm text-muted-foreground"
                                        >
                                            No required tasks are assigned.
                                        </p>
                                    </div>

                                    <div class="rounded-lg border bg-muted/30 p-4">
                                        <h4 class="text-sm font-semibold">
                                            Desired Tasks ({{ desiredJobTitleTasks.length }})
                                        </h4>

                                        <ol
                                            v-if="desiredJobTitleTasks.length"
                                            class="mt-3 list-decimal space-y-3 pl-6 text-sm"
                                        >
                                            <li
                                                v-for="task in desiredJobTitleTasks"
                                                :key="task.id"
                                                class="pl-1"
                                            >
                                                <p class="font-medium">
                                                    {{ task.name }}
                                                </p>
                                                <p
                                                    v-if="task.description"
                                                    class="mt-1 text-xs text-muted-foreground"
                                                >
                                                    {{ task.description }}
                                                </p>
                                            </li>
                                        </ol>

                                        <p
                                            v-else
                                            class="mt-3 text-sm text-muted-foreground"
                                        >
                                            No desired tasks are assigned.
                                        </p>
                                    </div>
                                </div>

                                <p
                                    v-else
                                    class="mt-2 text-sm text-muted-foreground"
                                >
                                    No default tasks are assigned to the selected job title.
                                </p>
                            </div>

                            <div class="border-t pt-5">
                                <h3 class="text-sm font-semibold">
                                    Add Custom Task
                                </h3>

                                <div class="mt-3 grid gap-4 md:grid-cols-2">
                                    <FormField
                                        label="Task Name"
                                        for-id="custom_task_name"
                                        :error="customTaskForm.errors.name"
                                    >
                                        <Input
                                            id="custom_task_name"
                                            v-model="customTaskForm.name"
                                        />
                                    </FormField>

                                    <FormField
                                        label="Description"
                                        for-id="custom_task_description"
                                        :error="customTaskForm.errors.description"
                                    >
                                        <Textarea
                                            id="custom_task_description"
                                            v-model="customTaskForm.description"
                                            rows="3"
                                        />
                                    </FormField>
                                </div>

                                <Button
                                    type="button"
                                    class="mt-4"
                                    :disabled="customTaskForm.processing"
                                    @click="submitCustomTask"
                                >
                                    {{ customTaskForm.processing ? 'Adding…' : 'Add Custom Task' }}
                                </Button>
                            </div>

                            <div
                                v-if="customTasks.length"
                                class="border-t pt-5"
                            >
                                <h3 class="text-sm font-semibold">
                                    Existing Custom Tasks ({{ customTasks.length }})
                                </h3>

                                <ol class="mt-3 list-decimal space-y-3 pl-6 text-sm">
                                    <li
                                        v-for="task in customTasks"
                                        :key="task.id"
                                        class="pl-1"
                                    >
                                        <div class="flex items-start justify-between gap-4 rounded-lg border p-4">
                                            <div class="min-w-0">
                                                <p class="font-medium">
                                                    {{ task.name }}
                                                </p>
                                                <p
                                                    v-if="task.description"
                                                    class="mt-1 text-xs text-muted-foreground"
                                                >
                                                    {{ task.description }}
                                                </p>
                                            </div>

                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="icon"
                                                aria-label="Delete custom task"
                                                @click="deleteCustomTask(task.id)"
                                            >
                                                <Trash2 class="h-4 w-4" />
                                            </Button>
                                        </div>
                                    </li>
                                </ol>
                            </div>
                        </FormSection>
                    </template>

                    <div class="flex gap-3 border-t pt-5">
                        <Button type="submit" :disabled="form.processing">
                            {{ form.processing ? 'Saving…' : 'Save Changes' }}
                        </Button>
                        <Link :href="`/portal/positions/${position.id}`">
                            <Button type="button" variant="outline">Cancel</Button>
                        </Link>
                    </div>
            </form>
                </div>
            </div>
    </div>

    <ConfirmActionDialog
        v-model:open="deleteDialogOpen"
        :title="pendingCustomDelete?.type === 'skill' ? 'Delete Custom Skill?' : 'Delete Custom Task?'"
        description="This custom item will be permanently deleted. This action cannot be undone."
        confirm-label="Delete"
        destructive
        @confirm="confirmCustomDelete"
    />
</template>
