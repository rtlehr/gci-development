<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Label } from '@/components/ui/label'

const props = defineProps({
    user: Object,
    roles: Array,
    selectedRoles: Array,
    permissionGroups: Array,
    selectedPermissions: Array,
})

const form = useForm({
    roles: [...props.selectedRoles],
    permissions: [...props.selectedPermissions],
})

const toggleRole = (roleId) => {
    if (form.roles.includes(roleId)) {
        form.roles = form.roles.filter((id) => id !== roleId)
    } else {
        form.roles.push(roleId)
    }
}

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
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold">Edit Access</h1>
                <p class="text-sm text-muted-foreground mt-1">
                    Manage roles and direct permissions for this user.
                </p>
            </div>

            <Link href="/admin/users">
                <Button variant="outline">Back to Users</Button>
            </Link>
        </div>

        <div class="border rounded-xl p-6 bg-background">
            <form @submit.prevent="submit" class="space-y-8">
                <div class="space-y-1">
                    <p class="text-sm text-muted-foreground">User</p>
                    <p class="font-medium">
                        {{ user.name }}
                        <span v-if="user.email" class="text-muted-foreground">
                            ({{ user.email }})
                        </span>
                    </p>
                </div>

                <!-- Roles -->
                <div class="space-y-4">
                    <div>
                        <h2 class="text-lg font-semibold">Roles</h2>
                        <p class="text-sm text-muted-foreground">
                            Roles grant sets of permissions automatically.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div
                            v-for="role in roles"
                            :key="role.id"
                            class="flex items-start gap-3 p-3 border rounded-lg hover:bg-muted/50 transition"
                        >
                            <input
                                type="checkbox"
                                class="mt-1"
                                :checked="form.roles.includes(role.id)"
                                @change="toggleRole(role.id)"
                            />

                            <div>
                                <p class="font-medium">
                                    {{ role.label || role.name }}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    {{ role.name }}
                                </p>
                                <p
                                    v-if="role.description"
                                    class="text-xs text-muted-foreground"
                                >
                                    {{ role.description }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Direct Permissions -->
                <div class="space-y-6">
                    <div>
                        <h2 class="text-lg font-semibold">Direct Permissions</h2>
                        <p class="text-sm text-muted-foreground">
                            These permissions are assigned directly to the user in addition to any role-based permissions.
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
                                Manage {{ group.group.toLowerCase() }} permissions.
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
                        {{ form.processing ? 'Saving...' : 'Save Access' }}
                    </Button>

                    <Link href="/admin/users">
                        <Button type="button" variant="outline">Cancel</Button>
                    </Link>
                </div>
            </form>
        </div>
    </div>
</template>