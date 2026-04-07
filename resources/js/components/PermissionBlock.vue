<script setup lang="ts">
import { useAuth } from '@/composables/useAuth'

const props = defineProps<{
    permission?: string
    permissions?: string[]
    role?: string
    fallback?: string
}>()

const { can, hasRole } = useAuth()

function hasAccess(): boolean {
    if (props.role) return hasRole(props.role)

    if (props.permission) return can(props.permission)

    if (props.permissions) {
        return props.permissions.some(p => can(p))
    }

    return false
}
</script>

<template>
    <div>
        <template v-if="hasAccess()">
            <slot />
        </template>

        <template v-else>
            <div v-if="fallback" class="text-gray-500">
                {{ fallback }}
            </div>
        </template>
    </div>
</template>