<template>
    <div class="p-6 max-w-5xl space-y-6">

        <!-- Page Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold">
                    Create Position
                </h1>

                <p class="text-sm text-muted-foreground mt-1">
                    Add a new position record.
                </p>
            </div>

            <Link href="/positions">
                <Button variant="outline">
                    Back to List
                </Button>
            </Link>
        </div>

        <!-- Main Form -->
        <div class="border rounded-xl p-6 bg-background">
            <form
                @submit.prevent="submit"
                class="space-y-8"
            >

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

                        <!-- Position Code -->
                        <div class="space-y-2">
                            <Label for="position_code">
                                Position Code
                            </Label>

                            <Input
                                id="position_code"
                                v-model="form.position_code"
                                :class="form.errors.position_code ? 'border-red-500' : ''"
                            />

                            <p
                                v-if="form.errors.position_code"
                                class="text-sm text-red-500"
                            >
                                {{ form.errors.position_code }}
                            </p>
                        </div>

                        <!-- Status -->
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

                            <p
                                v-if="form.errors.status"
                                class="text-sm text-red-500"
                            >
                                {{ form.errors.status }}
                            </p>
                        </div>

                        <!-- Job Title Dropdown -->
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

                            <p
                                v-if="form.errors.job_title_id"
                                class="text-sm text-red-500"
                            >
                                {{ form.errors.job_title_id }}
                            </p>
                        </div>

                        <!-- Experience Level -->
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
                                <option value="">
                                    Select Experience Level
                                </option>

                                <option value="Beginner">Beginner</option>
                                <option value="Novice">Novice</option>
                                <option value="Experienced">Experienced</option>
                                <option value="Senior">Senior</option>
                            </select>

                            <p
                                v-if="form.errors.experience_level"
                                class="text-sm text-red-500"
                            >
                                {{ form.errors.experience_level }}
                            </p>
                        </div>

                        <!-- Labor Category Preview -->
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
                                This is generated from the selected Job Title and Experience Level.
                            </p>
                        </div>
                    </div>
                </section>

                <!-- Requirements and Qualifications -->
                <section class="space-y-4">
                    <div class="border-b pb-2">
                        <h2 class="text-lg font-semibold">
                            Requirements and Qualifications
                        </h2>
                    </div>

                    <!-- Certifications -->
                    <div class="space-y-2">
                        <Label for="certifications_required">
                            Certifications Required
                        </Label>

                        <Textarea
                            id="certifications_required"
                            v-model="form.certifications_required"
                            rows="4"
                            :class="form.errors.certifications_required ? 'border-red-500' : ''"
                        />

                        <p
                            v-if="form.errors.certifications_required"
                            class="text-sm text-red-500"
                        >
                            {{ form.errors.certifications_required }}
                        </p>
                    </div>

                    <!-- Training -->
                    <div class="space-y-2">
                        <Label for="training_required">
                            Training Required
                        </Label>

                        <Textarea
                            id="training_required"
                            v-model="form.training_required"
                            rows="4"
                            :class="form.errors.training_required ? 'border-red-500' : ''"
                        />

                        <p
                            v-if="form.errors.training_required"
                            class="text-sm text-red-500"
                        >
                            {{ form.errors.training_required }}
                        </p>
                    </div>

                    <!-- Experience -->
                    <div class="space-y-2">
                        <Label for="experience">
                            Experience
                        </Label>

                        <Textarea
                            id="experience"
                            v-model="form.experience"
                            rows="4"
                            :class="form.errors.experience ? 'border-red-500' : ''"
                        />

                        <p
                            v-if="form.errors.experience"
                            class="text-sm text-red-500"
                        >
                            {{ form.errors.experience }}
                        </p>
                    </div>
                </section>

                <!-- Flags and Risk -->
                <section class="space-y-4">
                    <div class="border-b pb-2">
                        <h2 class="text-lg font-semibold">
                            Flags and Risk
                        </h2>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

                        <!-- Essential -->
                        <label class="flex items-center justify-between rounded-lg border p-4 cursor-pointer hover:bg-muted/50">
                            <span class="font-medium text-sm">
                                Essential
                            </span>

                            <input
                                type="checkbox"
                                v-model="form.is_essential"
                                class="h-5 w-5"
                            />
                        </label>

                        <!-- Travel Required -->
                        <label class="flex items-center justify-between rounded-lg border p-4 cursor-pointer hover:bg-muted/50">
                            <span class="font-medium text-sm">
                                Travel Required
                            </span>

                            <input
                                type="checkbox"
                                v-model="form.travel_required"
                                class="h-5 w-5"
                            />
                        </label>

                        <!-- High Risk Role -->
                        <label class="flex items-center justify-between rounded-lg border p-4 cursor-pointer hover:bg-muted/50">
                            <span class="font-medium text-sm">
                                High Risk Role
                            </span>

                            <input
                                type="checkbox"
                                v-model="form.high_risk_role"
                                class="h-5 w-5"
                            />
                        </label>
                    </div>
                </section>

                <!-- Location Information -->
                <section class="space-y-4">
                    <div class="border-b pb-2">
                        <h2 class="text-lg font-semibold">
                            Location Information
                        </h2>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label for="location">
                                Location
                            </Label>

                            <Input
                                id="location"
                                v-model="form.location"
                                :class="form.errors.location ? 'border-red-500' : ''"
                            />

                            <p
                                v-if="form.errors.location"
                                class="text-sm text-red-500"
                            >
                                {{ form.errors.location }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="building">
                                Building
                            </Label>

                            <Input
                                id="building"
                                v-model="form.building"
                                :class="form.errors.building ? 'border-red-500' : ''"
                            />

                            <p
                                v-if="form.errors.building"
                                class="text-sm text-red-500"
                            >
                                {{ form.errors.building }}
                            </p>
                        </div>
                    </div>
                </section>

                <!-- Mission and Component -->
                <section class="space-y-4">
                    <div class="border-b pb-2">
                        <h2 class="text-lg font-semibold">
                            Mission and Component
                        </h2>
                    </div>

                    <div class="space-y-2">
                        <Label for="component">
                            Component
                        </Label>

                        <Input
                            id="component"
                            v-model="form.component"
                            :class="form.errors.component ? 'border-red-500' : ''"
                        />

                        <p
                            v-if="form.errors.component"
                            class="text-sm text-red-500"
                        >
                            {{ form.errors.component }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="mission_description">
                            Mission Description
                        </Label>

                        <Textarea
                            id="mission_description"
                            v-model="form.mission_description"
                            rows="4"
                            :class="form.errors.mission_description ? 'border-red-500' : ''"
                        />

                        <p
                            v-if="form.errors.mission_description"
                            class="text-sm text-red-500"
                        >
                            {{ form.errors.mission_description }}
                        </p>
                    </div>
                </section>

                <!-- Organization Information -->
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

                <!-- Form Actions -->
                <div class="flex gap-3">
                    <Button
                        type="submit"
                        :disabled="form.processing"
                    >
                        {{ form.processing ? 'Saving...' : 'Create Position' }}
                    </Button>

                    <Link href="/positions">
                        <Button
                            type="button"
                            variant="outline"
                        >
                            Cancel
                        </Button>
                    </Link>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'

import {
    Link,
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
    organizations: {
        type: Array,
        default: () => [],
    },

    jobTitles: {
        type: Array,
        default: () => [],
    },
})

/*
|--------------------------------------------------------------------------
| Form State
|--------------------------------------------------------------------------
*/

const form = useForm({
    position_code: '',

    status: 'Open',

    job_title_id: null,

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

    position_organization_id: null,

    sponsoring_organization_id: null,

    funding_organization_id: null,
})

/*
|--------------------------------------------------------------------------
| Computed Values
|--------------------------------------------------------------------------
*/

/**
 * Finds the selected Job Title object from the dropdown list.
 */
const selectedJobTitle = computed(() => {
    return props.jobTitles.find((jobTitle) => {
        return Number(jobTitle.id) === Number(form.job_title_id)
    })
})

/**
 * Builds the labor category preview.
 *
 * The backend will generate and save the actual labor_category value.
 */
const generatedLaborCategory = computed(() => {
    if (
        !selectedJobTitle.value ||
        !form.experience_level
    ) {
        return ''
    }

    return `${selectedJobTitle.value.name} - ${form.experience_level}`
})

/*
|--------------------------------------------------------------------------
| Form Submission
|--------------------------------------------------------------------------
*/

/**
 * Submit the create request to Laravel.
 */
function submit() {
    form.post('/positions')
}
</script>