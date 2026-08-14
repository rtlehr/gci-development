<template>
    <!-- Main wrapper for workflow step configuration -->
    <div class="space-y-6">
        <p class="sr-only" role="status" aria-live="polite">{{ announcement }}</p>

        <!-- Loop through all workflow steps -->
        <div
            v-for="(step, index) in localSteps"
            :key="step.local_id"
            class="rounded-2xl border bg-white p-5 shadow-sm space-y-4"
        >
            <!-- Step header -->
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div>

                    <!-- Step title -->
                    <h3 class="text-lg font-semibold text-gray-900">
                        Step {{ index + 1 }}
                    </h3>

                    <!-- Step description -->
                    <p class="text-sm text-gray-500">
                        Configure this workflow step and its allowed statuses.
                    </p>
                </div>

                <!-- Step management buttons -->
                <div class="flex flex-wrap gap-2">

                    <!-- Move step upward in workflow -->
                    <button
                        type="button"
                        class="rounded border px-3 py-1 text-sm"
                        :disabled="index === 0"
                        :aria-label="`Move ${step.name || `step ${index + 1}`} up`"
                        @click="moveStepUp(index)"
                    >
                        Up
                    </button>

                    <!-- Move step downward in workflow -->
                    <button
                        type="button"
                        class="rounded border px-3 py-1 text-sm"
                        :disabled="index === localSteps.length - 1"
                        :aria-label="`Move ${step.name || `step ${index + 1}`} down`"
                        @click="moveStepDown(index)"
                    >
                        Down
                    </button>

                    <!-- Remove workflow step -->
                    <button
                        type="button"
                        class="rounded border px-3 py-1 text-sm"
                        :aria-label="`Remove ${step.name || `step ${index + 1}`}`"
                        @click="removeStep(index)"
                    >
                        Remove
                    </button>
                </div>
            </div>

            <!-- Main workflow step fields -->
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">

                <!-- Step Name -->
                <div>
                    <label :for="`workflow-${step.local_id}-step-name`" class="mb-1 block text-sm font-medium">Step Name</label>

                    <input
                        :id="`workflow-${step.local_id}-step-name`"
                        v-model="step.name"
                        class="w-full rounded border px-3 py-2 text-sm"
                    />
                </div>

                <!-- Step Code -->
                <div>
                    <label :for="`workflow-${step.local_id}-step-code`" class="mb-1 block text-sm font-medium">Step Code</label>

                    <input
                        :id="`workflow-${step.local_id}-step-code`"
                        v-model="step.code"
                        class="w-full rounded border px-3 py-2 text-sm"
                    />
                </div>

                <!-- Default Status -->
                <div>
                    <label :for="`workflow-${step.local_id}-default-status`" class="mb-1 block text-sm font-medium">Default Status</label>

                    <input
                        :id="`workflow-${step.local_id}-default-status`"
                        v-model="step.default_status"
                        class="w-full rounded border px-3 py-2 text-sm"
                    />
                </div>
            </div>

            <!-- Workflow step behavior toggles -->
            <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4">

                <!-- Step is active -->
                <label class="flex items-center gap-2 text-sm">
                    <input :id="`workflow-${step.local_id}-active`" v-model="step.is_active" type="checkbox" />
                    Active
                </label>

                <!-- Allow requested date field -->
                <label class="flex items-center gap-2 text-sm">
                    <input :id="`workflow-${step.local_id}-requested`" v-model="step.allows_requested_at" type="checkbox" />
                    Requested Date
                </label>

                <!-- Allow scheduled date field -->
                <label class="flex items-center gap-2 text-sm">
                    <input :id="`workflow-${step.local_id}-scheduled`" v-model="step.allows_scheduled_at" type="checkbox" />
                    Scheduled Date
                </label>

                <!-- Allow completed date field -->
                <label class="flex items-center gap-2 text-sm">
                    <input :id="`workflow-${step.local_id}-completed`" v-model="step.allows_completed_at" type="checkbox" />
                    Completed Date
                </label>

                <!-- Allow notes field -->
                <label class="flex items-center gap-2 text-sm">
                    <input :id="`workflow-${step.local_id}-notes`" v-model="step.allows_notes" type="checkbox" />
                    Notes
                </label>

                <!-- Allow comments field -->
                <label class="flex items-center gap-2 text-sm">
                    <input :id="`workflow-${step.local_id}-comments`" v-model="step.allows_comments" type="checkbox" />
                    Comments
                </label>

                <!-- Allow status selection -->
                <label class="flex items-center gap-2 text-sm">
                    <input :id="`workflow-${step.local_id}-status`" v-model="step.allows_status" type="checkbox" />
                    Status
                </label>
            </div>

            <!-- Step Statuses Section -->
            <div class="rounded-xl border p-4 space-y-4">

                <!-- Status section header -->
                <div class="flex items-center justify-between">
                    <div>

                        <!-- Status section title -->
                        <h4 class="font-medium">Step Statuses</h4>

                        <!-- Status section description -->
                        <p class="text-sm text-gray-500">
                            Add allowed statuses for this step.
                        </p>
                    </div>

                    <!-- Add new status -->
                    <button
                        type="button"
                        class="rounded border px-3 py-1 text-sm"
                        @click="addStatus(step)"
                    >
                        Add Status
                    </button>
                </div>

                <!-- Empty state -->
                <div v-if="!step.statuses.length" class="text-sm text-gray-500">
                    No statuses added.
                </div>

                <!-- Loop through step statuses -->
                <div
                    v-for="(status, statusIndex) in step.statuses"
                    :key="status.local_id"
                    class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end rounded-lg border p-3"
                >

                    <!-- Status Label -->
                    <div class="md:col-span-4">
                        <label :for="`workflow-${step.local_id}-${status.local_id}-label`" class="mb-1 block text-sm font-medium">Status Label</label>

                        <input
                            :id="`workflow-${step.local_id}-${status.local_id}-label`"
                            v-model="status.status_label"
                            class="w-full rounded border px-3 py-2 text-sm"
                        />
                    </div>

                    <!-- Status Code -->
                    <div class="md:col-span-4">
                        <label :for="`workflow-${step.local_id}-${status.local_id}-code`" class="mb-1 block text-sm font-medium">Status Code</label>

                        <input
                            :id="`workflow-${step.local_id}-${status.local_id}-code`"
                            v-model="status.status_code"
                            class="w-full rounded border px-3 py-2 text-sm"
                        />
                    </div>

                    <!-- Default Status Toggle -->
                    <div class="md:col-span-2">
                        <label class="flex items-center gap-2 text-sm h-10">

                            <!-- Only one status should be default -->
                            <input
                                :id="`workflow-${step.local_id}-${status.local_id}-default`"
                                v-model="status.is_default"
                                type="checkbox"
                                @change="makeStatusDefault(step, statusIndex)"
                            />

                            Default
                        </label>
                    </div>

                    <!-- Remove status button -->
                    <div class="md:col-span-2 flex justify-end">
                        <button
                            type="button"
                            class="rounded border px-3 py-1 text-sm"
                            :aria-label="`Remove status ${status.status_label || statusIndex + 1} from ${step.name || `step ${index + 1}`}`"
                            @click="removeStatus(step, statusIndex)"
                        >
                            Remove
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add workflow step button -->
        <div>
            <button
                type="button"
                class="rounded border px-3 py-2 text-sm"
                @click="addStep"
            >
                Add Step
            </button>
        </div>
    </div>
