<template>
    <div class="p-6 max-w-5xl space-y-6">

        <!-- ========================================================= -->
        <!-- Page Header -->
        <!-- ========================================================= -->

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

        <!-- ========================================================= -->
        <!-- Main Form -->
        <!-- ========================================================= -->

        <div class="border rounded-xl p-6 bg-background">

            <form
                @submit.prevent="submit"
                class="space-y-8"
            >

                <!-- ================================================= -->
                <!-- Core Position Information -->
                <!-- ================================================= -->

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
                                Status
                            </Label>

                            <select
                                id="status"
                                v-model="form.status"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
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
                                Job Title
                            </Label>

                            <Input
                                id="job_title"
                                v-model="form.job_title"
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
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
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

                <!-- ================================================= -->
                <!-- Requirements -->
                <!-- ================================================= -->

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
                        />

                        <p
                            v-if="form.errors.experience"
                            class="text-sm text-red-500"
                        >
                            {{ form.errors.experience }}
                        </p>
                    </div>
                </section>

                <!-- ================================================= -->
                <!-- Flags -->
                <!-- ================================================= -->

                <section class="space-y-4">

                    <div class="border-b pb-2">
                        <h2 class="text-lg font-semibold">
                            Flags and Risk
                        </h2>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

                        <!-- Essential -->
                        <label
                            class="flex items-center justify-between rounded-lg border p-4 cursor-pointer"
                        >
                            <span class="font-medium text-sm">
                                Essential
                            </span>

                            <input
                                type="checkbox"
                                v-model="form.is_essential"
                                class="h-5 w-5"
                            />
                        </label>

                        <!-- Travel -->
                        <label
                            class="flex items-center justify-between rounded-lg border p-4 cursor-pointer"
                        >
                            <span class="font-medium text-sm">
                                Travel Required
                            </span>

                            <input
                                type="checkbox"
                                v-model="form.travel_required"
                                class="h-5 w-5"
                            />
                        </label>

                        <!-- High Risk -->
                        <label
                            class="flex items-center justify-between rounded-lg border p-4 cursor-pointer"
                        >
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

                <!-- ================================================= -->
                <!-- Organization Information -->
                <!-- ================================================= -->

                <section class="space-y-4">

                    <div class="border-b pb-2">
                        <h2 class="text-lg font-semibold">
                            Organization Information
                        </h2>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

                        <OrganizationSelect
                            v-model="form.position_organization_id"
                            :organizations="organizations"
                            label="Position Org"
                            id="position_organization_id"
                            :error="form.errors.position_organization_id"
                        />

                        <OrganizationSelect
                            v-model="form.sponsoring_organization_id"
                            :organizations="organizations"
                            label="Sponsoring Org"
                            id="sponsoring_organization_id"
                            :error="form.errors.sponsoring_organization_id"
                        />

                        <OrganizationSelect
                            v-model="form.funding_organization_id"
                            :organizations="organizations"
                            label="Funding Org"
                            id="funding_organization_id"
                            :error="form.errors.funding_organization_id"
                        />
                    </div>
                </section>

                <!-- ================================================= -->
                <!-- Form Actions -->
                <!-- ================================================= -->

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
import { computed, watch } from 'vue'

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
})

/*
|--------------------------------------------------------------------------
| Form State
|--------------------------------------------------------------------------
*/

const form = useForm({

    position_code: '',

    status: 'Open',

    job_title: '',

    experience_level: '',

    labor_category: '',

    certifications_required: '',

    training_required: '',

    experience: '',

    is_essential: false,

    travel_required: false,

    high_risk_role: false,

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

/*
|--------------------------------------------------------------------------
| Form Submission
|--------------------------------------------------------------------------
*/

/**
 * Submit the create request.
 */
function submit() {

    form.post('/positions')
}
</script>