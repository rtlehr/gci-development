<template>
    <div class="p-6 space-y-6">
        <div class="rounded-2xl border bg-background p-6 shadow-sm">
            <h1 class="text-2xl font-semibold">Edit Workflow</h1>
            <p class="mt-1 text-sm text-muted-foreground">
                Update workflow details, steps, statuses, and order.
            </p>
        </div>

        <div class="rounded-2xl border bg-white p-4 shadow-sm">
            <div class="text-sm text-gray-500">Workflow</div>
            <div class="text-base font-medium text-gray-900">
                {{ workflow?.name || '—' }}
            </div>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <div class="rounded-2xl border bg-background p-6 shadow-sm space-y-4">
                <div>
                    <h2 class="text-lg font-semibold">Workflow Details</h2>
                    <p class="text-sm text-muted-foreground">
                        Main workflow settings.
                    </p>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <FormField label="Name" for-id="workflow-name" :error="form.errors.name" required>
                        <template #default="{ describedBy, invalid }">
                            <Input
                                id="workflow-name"
                                v-model="form.name"
                                :aria-describedby="describedBy"
                                :aria-invalid="invalid"
                                required
                            />
                        </template>
                    </FormField>

                    <FormField label="Code" for-id="workflow-code" :error="form.errors.code" required>
                        <template #default="{ describedBy, invalid }">
                            <Input
                                id="workflow-code"
                                v-model="form.code"
                                :aria-describedby="describedBy"
                                :aria-invalid="invalid"
                                required
                            />
                        </template>
                    </FormField>

                    <FormField
                        label="Description"
                        for-id="workflow-description"
                        :error="form.errors.description"
                        class="md:col-span-2 xl:col-span-3"
                    >
                        <template #default="{ describedBy, invalid }">
                            <textarea
                                id="workflow-description"
                                v-model="form.description"
                                rows="4"
                                class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                :aria-describedby="describedBy"
                                :aria-invalid="invalid"
                            ></textarea>
                        </template>
                    </FormField>
                </div>

                <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                    <label class="flex items-center gap-2 text-sm">
                        <input id="workflow-is-active" v-model="form.is_active" type="checkbox" />
                        Active Workflow
                    </label>

                    <label class="flex items-center gap-2 text-sm">
                        <input id="workflow-is-primary" v-model="form.is_primary" type="checkbox" />
                        Primary Workflow
                    </label>
                </div>
            </div>

            <div class="rounded-2xl border bg-background p-6 shadow-sm space-y-4">
                <div>
                    <h2 class="text-lg font-semibold">Workflow Steps</h2>
                    <p class="text-sm text-muted-foreground">
                        Add, edit, and reorder workflow steps.
                    </p>
                </div>

                <WorkflowStepsEditor v-model="form.steps" />
                <div v-if="form.errors.steps" class="text-sm text-destructive" role="alert">{{ form.errors.steps }}</div>
            </div>

            <div class="flex gap-2">
                <Button type="submit" :disabled="form.processing">
                    Update Workflow
                </Button>

                <Button as-child variant="outline"><Link href="/workflows">Cancel</Link></Button>
            </div>
        </form>
    </div>
</template>

<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import WorkflowStepsEditor from '@/components/forms/WorkflowStepsEditor.vue'
import FormField from '@/components/forms/FormField.vue'

// Existing workflow record passed from the backend.
const props = defineProps({
    workflow: {
        type: Object,
        required: true,
    },
})

// Reactive Inertia form state initialized
// with the existing workflow values and steps.
const form = useForm({
    name: props.workflow.name ?? '',
    code: props.workflow.code ?? '',
    description: props.workflow.description ?? '',
    is_active: !!props.workflow.is_active,
    is_primary: !!props.workflow.is_primary,
    steps: props.workflow.steps ?? [],
})

/**
 * Submits the updated workflow record
 * and related workflow steps
 * to the backend update endpoint.
 */
function submit() {
    form.put(`/workflows/${props.workflow.id}`)
}
</script>