</template>

<script setup>
// Vue reactive helpers
import { ref, watch } from 'vue'

// Component props
const props = defineProps({

    // Parent v-model data containing workflow steps
    modelValue: {
        type: Array,
        default: () => [],
    },
})

// Emit used for v-model updates
const emit = defineEmits(['update:modelValue'])

// Generates a temporary unique ID used for Vue keys
// before records exist in the database
function makeLocalId(prefix = 'tmp') {
    return `${prefix}-${Math.random().toString(36).slice(2, 11)}`
}

// Normalizes incoming workflow step data
// into a consistent local editable structure
function normalizeSteps(steps) {
    return (steps || []).map((step, index) => ({
        id: step.id ?? null,

        // Local ID used for frontend tracking
        local_id: step.local_id ?? makeLocalId('step'),

        name: step.name ?? '',
        code: step.code ?? '',

        // Default step order based on array position
        step_order: step.step_order ?? index + 1,

        is_active: step.is_active ?? true,

        // Normalize boolean values
        allows_requested_at: !!step.allows_requested_at,
        allows_scheduled_at: !!step.allows_scheduled_at,
        allows_completed_at: !!step.allows_completed_at,
        allows_notes: !!step.allows_notes,
        allows_comments: !!step.allows_comments,
        allows_status: !!step.allows_status,

        default_status: step.default_status ?? '',

        // Normalize workflow statuses
        statuses: (step.statuses || []).map((status) => ({
            id: status.id ?? null,
            local_id: status.local_id ?? makeLocalId('status'),
            status_code: status.status_code ?? '',
            status_label: status.status_label ?? '',
            is_default: !!status.is_default,
            is_active: status.is_active ?? true,
            sort_order: status.sort_order ?? 0,
        })),
    }))
}

