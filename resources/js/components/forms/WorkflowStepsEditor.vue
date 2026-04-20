<template>
    <div class="space-y-6">
        <div
            v-for="(step, index) in localSteps"
            :key="step.local_id"
            class="rounded-2xl border bg-white p-5 shadow-sm space-y-4"
        >
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">
                        Step {{ index + 1 }}
                    </h3>
                    <p class="text-sm text-gray-500">
                        Configure this workflow step and its allowed statuses.
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <button
                        type="button"
                        class="rounded border px-3 py-1 text-sm"
                        :disabled="index === 0"
                        @click="moveStepUp(index)"
                    >
                        Up
                    </button>

                    <button
                        type="button"
                        class="rounded border px-3 py-1 text-sm"
                        :disabled="index === localSteps.length - 1"
                        @click="moveStepDown(index)"
                    >
                        Down
                    </button>

                    <button
                        type="button"
                        class="rounded border px-3 py-1 text-sm"
                        @click="removeStep(index)"
                    >
                        Remove
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                <div>
                    <label class="mb-1 block text-sm font-medium">Step Name</label>
                    <input v-model="step.name" class="w-full rounded border px-3 py-2 text-sm" />
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">Step Code</label>
                    <input v-model="step.code" class="w-full rounded border px-3 py-2 text-sm" />
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">Default Status</label>
                    <input v-model="step.default_status" class="w-full rounded border px-3 py-2 text-sm" />
                </div>
            </div>

            <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4">
                <label class="flex items-center gap-2 text-sm">
                    <input v-model="step.is_active" type="checkbox" />
                    Active
                </label>

                <label class="flex items-center gap-2 text-sm">
                    <input v-model="step.allows_requested_at" type="checkbox" />
                    Requested Date
                </label>

                <label class="flex items-center gap-2 text-sm">
                    <input v-model="step.allows_scheduled_at" type="checkbox" />
                    Scheduled Date
                </label>

                <label class="flex items-center gap-2 text-sm">
                    <input v-model="step.allows_completed_at" type="checkbox" />
                    Completed Date
                </label>

                <label class="flex items-center gap-2 text-sm">
                    <input v-model="step.allows_notes" type="checkbox" />
                    Notes
                </label>

                <label class="flex items-center gap-2 text-sm">
                    <input v-model="step.allows_comments" type="checkbox" />
                    Comments
                </label>

                <label class="flex items-center gap-2 text-sm">
                    <input v-model="step.allows_status" type="checkbox" />
                    Status
                </label>
            </div>

            <div class="rounded-xl border p-4 space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h4 class="font-medium">Step Statuses</h4>
                        <p class="text-sm text-gray-500">
                            Add allowed statuses for this step.
                        </p>
                    </div>

                    <button type="button" class="rounded border px-3 py-1 text-sm" @click="addStatus(step)">
                        Add Status
                    </button>
                </div>

                <div v-if="!step.statuses.length" class="text-sm text-gray-500">
                    No statuses added.
                </div>

                <div
                    v-for="(status, statusIndex) in step.statuses"
                    :key="status.local_id"
                    class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end rounded-lg border p-3"
                >
                    <div class="md:col-span-4">
                        <label class="mb-1 block text-sm font-medium">Status Label</label>
                        <input v-model="status.status_label" class="w-full rounded border px-3 py-2 text-sm" />
                    </div>

                    <div class="md:col-span-4">
                        <label class="mb-1 block text-sm font-medium">Status Code</label>
                        <input v-model="status.status_code" class="w-full rounded border px-3 py-2 text-sm" />
                    </div>

                    <div class="md:col-span-2">
                        <label class="flex items-center gap-2 text-sm h-10">
                            <input
                                v-model="status.is_default"
                                type="checkbox"
                                @change="makeStatusDefault(step, statusIndex)"
                            />
                            Default
                        </label>
                    </div>

                    <div class="md:col-span-2 flex justify-end">
                        <button
                            type="button"
                            class="rounded border px-3 py-1 text-sm"
                            @click="removeStatus(step, statusIndex)"
                        >
                            Remove
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div>
            <button type="button" class="rounded border px-3 py-2 text-sm" @click="addStep">
                Add Step
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
    modelValue: {
        type: Array,
        default: () => [],
    },
})

