<template>
    <div class="p-6 max-w-5xl space-y-6">
        <!-- Page Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold">
                    Edit Position
                </h1>

                <p class="text-sm text-muted-foreground mt-1">
                    Update this position record and manage custom skills/tasks.
                </p>
            </div>

            <Link href="/positions">
                <Button variant="outline">
                    Back to List
                </Button>
            </Link>
        </div>

        <!-- Main Position Form -->
        <div class="border rounded-xl p-6 bg-background">
            <form @submit.prevent="submit" class="space-y-8">

                <!-- Core Position Information -->
                <section class="space-y-4">
                    <div class="border-b pb-2">
                        <h2 class="text-lg font-semibold">
                            Core Position Information
                        </h2>

                        <p class="text-sm text-muted-foreground mt-1">
                            Basic position details and labor category information.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label for="position_code">
                                Position Code
                            </Label>

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
                                <option value="Open">Open</option>
                                <option value="In Process">In Process</option>
                                <option value="Closed">Closed</option>
                            </select>

                            <p v-if="form.errors.status" class="text-sm text-red-500">
                                {{ form.errors.status }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="job_title_id">
                                Job Title <span class="text-red-500">*</span>
                            </Label>

                            <select
                                id="job_title_id"
                                v-model="form.job_title_id"
                                :class="[
                                    'flex h-10 w-full rounded-md border bg-background px-3 py-2 text-sm',
                                    form.errors.job_title_id ? 'border-red-500' : 'border-input'
                                ]"
                            >
                                <option :value="null">
                                    Select Job Title
                                </option>

                                <option
                                    v-for="jobTitle in props.jobTitles"
                                    :key="jobTitle.id"
                                    :value="jobTitle.id"
                                >
                                    {{ jobTitle.name }}
                                </option>
                            </select>

                            <p v-if="form.errors.job_title_id" class="text-sm text-red-500">
                                {{ form.errors.job_title_id }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="experience_level">
                                Experience Level
                            </Label>

                            <select
                                id="experience_level"
                                v-model="form.experience_level"
                                :class="[
                                    'flex h-10 w-full rounded-md border bg-background px-3 py-2 text-sm',
                                    form.errors.experience_level ? 'border-red-500' : 'border-input'
                                ]"
                            >
                                <option value="">Select Experience Level</option>
                                <option value="Beginner">Beginner</option>
                                <option value="Novice">Novice</option>
                                <option value="Experienced">Experienced</option>
                                <option value="Senior">Senior</option>
                            </select>

                            <p v-if="form.errors.experience_level" class="text-sm text-red-500">
                                {{ form.errors.experience_level }}
                            </p>
                        </div>

                        <div class="space-y-2 md:col-span-2">
                            <Label>
                                Labor Category Preview
                            </Label>

                            <Input
                                :model-value="generatedLaborCategory || 'Select Job Title and Experience Level'"
                                disabled
                                class="bg-muted"
                            />

                            <p class="text-xs text-muted-foreground">
                                This is generated from Job Title and Experience Level.
                            </p>
                        </div>
                    </div>
                </section>

                <!-- Requirements -->
                <section class="space-y-4">
                    <div class="border-b pb-2">
                        <h2 class="text-lg font-semibold">
                            Requirements and Qualifications
                        </h2>
                    </div>

                    <div class="space-y-2">
                        <Label for="certifications_required">
                            Certifications Required
                        </Label>

                        <Textarea
                            id="certifications_required"
                            v-model="form.certifications_required"
                            rows="4"
                        />
                    </div>

                    <div class="space-y-2">
                        <Label for="training_required">
                            Training Required
                        </Label>

                        <Textarea
                            id="training_required"
                            v-model="form.training_required"
                            rows="4"
                        />
                    </div>

                    <div class="space-y-2">
                        <Label for="experience">
                            Experience
                        </Label>

                        <Textarea
                            id="experience"
                            v-model="form.experience"
                            rows="4"
                        />
                    </div>
                </section>

                <!-- Flags -->
                <section class="space-y-4">
                    <div class="border-b pb-2">
                        <h2 class="text-lg font-semibold">
                            Flags and Risk
                        </h2>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <label class="flex items-center justify-between rounded-lg border p-4 cursor-pointer">
                            <span class="font-medium text-sm">Essential</span>
                            <input type="checkbox" v-model="form.is_essential" class="h-5 w-5" />
                        </label>

                        <label class="flex items-center justify-between rounded-lg border p-4 cursor-pointer">
                            <span class="font-medium text-sm">Travel Required</span>
                            <input type="checkbox" v-model="form.travel_required" class="h-5 w-5" />
                        </label>

                        <label class="flex items-center justify-between rounded-lg border p-4 cursor-pointer">
                            <span class="font-medium text-sm">High Risk Role</span>
                            <input type="checkbox" v-model="form.high_risk_role" class="h-5 w-5" />
                        </label>
                    </div>
                </section>

                <!-- Location -->
                <section class="space-y-4">
                    <div class="border-b pb-2">
                        <h2 class="text-lg font-semibold">
                            Location Information
                        </h2>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label for="location">Location</Label>
                            <Input id="location" v-model="form.location" />
                        </div>

                        <div class="space-y-2">
                            <Label for="building">Building</Label>
                            <Input id="building" v-model="form.building" />
                        </div>
                    </div>
                </section>

                <!-- Mission -->
                <section class="space-y-4">
                    <div class="border-b pb-2">
                        <h2 class="text-lg font-semibold">
                            Mission and Component
                        </h2>
                    </div>

                    <div class="space-y-2">
                        <Label for="component">Component</Label>
                        <Input id="component" v-model="form.component" />
                    </div>

                    <div class="space-y-2">
                        <Label for="mission_description">Mission Description</Label>
                        <Textarea id="mission_description" v-model="form.mission_description" rows="4" />
                    </div>
                </section>

                <!-- Organizations -->
                <section class="space-y-4">
                    <div class="border-b pb-2">
                        <h2 class="text-lg font-semibold">
                            Organization Information
                        </h2>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <OrganizationSelect
                            v-model="form.position_organization_id"
                            :organizations="props.organizations"
                            label="Position Org"
                            id="position_organization_id"
                            :error="form.errors.position_organization_id"
                        />

                        <OrganizationSelect
                            v-model="form.sponsoring_organization_id"
                            :organizations="props.organizations"
                            label="Sponsoring Org"
                            id="sponsoring_organization_id"
                            :error="form.errors.sponsoring_organization_id"
                        />

                        <OrganizationSelect
                            v-model="form.funding_organization_id"
                            :organizations="props.organizations"
                            label="Funding Org"
                            id="funding_organization_id"
                            :error="form.errors.funding_organization_id"
                        />
                    </div>
                </section>

                <!-- Funding -->
                <section class="space-y-4">
                    <div class="border-b pb-2">
                        <h2 class="text-lg font-semibold">
                            Funding Information
                        </h2>
                    </div>

                    <div class="space-y-2">
                        <Label for="funding_info">Funding Info</Label>
                        <Textarea id="funding_info" v-model="form.funding_info" rows="4" />
                    </div>
                </section>

                <!-- Closure -->
                <section class="space-y-4">
                    <div class="border-b pb-2">
                        <h2 class="text-lg font-semibold">
                            Closure Workflow
                        </h2>
                    </div>

                    <label class="flex items-center justify-between rounded-lg border p-4 cursor-pointer">
                        <span class="font-medium text-sm">Request to Close</span>
                        <input type="checkbox" v-model="form.request_to_close" class="h-5 w-5" />
                    </label>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label for="scheduled_to_close">Scheduled To Close</Label>
                            <Input id="scheduled_to_close" type="date" v-model="form.scheduled_to_close" />
                        </div>

                        <div class="space-y-2">
                            <Label for="close_date">Close Date</Label>
                            <Input
                                id="close_date"
                                type="date"
                                v-model="form.close_date"
                                :disabled="form.status !== 'Closed'"
                                :class="form.status !== 'Closed' ? 'bg-muted' : ''"
                            />
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label for="close_reason">Close Reason</Label>
                        <Textarea
                            id="close_reason"
                            v-model="form.close_reason"
                            rows="4"
                            :disabled="form.status !== 'Closed'"
                            :class="form.status !== 'Closed' ? 'bg-muted' : ''"
                        />
                    </div>
                </section>

                <!-- Additional -->
                <section class="space-y-4">
                    <div class="border-b pb-2">
                        <h2 class="text-lg font-semibold">
                            Additional Information
                        </h2>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label for="project_team_name">Project Team Name</Label>
                            <Input id="project_team_name" v-model="form.project_team_name" />
                        </div>

                        <div class="space-y-2">
                            <Label for="customer_lead_name">Customer Lead Name</Label>
                            <Input id="customer_lead_name" v-model="form.customer_lead_name" />
                        </div>

                        <div class="space-y-2">
                            <Label for="customer_created_at">Customer Created At</Label>
                            <Input id="customer_created_at" type="date" v-model="form.customer_created_at" />
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label for="notes">Notes</Label>
                        <Textarea id="notes" v-model="form.notes" rows="5" />
                    </div>
                </section>

                <!-- Save Position -->
                <div class="flex gap-3">
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Saving...' : 'Save Changes' }}
                    </Button>

                    <Link href="/positions">
                        <Button type="button" variant="outline">
                            Cancel
                        </Button>
                    </Link>
                </div>
            </form>
        </div>

        <!-- Skills and Tasks -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            <!-- Skills Card -->
            <div class="border rounded-xl p-6 bg-background space-y-6">
                <div class="border-b pb-2">
                    <h2 class="text-lg font-semibold">
                        Skills
                    </h2>

                    <p class="text-sm text-muted-foreground mt-1">
                        Review default Job Title skills and add position-specific custom skills.
                    </p>
                </div>

                <!-- Default Skills -->
                <section class="space-y-3">
                    <h3 class="font-medium">
                        Default Skills From Job Title
                    </h3>

                    <div v-if="jobTitleSkills.length" class="space-y-2">
                        <div
                            v-for="skill in jobTitleSkills"
                            :key="skill.id"
                            class="rounded-lg border p-3 bg-muted/30"
                        >
                            <div class="font-medium text-sm">
                                {{ skill.name }}
                            </div>

                            <div
                                v-if="skill.description"
                                class="text-sm text-muted-foreground mt-1"
                            >
                                {{ skill.description }}
                            </div>
                        </div>
                    </div>

                    <p v-else class="text-sm text-muted-foreground">
                        No default skills assigned to this Job Title.
                    </p>
                </section>

                <!-- Add Custom Skill -->
                <section class="space-y-4 border-t pt-4">
                    <h3 class="font-medium">
                        Add Custom Skill
                    </h3>

                    <form @submit.prevent="submitCustomSkill" class="space-y-4">
                        <div class="space-y-2">
                            <Label for="custom_skill_name">Skill Name</Label>

                            <Input
                                id="custom_skill_name"
                                v-model="customSkillForm.name"
                            />

                            <p v-if="customSkillForm.errors.name" class="text-sm text-red-500">
                                {{ customSkillForm.errors.name }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="custom_skill_description">Description</Label>

                            <Textarea
                                id="custom_skill_description"
                                v-model="customSkillForm.description"
                                rows="3"
                            />
                        </div>

                        <Button type="submit" :disabled="customSkillForm.processing">
                            {{ customSkillForm.processing ? 'Adding...' : 'Add Custom Skill' }}
                        </Button>
                    </form>
                </section>

                <!-- Existing Custom Skills -->
                <section class="space-y-3 border-t pt-4">
                    <h3 class="font-medium">
                        Existing Custom Skills
                    </h3>

                    <div
                        v-for="skill in customSkills"
                        :key="skill.id"
                        class="border rounded-lg p-3 flex items-start justify-between gap-4"
                    >
                        <div>
                            <div class="font-medium text-sm">
                                {{ skill.name }}
                            </div>

                            <p class="text-sm text-muted-foreground mt-1">
                                {{ skill.description || 'No description provided.' }}
                            </p>
                        </div>

                        <Button
                            variant="destructive"
                            size="sm"
                            @click="deleteCustomSkill(skill.id)"
                        >
                            Delete
                        </Button>
                    </div>

                    <p v-if="!customSkills.length" class="text-sm text-muted-foreground">
                        No custom skills have been added.
                    </p>
                </section>
            </div>

            <!-- Tasks Card -->
            <div class="border rounded-xl p-6 bg-background space-y-6">
                <div class="border-b pb-2">
                    <h2 class="text-lg font-semibold">
                        Tasks
                    </h2>

                    <p class="text-sm text-muted-foreground mt-1">
                        Review default Job Title tasks and add position-specific custom tasks.
                    </p>
                </div>

                <!-- Default Tasks -->
                <section class="space-y-3">
                    <h3 class="font-medium">
                        Default Tasks From Job Title
                    </h3>

                    <div v-if="jobTitleTasks.length" class="space-y-2">
                        <div
                            v-for="task in jobTitleTasks"
                            :key="task.id"
                            class="rounded-lg border p-3 bg-muted/30"
                        >
                            <div class="font-medium text-sm">
                                {{ task.name }}
                            </div>

                            <div
                                v-if="task.description"
                                class="text-sm text-muted-foreground mt-1"
                            >
                                {{ task.description }}
                            </div>
                        </div>
                    </div>

                    <p v-else class="text-sm text-muted-foreground">
                        No default tasks assigned to this Job Title.
                    </p>
                </section>

                <!-- Add Custom Task -->
                <section class="space-y-4 border-t pt-4">
                    <h3 class="font-medium">
                        Add Custom Task
                    </h3>

                    <form @submit.prevent="submitCustomTask" class="space-y-4">
                        <div class="space-y-2">
                            <Label for="custom_task_name">Task Name</Label>

                            <Input
                                id="custom_task_name"
                                v-model="customTaskForm.name"
                            />

                            <p v-if="customTaskForm.errors.name" class="text-sm text-red-500">
                                {{ customTaskForm.errors.name }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="custom_task_description">Description</Label>

                            <Textarea
                                id="custom_task_description"
                                v-model="customTaskForm.description"
                                rows="3"
                            />
                        </div>

                        <Button type="submit" :disabled="customTaskForm.processing">
                            {{ customTaskForm.processing ? 'Adding...' : 'Add Custom Task' }}
                        </Button>
                    </form>
                </section>

                <!-- Existing Custom Tasks -->
                <section class="space-y-3 border-t pt-4">
                    <h3 class="font-medium">
                        Existing Custom Tasks
                    </h3>

                    <div
                        v-for="task in customTasks"
                        :key="task.id"
                        class="border rounded-lg p-3 flex items-start justify-between gap-4"
                    >
                        <div>
                            <div class="font-medium text-sm">
                                {{ task.name }}
                            </div>

                            <p class="text-sm text-muted-foreground mt-1">
                                {{ task.description || 'No description provided.' }}
                            </p>
                        </div>

                        <Button
                            variant="destructive"
                            size="sm"
                            @click="deleteCustomTask(task.id)"
                        >
                            Delete
                        </Button>
                    </div>

                    <p v-if="!customTasks.length" class="text-sm text-muted-foreground">
                        No custom tasks have been added.
                    </p>
                </section>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, watch } from 'vue'

import {
    Link,
    router,
    useForm,
} from '@inertiajs/vue3'

import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'

import OrganizationSelect from '@/components/OrganizationSelect.vue'

/*
|--------------------------------------------------------------------------
| Props
|--------------------------------------------------------------------------
*/

const props = defineProps({
    position: {
        type: Object,
        required: true,
    },

    organizations: {
        type: Array,
        default: () => [],
    },

    jobTitles: {
        type: Array,
        default: () => [],
    },

    jobTitleSkills: {
        type: Array,
        default: () => [],
    },

    jobTitleTasks: {
        type: Array,
        default: () => [],
    },
})

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

function formatDateForInput(value) {
    if (!value) {
        return ''
    }

    return String(value).slice(0, 10)
}

/*
|--------------------------------------------------------------------------
| Main Position Form
|--------------------------------------------------------------------------
*/

const form = useForm({
    position_code: props.position.position_code ?? '',
    status: props.position.status ?? 'Open',
    job_title_id: props.position.job_title_id ?? null,
    experience_level: props.position.experience_level ?? '',

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
})

/*
|--------------------------------------------------------------------------
| Skill / Task Data
|--------------------------------------------------------------------------
*/

const jobTitleSkills = computed(() => props.jobTitleSkills ?? [])
const jobTitleTasks = computed(() => props.jobTitleTasks ?? [])

const customSkills = computed(() => props.position.custom_skills ?? [])
const customTasks = computed(() => props.position.custom_tasks ?? [])

const customSkillForm = useForm({
    name: '',
    description: '',
})

const customTaskForm = useForm({
    name: '',
    description: '',
})

/*
|--------------------------------------------------------------------------
| Computed Values
|--------------------------------------------------------------------------
*/

const selectedJobTitle = computed(() => {
    return props.jobTitles.find((jobTitle) => {
        return Number(jobTitle.id) === Number(form.job_title_id)
    })
})

const generatedLaborCategory = computed(() => {
    if (!selectedJobTitle.value || !form.experience_level) {
        return ''
    }

    return `${selectedJobTitle.value.name} - ${form.experience_level}`
})

/*
|--------------------------------------------------------------------------
| Watchers
|--------------------------------------------------------------------------
*/

watch(
    () => form.status,
    (newStatus) => {
        if (newStatus !== 'Closed') {
            form.close_date = ''
            form.close_reason = ''
        }
    }
)

/*
|--------------------------------------------------------------------------
| Position Actions
|--------------------------------------------------------------------------
*/

function submit() {
    form.put(`/positions/${props.position.id}`)
}

/*
|--------------------------------------------------------------------------
| Custom Skill Actions
|--------------------------------------------------------------------------
*/

function submitCustomSkill() {
    customSkillForm.post(`/positions/${props.position.id}/custom-skills`, {
        preserveScroll: true,
        onSuccess: () => {
            customSkillForm.reset()
        },
    })
}

function deleteCustomSkill(skillId) {
    if (!confirm('Delete this custom skill?')) {
        return
    }

    router.delete(`/positions/${props.position.id}/custom-skills/${skillId}`, {
        preserveScroll: true,
    })
}

/*
|--------------------------------------------------------------------------
| Custom Task Actions
|--------------------------------------------------------------------------
*/

function submitCustomTask() {
    customTaskForm.post(`/positions/${props.position.id}/custom-tasks`, {
        preserveScroll: true,
        onSuccess: () => {
            customTaskForm.reset()
        },
    })
}

function deleteCustomTask(taskId) {
    if (!confirm('Delete this custom task?')) {
        return
    }

    router.delete(`/positions/${props.position.id}/custom-tasks/${taskId}`, {
        preserveScroll: true,
    })
}
</script>