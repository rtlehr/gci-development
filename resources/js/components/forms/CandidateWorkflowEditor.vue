<template>
    <div class="space-y-6">
        <div
            v-for="step in localSteps"
            :key="step.id"
            class="rounded-2xl border bg-white p-4 shadow-sm"
        >
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">
                        {{ step.name }}
                    </h3>
                    <p class="text-sm text-gray-500">
                        {{ step.code }}
                    </p>
                </div>

                <div class="text-sm text-gray-500">
                    Step {{ step.step_order }}
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div v-if="step.allows_status">
                    <label class="mb-1 block text-sm font-medium text-gray-700">
                        Status
                    </label>
                    <select
                        v-model="step.form.status_code"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                    >
                        <option value="">Select status</option>
                        <option
                            v-for="status in step.statuses"
                            :key="status.id"
                            :value="status.status_code"
                        >
                            {{ status.status_label }}
                        </option>
                    </select>
                </div>

                <div v-if="showPerformedBy(step)">
                    <label class="mb-1 block text-sm font-medium text-gray-700">
                        Performed By
                    </label>
                    <select
                        v-model="step.form.performed_by_person_id"
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
                </div>

                <div v-if="step.allows_requested_at">
                    <label class="mb-1 block text-sm font-medium text-gray-700">
                        Requested Date / Time
                    </label>
                    <input
                        v-model="step.form.requested_at"
                        type="datetime-local"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                    />
                </div>

                <div v-if="step.allows_scheduled_at">
                    <label class="mb-1 block text-sm font-medium text-gray-700">
                        Scheduled Date / Time
                    </label>
                    <input
                        v-model="step.form.scheduled_at"
                        type="datetime-local"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                    />
                </div>

                <div v-if="step.allows_completed_at">
                    <label class="mb-1 block text-sm font-medium text-gray-700">
                        Completed Date / Time
                    </label>
                    <input
                        v-model="step.form.completed_at"
                        type="datetime-local"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                    />
                </div>
            </div>

            <div
                v-if="step.allows_notes || step.allows_comments"
                class="mt-4 grid grid-cols-1 gap-4"
            >
                <div v-if="step.allows_notes">
                    <label class="mb-1 block text-sm font-medium text-gray-700">
                        Notes
                    </label>
                    <textarea
                        v-model="step.form.notes"
                        rows="4"
                        maxlength="2500"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                    ></textarea>
                    <div class="mt-1 text-xs text-gray-500">
                        {{ step.form.notes?.length || 0 }} / 2500
                    </div>
                </div>

                <div v-if="step.allows_comments">
                    <label class="mb-1 block text-sm font-medium text-gray-700">
                        Comments
                    </label>
                    <textarea
                        v-model="step.form.comments"
                        rows="3"
                        maxlength="2500"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                    ></textarea>
                    <div class="mt-1 text-xs text-gray-500">
                        {{ step.form.comments?.length || 0 }} / 2500
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, watch, ref } from 'vue'

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

function normalizeDateTime(value) {
    if (!value) return ''

    if (typeof value === 'string') {
        return value.length >= 16 ? value.slice(0, 16) : value
    }

    return ''
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

const localSteps = ref([])

function buildLocalSteps() {
    const existingMap = buildEventMap(props.existingEvents)

    localSteps.value = (props.workflowSteps || []).map((step) => {
        const existing = existingMap[step.id] || null

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

function showPerformedBy(step) {
    return (
        step.allows_requested_at ||
        step.allows_scheduled_at ||
        step.allows_completed_at ||
        step.allows_notes ||
        step.allows_comments
    )
}

const outgoingValue = computed(() =>
    localSteps.value.map((step) => ({
        workflow_step_id: step.form.workflow_step_id,
        status_code: step.form.status_code || null,
        requested_at: step.form.requested_at || null,
        scheduled_at: step.form.scheduled_at || null,
        completed_at: step.form.completed_at || null,
        performed_by_person_id: step.form.performed_by_person_id || null,
        notes: step.form.notes || null,
        comments: step.form.comments || null,
    }))
)

watch(
    () => [props.workflowSteps, props.existingEvents],
    () => {
        buildLocalSteps()
    },
    { immediate: true, deep: true }
)

watch(
    outgoingValue,
    (value) => {
        emit('update:modelValue', value)
    },
    { immediate: true, deep: true }
)
</script>