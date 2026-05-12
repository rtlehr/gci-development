<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'

// Existing role data, grouped permissions,
// and currently selected permission IDs from the backend.
const props = defineProps({
    role: {
        type: Object,
        required: true,
    },
    permissionGroups: {
        type: Array,
        default: () => [],
    },
    selectedPermissions: {
        type: Array,
        default: () => [],
    },
})

// Reactive Inertia form state initialized
// with the existing role values.
const form = useForm({
    name: props.role.name ?? '',
    label: props.role.label ?? '',
    description: props.role.description ?? '',
    permissions: [...props.selectedPermissions],
})

/**
 * Adds or removes a permission ID
 * from the selected permissions array.
 *
 * @param {number|string} permissionId
 */
const togglePermission = (permissionId) => {
    if (form.permissions.includes(permissionId)) {
        form.permissions = form.permissions.filter((id) => id !== permissionId)
    } else {
        form.permissions.push(permissionId)
    }
}

/**
 * Performs basic client-side validation
 * before submitting the updated role to the backend.
 */
function submit() {
    form.clearErrors()

    let hasError = false

    // Role name is required before submit.
    if (!form.name || form.name.trim() === '') {
        form.setError('name', 'Role name is required.')
        hasError = true
    }

    if (hasError) return

    form.put(`/admin/roles/${props.role.id}`)
}
</script>

<template>
    <div class="p-6 max-w-4xl space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold">Edit Role</h1>
                <p class="text-sm text-muted-foreground mt-1">
                    Update this role and its permissions.
                </p>
            </div>

            <Link href="/admin/roles">
                <Button variant="outline">Back to List</Button>
            </Link>
        </div>

        <div class="border rounded-xl p-6 bg-background">
            <form @submit.prevent="submit" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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
                </div>

                <div class="space-y-2">
                    <Label for="description">Description</Label>
                    <Textarea
                        id="description"
                        v-model="form.description"
                        rows="4"
                        :class="form.errors.description ? 'border-red-500' : ''"
                    />
                    <p v-if="form.errors.description" class="text-sm text-red-500">
                        {{ form.errors.description }}
                    </p>
                </div>

                <div class="space-y-6">
                    <div>
                        <h2 class="text-lg font-semibold">Role Permissions</h2>
                        <p class="text-sm text-muted-foreground">
                            Choose which permissions this role grants.
                        </p>
                    </div>

                    <div
                        v-for="group in permissionGroups"
                        :key="group.group"
                        class="space-y-4"
                    >
                        <div>
                            <h3 class="text-base font-semibold">{{ group.group }}</h3>
                            <p class="text-sm text-muted-foreground">
                                {{ group.group }} permissions.
                            </p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div
                                v-for="permission in group.permissions"
                                :key="permission.id"
                                class="flex items-start gap-3 p-3 border rounded-lg hover:bg-muted/50 transition"
                            >
                                <input
                                    type="checkbox"
                                    class="mt-1"
                                    :checked="form.permissions.includes(permission.id)"
                                    @change="togglePermission(permission.id)"
                                />

                                <div>
                                    <p class="font-medium">
                                        {{ permission.label || permission.name }}
                                    </p>
                                    <p class="text-xs text-muted-foreground">
                                        {{ permission.name }}
                                    </p>
                                    <p
                                        v-if="permission.description"
                                        class="text-xs text-muted-foreground"
                                    >
                                        {{ permission.description }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex gap-3">
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Saving...' : 'Save Changes' }}
                    </Button>

                    <Link href="/admin/roles">
                        <Button type="button" variant="outline">Cancel</Button>
                    </Link>
                </div>
            </form>
        </div>
    </div>
</template>