<template>
    <div class="p-6 max-w-5xl space-y-6">

        <!-- Page Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold">
                    Edit Position
                </h1>

                <p class="text-sm text-muted-foreground mt-1">
                    Update this position record.
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
                                <option value="Open">
                                    Open
                                </option>

                                <option value="In Process">
                                    In Process
                                </option>

                                <option value="Closed">
                                    Closed
                                </option>
                            </select>

                            <p
                                v-if="form.errors.status"
                                class="text-sm text-red-500"
                            >
                                {{ form.errors.status }}
                            </p>
                        </div>

                        <!-- Job Title -->
                        <div class="space-y-2">
                            <Label for="job_title">
                                Job Title <span class="text-red-500">*</span>
                            </Label>

                            <Input
                                id="job_title"
                                v-model="form.job_title"
                                :class="form.errors.job_title ? 'border-red-500' : ''"
                            />

                            <p
                                v-if="form.errors.job_title"
                                class="text-sm text-red-500"
                            >
                                {{ form.errors.job_title }}
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

                                <option value="Beginner">
                                    Beginner
                                </option>

                                <option value="Novice">
                                    Novice
                                </option>

                                <option value="Experienced">
                                    Experienced
                                </option>

                                <option value="Senior">
                                    Senior
                                </option>
                            </select>

                            <p
                                v-if="form.errors.experience_level"
                                class="text-sm text-red-500"
                            >
                                {{ form.errors.experience_level }}
                            </p>
                        </div>

                        <!-- Labor Category -->
                        <div class="space-y-2 md:col-span-2">
                            <Label for="labor_category">
                                Labor Category
                            </Label>

                            <Input
                                id="labor_category"
                                v-model="form.labor_category"
                                disabled
                                class="bg-muted"
                            />

                            <p class="text-xs text-muted-foreground">
                                This is automatically generated from Job Title and Experience Level.
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

                        <p class="text-sm text-muted-foreground mt-1">
                            Capture required certifications, training, and experience.
                        </p>
                    </div>

                    <!-- Certifications Required -->
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

                    <!-- Training Required -->
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

                        <p class="text-sm text-muted-foreground mt-1">
                            Mark whether this position is essential, travel-based, or high risk.
                        </p>
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

                        <p class="text-sm text-muted-foreground mt-1">
                            Physical or operational location details.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                        <!-- Location -->
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

                        <!-- Building -->
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

                        <p class="text-sm text-muted-foreground mt-1">
                            Mission description and component information.
                        </p>
                    </div>

                    <!-- Component -->
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

                    <!-- Mission Description -->
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

                        <p class="text-sm text-muted-foreground mt-1">
                            Select the related position, sponsoring, and funding organizations.
                        </p>
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

                <!-- Funding Information -->
                <section class="space-y-4">
                    <div class="border-b pb-2">
                        <h2 class="text-lg font-semibold">
                            Funding Information
                        </h2>

                        <p class="text-sm text-muted-foreground mt-1">
                            Funding details and notes for this position.
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="funding_info">
                            Funding Info
                        </Label>

                        <Textarea
                            id="funding_info"
                            v-model="form.funding_info"
                            rows="4"
                            :class="form.errors.funding_info ? 'border-red-500' : ''"
                        />

                        <p
                            v-if="form.errors.funding_info"
                            class="text-sm text-red-500"
                        >
                            {{ form.errors.funding_info }}
                        </p>
                    </div>
                </section>

                <!-- Closure Workflow -->
                <section class="space-y-4">
                    <div class="border-b pb-2">
                        <h2 class="text-lg font-semibold">
                            Closure Workflow
                        </h2>

                        <p class="text-sm text-muted-foreground mt-1">
                            Track close requests, scheduled closure, and final closure information.
                        </p>
                    </div>

                    <!-- Request to Close -->
                    <label class="flex items-center justify-between rounded-lg border p-4 cursor-pointer hover:bg-muted/50">
                        <span class="font-medium text-sm">
                            Request to Close
                        </span>

                        <input
                            type="checkbox"
                            v-model="form.request_to_close"
                            class="h-5 w-5"
                        />
                    </label>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                        <!-- Scheduled to Close -->
                        <div class="space-y-2">
                            <Label for="scheduled_to_close">
                                Scheduled To Close
                            </Label>

                            <Input
                                id="scheduled_to_close"
                                type="date"
                                v-model="form.scheduled_to_close"
                                :class="form.errors.scheduled_to_close ? 'border-red-500' : ''"
                            />

                            <p
                                v-if="form.errors.scheduled_to_close"
                                class="text-sm text-red-500"
                            >
                                {{ form.errors.scheduled_to_close }}
                            </p>
                        </div>

                        <!-- Close Date -->
                        <div class="space-y-2">
                            <Label for="close_date">
                                Close Date
                                <span
                                    v-if="form.status === 'Closed'"
                                    class="text-red-500"
                                >
                                    *
                                </span>
                            </Label>

                            <Input
                                id="close_date"
                                type="date"
                                v-model="form.close_date"
                                :disabled="form.status !== 'Closed'"
                                :class="[
                                    form.errors.close_date ? 'border-red-500' : '',
                                    form.status !== 'Closed' ? 'bg-muted' : ''
                                ]"
                            />

                            <p
                                v-if="form.errors.close_date"
                                class="text-sm text-red-500"
                            >
                                {{ form.errors.close_date }}
                            </p>
                        </div>
                    </div>

                    <!-- Close Reason -->
                    <div class="space-y-2">
                        <Label for="close_reason">
                            Close Reason
                            <span
                                v-if="form.status === 'Closed'"
                                class="text-red-500"
                            >
                                *
                            </span>
                        </Label>

                        <Textarea
                            id="close_reason"
                            v-model="form.close_reason"
                            rows="4"
                            :disabled="form.status !== 'Closed'"
                            :class="[
                                form.errors.close_reason ? 'border-red-500' : '',
                                form.status !== 'Closed' ? 'bg-muted' : ''
                            ]"
                        />

                        <p
                            v-if="form.errors.close_reason"
                            class="text-sm text-red-500"
                        >
                            {{ form.errors.close_reason }}
                        </p>
                    </div>
                </section>

                <!-- Additional Information -->
                <section class="space-y-4">
                    <div class="border-b pb-2">
                        <h2 class="text-lg font-semibold">
                            Additional Information
                        </h2>

                        <p class="text-sm text-muted-foreground mt-1">
                            Existing optional fields currently used by the app.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                        <!-- Project Team Name -->
                        <div class="space-y-2">
                            <Label for="project_team_name">
                                Project Team Name
                            </Label>

                            <Input
                                id="project_team_name"
                                v-model="form.project_team_name"
                                :class="form.errors.project_team_name ? 'border-red-500' : ''"
                            />

                            <p
                                v-if="form.errors.project_team_name"
                                class="text-sm text-red-500"
                            >
                                {{ form.errors.project_team_name }}
                            </p>
                        </div>

                        <!-- Customer Lead Name -->
                        <div class="space-y-2">
                            <Label for="customer_lead_name">
                                Customer Lead Name
                            </Label>

                            <Input
                                id="customer_lead_name"
                                v-model="form.customer_lead_name"
                                :class="form.errors.customer_lead_name ? 'border-red-500' : ''"
                            />

                            <p
                                v-if="form.errors.customer_lead_name"
                                class="text-sm text-red-500"
                            >
                                {{ form.errors.customer_lead_name }}
                            </p>
                        </div>

                        <!-- Customer Created At -->
                        <div class="space-y-2">
                            <Label for="customer_created_at">
                                Customer Created At
                            </Label>

                            <Input
                                id="customer_created_at"
                                type="date"
                                v-model="form.customer_created_at"
                                :class="form.errors.customer_created_at ? 'border-red-500' : ''"
                            />

                            <p
                                v-if="form.errors.customer_created_at"
                                class="text-sm text-red-500"
                            >
                                {{ form.errors.customer_created_at }}
                            </p>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="space-y-2">
                        <Label for="notes">
                            Notes
                        </Label>

                        <Textarea
                            id="notes"
                            v-model="form.notes"
                            rows="5"
                            :class="form.errors.notes ? 'border-red-500' : ''"
                        />

                        <p
                            v-if="form.errors.notes"
                            class="text-sm text-red-500"
                        >
                            {{ form.errors.notes }}
                        </p>
                    </div>
                </section>

                <!-- Form Actions -->
                <div class="flex gap-3">
                    <Button
                        type="submit"
                        :disabled="form.processing"
                    >
                        {{ form.processing ? 'Saving...' : 'Save Changes' }}
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
import {
    computed,
    watch,
} from 'vue'

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
|
| The backend provides:
| - position: the current position being edited
| - organizations: the full organization dropdown list
|
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
})

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

