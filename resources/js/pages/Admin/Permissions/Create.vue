<template>
    <div class="p-6 max-w-4xl space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold">Create Permission</h1>
                <p class="text-sm text-muted-foreground mt-1">
                    Add a new permission definition.
                </p>
            </div>

            <Button as-child variant="outline"><Link href="/admin/permissions">Back to List</Link></Button>
        </div>

        <div class="border rounded-xl p-6 bg-background">
            <form @submit.prevent="submit" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <Label for="group_name">Group</Label>
                        <Input
                            id="group_name"
                            v-model="form.group_name"
                            :class="form.errors.group_name ? 'border-red-500' : ''"
                        />
                        <p v-if="form.errors.group_name" class="text-sm text-red-500">
                            {{ form.errors.group_name }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="label">Label</Label>
                        <Input
                            id="label"
                            v-model="form.label"
                            :class="form.errors.label ? 'border-red-500' : ''"
                        />
                        <p v-if="form.errors.label" class="text-sm text-red-500">
                            {{ form.errors.label }}
                        </p>
                    </div>
                </div>

                <div class="space-y-2">
                    <Label for="name">
                        Name <span class="text-red-500">*</span>
                    </Label>
                    <Input
                        id="name"
                        v-model="form.name"
                        :class="form.errors.name ? 'border-red-500' : ''"
                    />
                    <p v-if="form.errors.name" class="text-sm text-red-500">
                        {{ form.errors.name }}
                    </p>
                </div>

                <div class="space-y-2">
                    <Label for="description">Description</Label>
                    <Textarea
                        id="description"
                        v-model="form.description"
                        rows="5"
                        :class="form.errors.description ? 'border-red-500' : ''"
                    />
                    <p v-if="form.errors.description" class="text-sm text-red-500">
                        {{ form.errors.description }}
                    </p>
                </div>

                <div class="space-y-4">
                    <div>
                        <h2 class="text-lg font-semibold">Permission Flags</h2>
                        <p class="text-sm text-muted-foreground">
                            Control how this permission behaves in the system.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="flex items-start gap-3 p-3 border rounded-lg">
                            <input
                                id="is_system"
                                v-model="form.is_system"
                                type="checkbox"
                                class="mt-1"
                            />
                            <div>
                                <Label for="is_system">System Permission</Label>
                                <p class="text-xs text-muted-foreground">
                                    Marks this as a built-in system permission.
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3 p-3 border rounded-lg">
                            <input
                                id="is_locked"
                                v-model="form.is_locked"
                                type="checkbox"
                                class="mt-1"
                            />
                            <div>
                                <Label for="is_locked">Locked Permission</Label>
                                <p class="text-xs text-muted-foreground">
                                    Prevents this permission from being edited or deleted in the UI.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex gap-3">
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Saving...' : 'Create Permission' }}
                    </Button>

                    <Button as-child variant="outline"><Link href="/admin/permissions">Cancel</Link></Button>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'

// Reactive Inertia form state.
// Tracks field values, validation errors, and submission state.
const form = useForm({
    name: '',
    group_name: '',
    label: '',
    description: '',
    is_system: false,
    is_locked: false,
})

/**
 * Performs client-side validation before submitting
 * the new permission to the backend create endpoint.
 */
function submit() {
    form.clearErrors()

    let hasError = false

    // Permission name is required before submit.
    if (!form.name || form.name.trim() === '') {
        form.setError('name', 'Permission name is required.')
        hasError = true
    }

    if (hasError) return

    form.post('/admin/permissions')
}
</script>