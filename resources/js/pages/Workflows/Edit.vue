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
                    <div>
                        <Label>Name</Label>
                        <Input v-model="form.name" />
                        <div v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</div>
                    </div>

                    <div>
                        <Label>Code</Label>
                        <Input v-model="form.code" />
                        <div v-if="form.errors.code" class="mt-1 text-sm text-red-600">{{ form.errors.code }}</div>
                    </div>

                    <div class="md:col-span-2 xl:col-span-3">
                        <Label>Description</Label>
                        <textarea
                            v-model="form.description"
                            rows="4"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        ></textarea>
                        <div v-if="form.errors.description" class="mt-1 text-sm text-red-600">{{ form.errors.description }}</div>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                    <label class="flex items-center gap-2 text-sm">
                        <input v-model="form.is_active" type="checkbox" />
                        Active Workflow
                    </label>

                    <label class="flex items-center gap-2 text-sm">
                        <input v-model="form.is_primary" type="checkbox" />
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
                <div v-if="form.errors.steps" class="text-sm text-red-600">{{ form.errors.steps }}</div>
            </div>

            <div class="flex gap-2">
                <Button type="submit" :disabled="form.processing">
                    Update Workflow
                </Button>

                <Link href="/workflows">
                    <Button type="button" variant="outline">Cancel</Button>
                </Link>
            </div>
        </form>
    </div>
</template>

<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import WorkflowStepsEditor from '@/components/forms/WorkflowStepsEditor.vue'

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