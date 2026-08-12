<template>
    <div class="p-6 space-y-6">
        <div class="rounded-2xl border bg-background p-6 shadow-sm">
            <h1 class="text-2xl font-semibold">Create Workflow</h1>
            <p class="mt-1 text-sm text-muted-foreground">
                Create a workflow and define its steps and statuses.
            </p>
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
                        <div v-if="form.errors.name" class="mt-1 text-sm text-destructive">
                            {{ form.errors.name }}
                        </div>
                    </div>

                    <div>
                        <Label>Code</Label>
                        <Input v-model="form.code" />
                        <div v-if="form.errors.code" class="mt-1 text-sm text-destructive">
                            {{ form.errors.code }}
                        </div>
                    </div>

                    <div class="md:col-span-2 xl:col-span-3">
                        <Label>Description</Label>
                        <textarea
                            v-model="form.description"
                            rows="4"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        ></textarea>
                        <div v-if="form.errors.description" class="mt-1 text-sm text-destructive">
                            {{ form.errors.description }}
                        </div>
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
                        Add steps and reorder them as needed.
                    </p>
                </div>

                <WorkflowStepsEditor v-model="form.steps" />

                <div v-if="form.errors.steps" class="text-sm text-destructive">
                    {{ form.errors.steps }}
                </div>
            </div>

            <div class="flex gap-2">
                <Button type="submit" :disabled="form.processing">
                    Save Workflow
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

// Reactive Inertia form state.
// Stores workflow details and the dynamic workflow steps collection.
const form = useForm({
    name: '',
    code: '',
    description: '',
    is_active: true,
    is_primary: false,
    steps: [],
})

/**
 * Submits the new workflow record
 * and its related workflow steps
 * to the backend create endpoint.
 */
function submit() {
    form.post('/workflows')
}
</script>