<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Label } from '@/components/ui/label'

const props = defineProps({
    user: Object,
    permissions: Array,
    selectedPermissions: Array,
})

const form = useForm({
    permissions: [...props.selectedPermissions],
})

const togglePermission = (permissionId) => {
    if (form.permissions.includes(permissionId)) {
        form.permissions = form.permissions.filter((id) => id !== permissionId)
    } else {
        form.permissions.push(permissionId)
    }
}

const submit = () => {
    form.put(`/admin/users/${props.user.id}/permissions`)
}
</script>

<template>
    <div class="p-6 max-w-4xl space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold">Edit Permissions</h1>
                <p class="text-sm text-muted-foreground mt-1">
                    Manage access for this user.
                </p>
            </div>

            <Link href="/admin/users">
                <Button variant="outline">Back to Users</Button>
            </Link>
        </div>

        <!-- Card -->
        <div class="border rounded-xl p-6 bg-background">
            <form @submit.prevent="submit" class="space-y-6">

                <!-- User Info -->
                <div class="space-y-1">
                    <p class="text-sm text-muted-foreground">User</p>
                    <p class="font-medium">
                        {{ user.name }}
                        <span v-if="user.email" class="text-muted-foreground">
                            ({{ user.email }})
                        </span>
                    </p>
                </div>

                <!-- Permissions Grid -->
                <div class="space-y-4">
                    <Label>Permissions</Label>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div
                            v-for="permission in permissions"
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

                <!-- Buttons -->
                <div class="flex gap-3">
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Saving...' : 'Save Permissions' }}
                    </Button>

                    <Link href="/admin/users">
                        <Button type="button" variant="outline">Cancel</Button>
                    </Link>
                </div>
            </form>
        </div>
    </div>
</template>