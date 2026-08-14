<template>
    <!-- Main container for all workflow step cards -->
    <div class="space-y-6">

        <!-- Loop through each workflow step -->
        <div
            v-for="step in localSteps"
            :key="step.id"
            class="rounded-2xl border bg-white p-4 shadow-sm"
        >
            <!-- Step header -->
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <!-- Step display name -->
                    <h3 class="text-lg font-semibold text-gray-900">
                        {{ step.name }}
                    </h3>

                    <!-- Internal step code -->
                    <p class="text-sm text-gray-500">
                        {{ step.code }}
                    </p>
                </div>

                <!-- Step order number -->
                <div class="text-sm text-gray-500">
                    Step {{ step.step_order }}
                </div>
            </div>

            <!-- Main workflow step field grid -->
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">

                <!-- Status selection -->
                <div v-if="step.allows_status">
                    <label :for="`workflow-step-${step.id}-status`" class="mb-1 block text-sm font-medium text-gray-700">
                        Status
                    </label>

                    <select
                        :id="`workflow-step-${step.id}-status`"
                        v-model="step.form.status_code"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                    >
                        <option value="">Select status</option>

                        <!-- Available statuses for this workflow step -->
                        <option
                            v-for="status in step.statuses"
                            :key="status.id"
                            :value="status.status_code"
                        >
                            {{ status.status_label }}
                        </option>
                    </select>
                </div>

                <!-- Person responsible for this workflow action -->
                <div v-if="showPerformedBy(step)">
                    <label :for="`workflow-step-${step.id}-performed-by`" class="mb-1 block text-sm font-medium text-gray-700">
                        Performed By
                    </label>

                    <select
                        :id="`workflow-step-${step.id}-performed-by`"
                        v-model="step.form.performed_by_person_id"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                    >
                        <option value="">Select person</option>

                        <!-- Available people list -->
                        <option
                            v-for="person in people"
                            :key="person.id"
                            :value="person.id"
                        >
                            {{ person.full_name ?? `${person.first_name} ${person.last_name}` }}
                        </option>
                    </select>
                </div>

                <!-- Requested datetime -->
                <div v-if="step.allows_requested_at">
                    <label :for="`workflow-step-${step.id}-requested-at`" class="mb-1 block text-sm font-medium text-gray-700">
                        Requested Date / Time
                    </label>

                    <input
                        :id="`workflow-step-${step.id}-requested-at`"
                        v-model="step.form.requested_at"
                        type="datetime-local"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                    />
                </div>

                <!-- Scheduled datetime -->
                <div v-if="step.allows_scheduled_at">
                    <label :for="`workflow-step-${step.id}-scheduled-at`" class="mb-1 block text-sm font-medium text-gray-700">
                        Scheduled Date / Time
                    </label>

                    <input
                        :id="`workflow-step-${step.id}-scheduled-at`"
                        v-model="step.form.scheduled_at"
                        type="datetime-local"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                    />
                </div>

                <!-- Completed datetime -->
                <div v-if="step.allows_completed_at">
                    <label :for="`workflow-step-${step.id}-completed-at`" class="mb-1 block text-sm font-medium text-gray-700">
                        Completed Date / Time
                    </label>

                    <input
                        :id="`workflow-step-${step.id}-completed-at`"
                        v-model="step.form.completed_at"
                        type="datetime-local"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                    />
                </div>
            </div>

            <!-- Notes and comments section -->
            <div
                v-if="step.allows_notes || step.allows_comments"
                class="mt-4 grid grid-cols-1 gap-4"
            >

                <!-- Internal notes -->
                <div v-if="step.allows_notes">
                    <label :for="`workflow-step-${step.id}-notes`" class="mb-1 block text-sm font-medium text-gray-700">
                        Notes
                    </label>

                    <textarea
                        :id="`workflow-step-${step.id}-notes`"
                        v-model="step.form.notes"
                        :aria-describedby="`workflow-step-${step.id}-notes-count`"
                        rows="4"
                        maxlength="2500"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                    ></textarea>

                    <!-- Character counter -->
                    <div :id="`workflow-step-${step.id}-notes-count`" class="mt-1 text-xs text-gray-500" aria-live="polite">
                        {{ step.form.notes?.length || 0 }} / 2500 characters
                    </div>
                </div>

                <!-- User-facing comments -->
                <div v-if="step.allows_comments">
                    <label :for="`workflow-step-${step.id}-comments`" class="mb-1 block text-sm font-medium text-gray-700">
                        Comments
                    </label>

                    <textarea
                        :id="`workflow-step-${step.id}-comments`"
                        v-model="step.form.comments"
                        :aria-describedby="`workflow-step-${step.id}-comments-count`"
                        rows="3"
                        maxlength="2500"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                    ></textarea>

                    <!-- Character counter -->
                    <div :id="`workflow-step-${step.id}-comments-count`" class="mt-1 text-xs text-gray-500" aria-live="polite">
                        {{ step.form.comments?.length || 0 }} / 2500 characters
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
// Vue utilities used for reactive data handling
import { computed, watch, ref } from 'vue'