/**
 * Formats a backend date value for use in an HTML date input.
 *
 * @param {string|null} value
 * @returns {string}
 */
function formatDateForInput(value) {

    if (!value) {
        return ''
    }

    return String(value).slice(0, 10)
}

/*
|--------------------------------------------------------------------------
| Form State
|--------------------------------------------------------------------------
|
| This initializes the edit form with the existing position record.
|
*/

const form = useForm({

    position_code:
        props.position.position_code ?? '',

    status:
        props.position.status ?? 'Open',

    job_title:
        props.position.job_title ?? '',

    experience_level:
        props.position.experience_level ?? '',

    labor_category:
        props.position.labor_category ?? '',

    certifications_required:
        props.position.certifications_required ?? '',

    training_required:
        props.position.training_required ?? '',

    experience:
        props.position.experience ?? '',

    is_essential:
        Boolean(props.position.is_essential),

    travel_required:
        Boolean(props.position.travel_required),

    high_risk_role:
        Boolean(props.position.high_risk_role),

    location:
        props.position.location ?? '',

    building:
        props.position.building ?? '',

    mission_description:
        props.position.mission_description ?? '',

    component:
        props.position.component ?? '',

    position_organization_id:
        props.position.position_organization_id ?? null,

    sponsoring_organization_id:
        props.position.sponsoring_organization_id ?? null,

    funding_organization_id:
        props.position.funding_organization_id ?? null,

    funding_info:
        props.position.funding_info ?? '',

    request_to_close:
        Boolean(props.position.request_to_close),

    scheduled_to_close:
        formatDateForInput(
            props.position.scheduled_to_close
        ),

    close_date:
        formatDateForInput(
            props.position.close_date
        ),

    close_reason:
        props.position.close_reason ?? '',

    project_team_name:
        props.position.project_team_name ?? '',

    customer_lead_name:
        props.position.customer_lead_name ?? '',

    customer_created_at:
        formatDateForInput(
            props.position.customer_created_at
        ),

    notes:
        props.position.notes ?? '',
})

