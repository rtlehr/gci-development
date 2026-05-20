<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'

// Backend-provided user, role, and permission data.
const props = defineProps({
    user: Object,
    roles: Array,
    selectedRoles: Array,
    permissionGroups: Array,
    selectedPermissions: Array,
})

/**
 * Removes duplicate IDs from an array.
 *
 * @param {Array} ids
 * @returns {Array}
 */
const uniqueIds = (ids) => {
    return [...new Set(ids)]
}

/**
 * Returns all permission IDs assigned to a role.
 *
 * @param {Object} role
 * @returns {Array<number>}
 */
const getRolePermissionIds = (role) => {
    return (role.permissions || []).map((permission) => permission.id)
}

/**
 * Builds a combined list of permission IDs
 * from the currently selected roles.
 *
 * @returns {Array<number>}
 */
const getRolePermissionIdsFromSelectedRoles = () => {
    return props.roles
        .filter((role) => props.selectedRoles.includes(role.id))
        .flatMap((role) => getRolePermissionIds(role))
}

// Reactive Inertia form state.
// Initializes with selected roles and merged permission IDs.
const form = useForm({
    roles: [...props.selectedRoles],

    permissions: uniqueIds([
        ...props.selectedPermissions,
        ...getRolePermissionIdsFromSelectedRoles(),
    ]),
})

/**
 * Toggles a role on or off.
 *
 * When enabled:
 * - The role is added
 * - All role permissions are added
 *
 * When disabled:
 * - The role is removed
 * - All role permissions are removed
 *
 * Manual permission changes do not change role selections.
 *
 * @param {number|string} roleId
 */
const toggleRole = (roleId) => {
    const role = props.roles.find((item) => item.id === roleId)

    if (!role) {
        return
    }

    const rolePermissionIds = getRolePermissionIds(role)

    if (form.roles.includes(roleId)) {
        form.roles = form.roles.filter((id) => id !== roleId)

        const remainingRolePermissionIds = props.roles
            .filter((item) => form.roles.includes(item.id))
            .flatMap((item) => getRolePermissionIds(item))

        form.permissions = form.permissions.filter((permissionId) => {
            const belongsToUncheckedRole = rolePermissionIds.includes(permissionId)
            const belongsToRemainingRole = remainingRolePermissionIds.includes(permissionId)

            return !belongsToUncheckedRole || belongsToRemainingRole
        })
    } else {
        form.roles.push(roleId)

        form.permissions = uniqueIds([
            ...form.permissions,
            ...rolePermissionIds,
        ])
    }
}

/**
 * Toggles an individual permission on or off.
 *
 * This does not change selected roles.
 *
 * @param {number|string} permissionId
 */
const togglePermission = (permissionId) => {
    if (form.permissions.includes(permissionId)) {
        form.permissions = form.permissions.filter(
            (id) => id !== permissionId
        )
    } else {
        form.permissions.push(permissionId)
    }
}

/**
 * Submits the updated role and permission assignments
 * to the backend update endpoint.
 */
const submit = () => {
    form.put(`/admin/users/${props.user.id}/permissions`)
}
</script>

<template>
    <div class="p-6 max-w-4xl space-y-6">

        <div class="flex items-center justify-between">

            <div>
                <h1 class="text-2xl font-semibold">
                    Edit Access
                </h1>

                <p class="text-sm text-muted-foreground mt-1">
                    Manage roles and permissions for this user.
                </p>
            </div>

            <Link href="/admin/users">
                <Button variant="outline">
                    Back to Users
                </Button>
            </Link>

        </div>

        <div class="border rounded-xl p-6 bg-background">

            <form @submit.prevent="submit" class="space-y-8">

                <div class="space-y-1">

                    <p class="text-sm text-muted-foreground">
                        User
                    </p>

                    <p class="font-medium">
                        {{ user.name }}

                        <span
                            v-if="user.email"
                            class="text-muted-foreground"
                        >
                            ({{ user.email }})
                        </span>
                    </p>

                </div>

                <!-- Roles -->
                <div class="space-y-4">

                    <div>

                        <h2 class="text-lg font-semibold">
                            Roles
                        </h2>

                        <p class="text-sm text-muted-foreground">
                            Selecting a role automatically adds that role’s default permissions.
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

                <!-- Permissions -->
                <div class="space-y-6">

                    <div>

                        <h2 class="text-lg font-semibold">
                            Permissions
                        </h2>

                        <p class="text-sm text-muted-foreground">
                            Permissions can be customized without changing the selected roles.
                        </p>

                    </div>

                    <div
                        v-for="group in permissionGroups"
                        :key="group.group"
                        class="space-y-4"
                    >

                        <div>

                            <h3 class="text-base font-semibold">
                                {{ group.group }}
                            </h3>

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

                    <Button
                        type="submit"
                        :disabled="form.processing"
                    >
                        {{ form.processing ? 'Saving...' : 'Save Access' }}
                    </Button>

                    <Link href="/admin/users">

                        <Button
                            type="button"
                            variant="outline"
                        >
                            Cancel
                        </Button>

                    </Link>

                </div>

            </form>

        </div>

    </div>
</template>