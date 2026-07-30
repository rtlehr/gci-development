<template>
    <div v-if="devSwitcherAvailable" class="rounded-lg border p-3 space-y-2 bg-muted/40">
        <div class="text-xs font-semibold text-muted-foreground">
            Development User Switcher
        </div>

        <select
            class="w-full rounded-md border bg-background px-3 py-2 text-sm"
            :value="''"
            @change="switchUser"
        >
            <option value="">Select test user...</option>

            <option
                v-for="testUser in testUsers"
                :key="testUser.person_code"
                :value="testUser.person_code"
            >
                {{ userOptionLabel(testUser) }}
            </option>
        </select>

        <button
            type="button"
            class="text-xs underline text-muted-foreground hover:text-foreground"
            @click="clearUser"
        >
            Return to your user
        </button>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'

const page = usePage()

// The backend exposes this only when APP_ENV=local and DEV_USER_ENABLED=true.
const devSwitcherAvailable = computed(
    () => page.props.dev?.available === true,
)

// Raw test users passed from the backend through Inertia page props.
const rawTestUsers = computed(() => page.props.dev?.testUsers ?? [])

// Current user's person code.
// Used to filter the active user out of the dropdown.
const currentPersonCode = computed(() => {
    return page.props.auth?.user?.person_code ?? ''
})

// Available test users excluding the currently active user.
const testUsers = computed(() => {
    const current = currentPersonCode.value

    return rawTestUsers.value
        .filter((user) => user.person_code !== current)
        .sort((left, right) => left.name.localeCompare(right.name))
})

/**
 * Builds the readable dropdown label.
 * Example: Charles Winchester - PM
 */
function userOptionLabel(user) {
    return user.role_display
        ? `${user.name} - ${user.role_display}`
        : user.name
}

/**
 * Switches the current development impersonation user.
 *
 * @param {Event} event
 */
function switchUser(event) {
    const personCode = event.target.value

    if (!personCode) {
        return
    }

    router.post('/dev/switch-user', {
        person_code: personCode,
    })
}

/**
 * Clears development impersonation
 * and returns to the original user.
 */
function clearUser() {
    router.post('/dev/clear-user')
}
</script>