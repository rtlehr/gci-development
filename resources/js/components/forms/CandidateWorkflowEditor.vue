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
import { ref, watch } from 'vue'

const props = defineProps({
    workflowSteps: {
        type: Array,
        required: true,
    },
    existingEvents: {
        type: Array,
        default: () => [],
    },
    people: {
        type: Array,
        default: () => [],
    },
    modelValue: {
        type: Array,
        default: () => [],
    },
})

const emit = defineEmits(['update:modelValue'])

// Expose the editor's current state so the parent can read the exact
// workflow payload immediately before submitting the Inertia form.
// This avoids relying on watcher timing for persistence.

function normalizeDateTime(value) {
    if (!value || typeof value !== 'string') return ''

    return value.length >= 16 ? value.slice(0, 16) : value
}

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

function normalizeSteps(workflowSteps, events) {
    const eventMap = buildEventMap(events)

    return (workflowSteps || []).map((step) => {
        const existing = eventMap[step.id] || null

        return {
            ...step,
            form: {
                workflow_step_id: step.id,
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

function serializeSteps(steps) {
    return (steps || []).map((step) => ({
        workflow_step_id: step.form.workflow_step_id,
        status_code: step.form.status_code || null,
        requested_at: step.form.requested_at || null,
        scheduled_at: step.form.scheduled_at || null,
        completed_at: step.form.completed_at || null,
        performed_by_person_id: step.form.performed_by_person_id || null,
        notes: step.form.notes || null,
        comments: step.form.comments || null,
    }))
}

function sourceEvents() {
    return props.modelValue?.length ? props.modelValue : props.existingEvents
}

const localSteps = ref(normalizeSteps(props.workflowSteps, sourceEvents()))
let syncingFromParent = false

function showPerformedBy(step) {
    return (
        step.allows_requested_at ||
        step.allows_scheduled_at ||
        step.allows_completed_at ||
        step.allows_notes ||
        step.allows_comments
    )
}

watch(
    () => [props.workflowSteps, props.existingEvents],
    () => {
        const incoming = serializeSteps(normalizeSteps(props.workflowSteps, sourceEvents()))
        const current = serializeSteps(localSteps.value)

        if (JSON.stringify(incoming) === JSON.stringify(current)) return

        syncingFromParent = true
        localSteps.value = normalizeSteps(props.workflowSteps, sourceEvents())
        syncingFromParent = false
    },
    { deep: true }
)

watch(
    () => props.modelValue,
    (newValue) => {
        if (!newValue?.length) return

        const incoming = serializeSteps(normalizeSteps(props.workflowSteps, newValue))
        const current = serializeSteps(localSteps.value)

        if (JSON.stringify(incoming) === JSON.stringify(current)) return

        syncingFromParent = true
        localSteps.value = normalizeSteps(props.workflowSteps, newValue)
        syncingFromParent = false
    },
    { deep: true }
)

defineExpose({
    getValue: () => serializeSteps(localSteps.value),
})

watch(
    localSteps,
    (newValue) => {
        if (syncingFromParent) return

        emit('update:modelValue', serializeSteps(newValue))
    },
    { deep: true, immediate: true, flush: 'sync' }
)
</script>
