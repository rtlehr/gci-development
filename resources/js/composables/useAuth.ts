import { usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

type AuthUser = {
    username: string
    security_level: number
}

// 👇 Define the shape of your page props
type PageProps = {
    auth?: {
        user?: AuthUser
    }
}

export function useAuth() {
    const page = usePage<PageProps>()

    console.log("page.props: ", page.props)

    const user = computed(() => page.props.auth?.user ?? null)

    const username = computed(() => user.value?.username ?? '')
    const securityLevel = computed(() => user.value?.security_level ?? 0)

    function hasLevel(level: number): boolean {
        return securityLevel.value >= level
    }

    return {
        user,
        username,
        securityLevel,
        hasLevel,
    }
}