/*
|--------------------------------------------------------------------------
| Computed Values
|--------------------------------------------------------------------------
*/

/**
 * Automatically builds the Labor Category value.
 *
 * Example:
 * Frontend Developer - Senior
 */
const generatedLaborCategory = computed(() => {

    if (
        !form.job_title ||
        !form.experience_level
    ) {
        return ''
    }

    return `${form.job_title} - ${form.experience_level}`
})

/*
|--------------------------------------------------------------------------
| Watchers
|--------------------------------------------------------------------------
*/

/**
 * Keeps labor category synchronized with
 * Job Title + Experience Level.
 */
watch(
    generatedLaborCategory,

    (value) => {
        form.labor_category = value
    },

    { immediate: true }
)

/**
 * Clears the close date and close reason when
 * the status is not Closed.
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
| Validation / Submit
|--------------------------------------------------------------------------
*/

/**
 * Performs lightweight frontend validation before
 * sending the update request to Laravel.
 */
function submit() {

    form.clearErrors()

    let hasError = false

    // Job title is required.
    if (
        !form.job_title ||
        form.job_title.trim() === ''
    ) {

        form.setError(
            'job_title',
            'Job title is required.'
        )

        hasError = true
    }

    // Status is required.
    if (
        !form.status ||
        form.status.trim() === ''
    ) {

        form.setError(
            'status',
            'Status is required.'
        )

        hasError = true
    }

    // Closed positions require final close information.
    if (form.status === 'Closed') {

        if (!form.close_date) {

            form.setError(
                'close_date',
                'Close date is required when status is Closed.'
            )

            hasError = true
        }

        if (
            !form.close_reason ||
            form.close_reason.trim() === ''
        ) {

            form.setError(
                'close_reason',
                'Close reason is required when status is Closed.'
            )

            hasError = true
        }
    }

    // Stop submission if local validation failed.
    if (hasError) {
        return
    }

    // Submit the updated position to Laravel.
    form.put(`/positions/${props.position.id}`)
}
</script>