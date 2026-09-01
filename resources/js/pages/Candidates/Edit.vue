<template>
    <div class="space-y-6">
        <div class="rounded-2xl border bg-white p-6 shadow-sm">
            <h1 class="text-2xl font-semibold text-gray-900">Edit Candidate</h1>
            <p class="mt-1 text-sm text-gray-500">
                Update the candidate and workflow details.
            </p>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <div class="rounded-2xl border bg-white p-6 shadow-sm">
                <h2 class="mb-4 text-lg font-semibold text-gray-900">
                    Candidate Details
                </h2>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <div>
                        <label for="candidate-person-id" class="mb-1 block text-sm font-medium text-gray-700">
                            Person
                            <span class="text-destructive" aria-hidden="true">*</span><span class="sr-only"> (required)</span>
                        </label>
                        <select
                            v-model="form.person_id"
                            id="candidate-person-id"
                            required
                            aria-required="true"
                            :aria-invalid="Boolean(form.errors.person_id)"
                            :aria-describedby="form.errors.person_id ? 'candidate-person-id-error' : undefined"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                        >
                            <option value="">Select person</option>
                            <option
                                v-for="person in people"
                                :key="person.id"
                                :value="person.id"
                            >
                                {{ person.full_name ?? `${person.first_name} ${person.last_name}` }}
                            </option>
                        </select>
                        <div v-if="form.errors.person_id" class="mt-1 text-sm text-destructive" id="candidate-person-id-error" role="alert">
                            {{ form.errors.person_id }}
                        </div>
                    </div>

                    <div>
                        <label for="candidate-position-id" class="mb-1 block text-sm font-medium text-gray-700">
                            Position
                            <span class="text-destructive" aria-hidden="true">*</span><span class="sr-only"> (required)</span>
                        </label>
                        <select
                            v-model="form.position_id"
                            id="candidate-position-id"
                            required
                            aria-required="true"
                            :aria-invalid="Boolean(form.errors.position_id)"
                            :aria-describedby="form.errors.position_id ? 'candidate-position-id-error' : undefined"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                        >
                            <option value="">Select position</option>
                            <option
                                v-for="position in positions"
                                :key="position.id"
                                :value="position.id"
                            >
                                {{ position.job_title ?? position.title ?? `Position #${position.id}` }}
                            </option>
                        </select>
                        <div v-if="form.errors.position_id" class="mt-1 text-sm text-destructive" id="candidate-position-id-error" role="alert">
                            {{ form.errors.position_id }}
                        </div>
                    </div>

                    <div>
                        <label for="candidate-status" class="mb-1 block text-sm font-medium text-gray-700">
                            Candidate Status
                            <span class="text-destructive" aria-hidden="true">*</span><span class="sr-only"> (required)</span>
                        </label>
                        <select
                            v-model="form.status"
                            id="candidate-status"
                            required
                            aria-required="true"
                            :aria-invalid="Boolean(form.errors.status)"
                            :aria-describedby="form.errors.status ? 'candidate-status-error' : undefined"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                        >
                            <option value="submitted">Submitted</option>
                            <option value="selected">Selected</option>
                            <option value="approved">Approved</option>
                            <option value="assigned">Assigned</option>
                        </select>
                        <div v-if="form.errors.status" class="mt-1 text-sm text-destructive" id="candidate-status-error" role="alert">
                            {{ form.errors.status }}
                        </div>
                    </div>

                    <div>
                        <label for="candidate-candidate-fbr" class="mb-1 block text-sm font-medium text-gray-700">
                            Candidate FBR
                        </label>
                        <input
                            v-model="form.candidate_fbr"
                            id="candidate-candidate-fbr"
                            :aria-invalid="Boolean(form.errors.candidate_fbr)"
                            :aria-describedby="form.errors.candidate_fbr ? 'candidate-candidate-fbr-error' : undefined"
                            type="number"
                            step="0.01"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                        />
                        <div v-if="form.errors.candidate_fbr" class="mt-1 text-sm text-destructive" id="candidate-candidate-fbr-error" role="alert">
                            {{ form.errors.candidate_fbr }}
                        </div>
                    </div>

                    <div>
                        <label for="candidate-submitted-at" class="mb-1 block text-sm font-medium text-gray-700">
                            Submitted At
                        </label>
                        <input
                            v-model="form.submitted_at"
                            id="candidate-submitted-at"
                            :aria-invalid="Boolean(form.errors.submitted_at)"
                            :aria-describedby="form.errors.submitted_at ? 'candidate-submitted-at-error' : undefined"
                            type="datetime-local"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                        />
                        <div v-if="form.errors.submitted_at" class="mt-1 text-sm text-destructive" id="candidate-submitted-at-error" role="alert">
                            {{ form.errors.submitted_at }}
                        </div>
                    </div>

                    <div>
                        <label for="candidate-submitted-by-person-id" class="mb-1 block text-sm font-medium text-gray-700">
                            Submitted By
                        </label>
                        <select
                            v-model="form.submitted_by_person_id"
                            id="candidate-submitted-by-person-id"
                            :aria-invalid="Boolean(form.errors.submitted_by_person_id)"
                            :aria-describedby="form.errors.submitted_by_person_id ? 'candidate-submitted-by-person-id-error' : undefined"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                        >
                            <option value="">Select person</option>
                            <option
                                v-for="person in people"
                                :key="person.id"
                                :value="person.id"
                            >
                                {{ person.full_name ?? `${person.first_name} ${person.last_name}` }}
                            </option>
                        </select>
                        <div
                            v-if="form.errors.submitted_by_person_id"
                            class="mt-1 text-sm text-destructive"
                         id="candidate-submitted-by-person-id-error" role="alert">
                            {{ form.errors.submitted_by_person_id }}
                        </div>
                    </div>

                    <div>
                        <label for="candidate-scheduled-start-date" class="mb-1 block text-sm font-medium text-gray-700">
                            Scheduled Start Date
                        </label>
                        <input
                            v-model="form.scheduled_start_date"
                            id="candidate-scheduled-start-date"
                            :aria-invalid="Boolean(form.errors.scheduled_start_date)"
                            :aria-describedby="form.errors.scheduled_start_date ? 'candidate-scheduled-start-date-error' : undefined"
                            type="date"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                        />
                        <div
                            v-if="form.errors.scheduled_start_date"
                            class="mt-1 text-sm text-destructive"
                         id="candidate-scheduled-start-date-error" role="alert">
                            {{ form.errors.scheduled_start_date }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border bg-white p-6 shadow-sm">
                <h2 class="mb-4 text-lg font-semibold text-gray-900">
                    Workflow Steps
                </h2>

                <CandidateWorkflowEditor
                    ref="workflowEditor"
                    v-model="form.step_events"
                    :workflow-steps="workflowSteps"
                    :existing-events="candidate.step_events ?? []"
                    :people="people"
                />
            </div>

            <div class="flex items-center gap-3">
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="rounded-lg bg-black px-4 py-2 text-sm font-medium text-white disabled:opacity-50"
                >
                    Update Candidate
                </button>

                <Link
                    href="/candidates"
                    class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700"
                >
                    Cancel
                </Link>
            </div>
        </form>
    </div>
</template>

<script setup>
import { useForm, Link } from '@inertiajs/vue3'
import CandidateWorkflowEditor from '@/components/forms/CandidateWorkflowEditor.vue'

// Backend-provided candidate data,
// workflow steps, people, and positions.
const props = defineProps({
    candidate: Object,
    people: Array,
    positions: Array,
    workflowSteps: Array,
})

/**
 * Normalizes datetime values into a format
 * compatible with the HTML datetime-local input.
 *
 * @param {string|null} value
 * @returns {string}
 */
function normalizeDateTime(value) {
    if (!value) return ''

    return value.length >= 16
        ? value.slice(0, 16)
        : value
}

// Reactive Inertia form state initialized
// with the existing candidate values.
const workflowEditor = ref(null)

const form = useForm({
    person_id: props.candidate.person_id ?? '',
    position_id: props.candidate.position_id ?? '',
    status: props.candidate.status ?? 'submitted',
    candidate_fbr: props.candidate.candidate_fbr ?? '',
    submitted_at: normalizeDateTime(props.candidate.submitted_at),
    submitted_by_person_id: props.candidate.submitted_by_person_id ?? '',
    scheduled_start_date: props.candidate.scheduled_start_date ?? '',
    step_events: props.candidate.step_events ?? [],
})

/**
 * Submits the updated candidate data
 * to the backend update endpoint.
 */
function submit() {
    form.step_events = workflowEditor.value?.getValue?.() ?? form.step_events
    form.put(`/candidates/${props.candidate.id}`)
}
</script>