const emit = defineEmits(['update:modelValue'])

function makeLocalId(prefix = 'tmp') {
    return `${prefix}-${Math.random().toString(36).slice(2, 11)}`
}

function normalizeSteps(steps) {
    return (steps || []).map((step, index) => ({
        id: step.id ?? null,
        local_id: step.local_id ?? makeLocalId('step'),
        name: step.name ?? '',
        code: step.code ?? '',
        step_order: step.step_order ?? index + 1,
        is_active: step.is_active ?? true,
        allows_requested_at: !!step.allows_requested_at,
        allows_scheduled_at: !!step.allows_scheduled_at,
        allows_completed_at: !!step.allows_completed_at,
        allows_notes: !!step.allows_notes,
        allows_comments: !!step.allows_comments,
        allows_status: !!step.allows_status,
        default_status: step.default_status ?? '',
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

function serializeSteps(steps) {
    return steps.map((step, index) => ({
        id: step.id,
        name: step.name,
        code: step.code,
        step_order: index + 1,
        is_active: step.is_active,
        allows_requested_at: step.allows_requested_at,
        allows_scheduled_at: step.allows_scheduled_at,
        allows_completed_at: step.allows_completed_at,
        allows_notes: step.allows_notes,
        allows_comments: step.allows_comments,
        allows_status: step.allows_status,
        default_status: step.default_status || null,
        statuses: step.statuses.map((status, statusIndex) => ({
            id: status.id,
            status_code: status.status_code,
            status_label: status.status_label,
            is_default: status.is_default,
            is_active: status.is_active,
            sort_order: statusIndex + 1,
        })),
    }))
}

const localSteps = ref(normalizeSteps(props.modelValue))
let isSyncingFromParent = false

watch(
    () => props.modelValue,
    (newValue) => {
        const incoming = JSON.stringify(newValue || [])
        const current = JSON.stringify(serializeSteps(localSteps.value))

        if (incoming !== current) {
            isSyncingFromParent = true
            localSteps.value = normalizeSteps(newValue)
            isSyncingFromParent = false
        }
    },
    { deep: true }
)

watch(
    localSteps,
    (newValue) => {
        if (isSyncingFromParent) return
        emit('update:modelValue', serializeSteps(newValue))
    },
    { deep: true }
)

function addStep() {
    localSteps.value.push({
        id: null,
        local_id: makeLocalId('step'),
        name: '',
        code: '',
        step_order: localSteps.value.length + 1,
        is_active: true,
        allows_requested_at: false,
        allows_scheduled_at: false,
        allows_completed_at: false,
        allows_notes: false,
        allows_comments: false,
        allows_status: false,
        default_status: '',
        statuses: [],
    })
}

function removeStep(index) {
    localSteps.value.splice(index, 1)
}

function moveStepUp(index) {
    if (index <= 0) return
    const temp = localSteps.value[index - 1]
    localSteps.value[index - 1] = localSteps.value[index]
    localSteps.value[index] = temp
}

function moveStepDown(index) {
    if (index >= localSteps.value.length - 1) return
    const temp = localSteps.value[index + 1]
    localSteps.value[index + 1] = localSteps.value[index]
    localSteps.value[index] = temp
}

function addStatus(step) {
    step.statuses.push({
        id: null,
        local_id: makeLocalId('status'),
        status_code: '',
        status_label: '',
        is_default: step.statuses.length === 0,
        is_active: true,
        sort_order: step.statuses.length + 1,
    })
}

function removeStatus(step, statusIndex) {
    step.statuses.splice(statusIndex, 1)
}

function makeStatusDefault(step, selectedIndex) {
    step.statuses.forEach((status, index) => {
        status.is_default = index === selectedIndex
    })
}
</script>