// Component props
const props = defineProps({

    // Workflow step definitions from the backend
    workflowSteps: {
        type: Array,
        required: true,
    },

    // Existing saved workflow events/history
    existingEvents: {
        type: Array,
        default: () => [],
    },

    // Available people list for assignment
    people: {
        type: Array,
        default: () => [],
    },

    // Parent v-model value
    modelValue: {
        type: Array,
        default: () => [],
    },
})

// Emit used for v-model updates
const emit = defineEmits(['update:modelValue'])

// Converts datetime values into a format compatible
// with HTML datetime-local inputs
function normalizeDateTime(value) {
    if (!value) return ''

    if (typeof value === 'string') {

        // datetime-local only needs YYYY-MM-DDTHH:MM
        return value.length >= 16 ? value.slice(0, 16) : value
    }

    return ''
}

// Builds a lookup map of existing workflow events
// keyed by workflow_step_id for easier access
function buildEventMap(events) {
    const map = {}

    for (const event of events || []) {
        map[event.workflow_step_id] = {
            workflow_step_id: event.workflow_step_id,
            status_code: event.status_code ?? '',
            requested_at: normalizeDateTime(event.requested_at),
            scheduled_at: normalizeDateTime(event.scheduled_at),
            completed_at: normalizeDateTime(event.completed_at),
            performed_by_person_id: event.performed_by_person_id ?? '',
            notes: event.notes ?? '',
            comments: event.comments ?? '',
        }
    }

    return map
}

// Local reactive copy of workflow steps and form data
const localSteps = ref([])

// Builds the local editable workflow step structure
function buildLocalSteps() {

    // Convert existing events into an indexed map
    const existingMap = buildEventMap(props.existingEvents)

    // Merge workflow step definitions with existing event data
    localSteps.value = (props.workflowSteps || []).map((step) => {

        // Existing saved event for this step
        const existing = existingMap[step.id] || null

        return {
            ...step,

            // Form data bound to the UI
            form: {
                workflow_step_id: step.id,

                // Use existing value first, otherwise use default status
                status_code: existing?.status_code ?? step.default_status ?? '',

                requested_at: existing?.requested_at ?? '',
                scheduled_at: existing?.scheduled_at ?? '',
                completed_at: existing?.completed_at ?? '',
                performed_by_person_id: existing?.performed_by_person_id ?? '',
                notes: existing?.notes ?? '',
                comments: existing?.comments ?? '',
            },
        }
    })
}

// Determines if the "Performed By" dropdown should display
function showPerformedBy(step) {
    return (
        step.allows_requested_at ||
        step.allows_scheduled_at ||
        step.allows_completed_at ||
        step.allows_notes ||
        step.allows_comments
    )
}

// Creates the outgoing payload structure sent back to the parent component
const outgoingValue = computed(() =>
    localSteps.value.map((step) => ({
        workflow_step_id: step.form.workflow_step_id,

        // Empty values are converted to null before submission
        status_code: step.form.status_code || null,
        requested_at: step.form.requested_at || null,
        scheduled_at: step.form.scheduled_at || null,
        completed_at: step.form.completed_at || null,
        performed_by_person_id: step.form.performed_by_person_id || null,
        notes: step.form.notes || null,
        comments: step.form.comments || null,
    }))
)

// Rebuild local step data whenever workflow steps
// or existing events change
watch(
    () => [props.workflowSteps, props.existingEvents],
    () => {
        buildLocalSteps()
    },
    { immediate: true, deep: true }
)

// Emit updated workflow event data back to the parent component
watch(
    outgoingValue,
    (value) => {
        emit('update:modelValue', value)
    },
    { immediate: true, deep: true }
)
</script>