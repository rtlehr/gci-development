import { usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

type AuthUser = {
    id?: number
    username: string
    role: string
    permissions: string[]
    email?: string
    person_code?: string
    first_name?: string
    last_name?: string
} | null

type PageProps = {
    auth?: {
        user?: AuthUser
    }
}

export function useAuth() {
    const page = usePage<PageProps>()

    const user = computed(() => page.props.auth?.user ?? null)

    const username = computed(() => user.value?.username ?? '')
    const role = computed(() => user.value?.role ?? '')
    const permissions = computed(() => user.value?.permissions ?? [])

    function can(permission: string): boolean {
        return permissions.value.includes(permission)
    }

    function hasRole(checkRole: string): boolean {
        return role.value === checkRole
    }

    return {
        user,
        username,
        role,
        permissions,
        can,
        hasRole,
    }
}