// Converts local editable data into a clean payload
// ready for backend submission
function serializeSteps(steps) {
    return steps.map((step, index) => ({
        id: step.id,
        name: step.name,
        code: step.code,

        // Recalculate step order from current array position
        step_order: index + 1,

        is_active: step.is_active,
        allows_requested_at: step.allows_requested_at,
        allows_scheduled_at: step.allows_scheduled_at,
        allows_completed_at: step.allows_completed_at,
        allows_notes: step.allows_notes,
        allows_comments: step.allows_comments,
        allows_status: step.allows_status,

        default_status: step.default_status || null,

        // Serialize statuses
        statuses: step.statuses.map((status, statusIndex) => ({
            id: status.id,
            status_code: status.status_code,
            status_label: status.status_label,
            is_default: status.is_default,
            is_active: status.is_active,

            // Recalculate sort order
            sort_order: statusIndex + 1,
        })),
    }))
}

// Local reactive workflow step collection
const localSteps = ref(normalizeSteps(props.modelValue))
const announcement = ref('')

// Prevents circular update loops between parent and child
let isSyncingFromParent = false

// Watch for incoming parent updates
watch(
    () => props.modelValue,
    (newValue) => {

        // Compare serialized versions to avoid unnecessary refreshes
        const incoming = JSON.stringify(newValue || [])
        const current = JSON.stringify(serializeSteps(localSteps.value))

        if (incoming !== current) {
            isSyncingFromParent = true

            // Replace local state with normalized incoming data
            localSteps.value = normalizeSteps(newValue)

            isSyncingFromParent = false
        }
    },
    { deep: true }
)

// Watch local data changes and emit updates back to parent
watch(
    localSteps,
    (newValue) => {

        // Skip updates while syncing from parent
        if (isSyncingFromParent) return

        emit('update:modelValue', serializeSteps(newValue))
    },
    { deep: true }
)

// Adds a new workflow step
function addStep() {
    localSteps.value.push({
        id: null,
        local_id: makeLocalId('step'),

        name: '',
        code: '',

        // Default order is current length + 1
        step_order: localSteps.value.length + 1,

        is_active: true,

        // Default feature toggles
        allows_requested_at: false,
        allows_scheduled_at: false,
        allows_completed_at: false,
        allows_notes: false,
        allows_comments: false,
        allows_status: false,

        default_status: '',

        // New step starts with no statuses
        statuses: [],
    })
    announcement.value = `Added workflow step ${localSteps.value.length}.`
}

// Removes a workflow step
function removeStep(index) {
    const stepName = localSteps.value[index]?.name || `step ${index + 1}`
    localSteps.value.splice(index, 1)
    announcement.value = `Removed ${stepName}.`
}

// Moves a workflow step upward in the list
function moveStepUp(index) {

    // Prevent moving the first item higher
    if (index <= 0) return

    const temp = localSteps.value[index - 1]

    localSteps.value[index - 1] = localSteps.value[index]
    localSteps.value[index] = temp
    announcement.value = `Moved ${localSteps.value[index - 1]?.name || `step ${index + 1}`} up.`
}

// Moves a workflow step downward in the list
function moveStepDown(index) {

    // Prevent moving the last item lower
    if (index >= localSteps.value.length - 1) return

    const temp = localSteps.value[index + 1]

    localSteps.value[index + 1] = localSteps.value[index]
    localSteps.value[index] = temp
    announcement.value = `Moved ${localSteps.value[index + 1]?.name || `step ${index + 1}`} down.`
}

// Adds a new status to a workflow step
function addStatus(step) {
    step.statuses.push({
        id: null,
        local_id: makeLocalId('status'),

        status_code: '',
        status_label: '',

        // First status automatically becomes default
        is_default: step.statuses.length === 0,

        is_active: true,

        // Default sort order
        sort_order: step.statuses.length + 1,
    })
    announcement.value = `Added a status to ${step.name || 'workflow step'}.`
}

// Removes a status from a workflow step
function removeStatus(step, statusIndex) {
    const statusName = step.statuses[statusIndex]?.status_label || `status ${statusIndex + 1}`
    step.statuses.splice(statusIndex, 1)
    announcement.value = `Removed ${statusName} from ${step.name || 'workflow step'}.`
}

// Ensures only one status is marked as default
function makeStatusDefault(step, selectedIndex) {
    step.statuses.forEach((status, index) => {
        status.is_default = index === selectedIndex
    })
}
</script>