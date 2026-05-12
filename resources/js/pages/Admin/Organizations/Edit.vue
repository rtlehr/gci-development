<script setup lang="ts">
import { useForm, Link } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'

// Existing organization record and parent organization options
// passed from the backend.
const props = defineProps<{
    organization: any
    parents: any[]
}>()

// Reactive Inertia form object.
// Starts with the existing organization values.
const form = useForm({
    name: props.organization.name,
    parent_id: props.organization.parent_id ?? 1,
    status: props.organization.status,
    notes: props.organization.notes ?? '',
})

/**
 * Submits the organization edit form
 * to the backend update endpoint.
 */
const submit = () => {
    form.put(`/admin/organizations/${props.organization.id}`)
}
</script>

<template>
    <div class="p-6 space-y-6">
        <div class="rounded-2xl border bg-background p-6 shadow-sm">
            <h1 class="text-2xl font-semibold">Edit Organization</h1>

            <p class="mt-1 text-sm text-muted-foreground">
                Update this organization.
            </p>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <div class="rounded-2xl border bg-background p-6 shadow-sm space-y-4">
                <div>
                    <Label for="name">Name</Label>

                    <Input
                        id="name"
                        v-model="form.name"
                        type="text"
                        class="mt-1"
                    />

                    <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">
                        {{ form.errors.name }}
                    </p>
                </div>

                <div>
                    <Label for="parent_id">Parent Organization</Label>

                    <select
                        id="parent_id"
                        v-model="form.parent_id"
                        class="mt-1 flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    >
                        <option v-for="p in parents" :key="p.id" :value="p.id">
                            {{ p.name }}
                        </option>
                    </select>

                    <p v-if="form.errors.parent_id" class="mt-1 text-sm text-red-600">
                        {{ form.errors.parent_id }}
                    </p>
                </div>

                <div>
                    <Label for="status">Status</Label>

                    <select
                        id="status"
                        v-model="form.status"
                        class="mt-1 flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    >
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>

                    <p v-if="form.errors.status" class="mt-1 text-sm text-red-600">
                        {{ form.errors.status }}
                    </p>
                </div>

                <div>
                    <Label for="notes">Notes</Label>

                    <textarea
                        id="notes"
                        v-model="form.notes"
                        rows="4"
                        class="mt-1 flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    ></textarea>

                    <p v-if="form.errors.notes" class="mt-1 text-sm text-red-600">
                        {{ form.errors.notes }}
                    </p>
                </div>
            </div>

            <div class="flex gap-2">
                <Button type="submit" :disabled="form.processing">
                    {{ form.processing ? 'Saving...' : 'Update Organization' }}
                </Button>

                <Link href="/admin/organizations">
                    <Button type="button" variant="outline">
                        Cancel
                    </Button>
                </Link>
            </div>
        </form>
    </div>
</template>