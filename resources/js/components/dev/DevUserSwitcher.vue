<template>
    <!-- Only display the user switcher in development/debug mode -->
    <div v-if="devDebug" class="rounded-lg border p-3 space-y-2 bg-muted/40">

        <!-- Section title -->
        <div class="text-xs font-semibold text-muted-foreground">
            Development User Switcher
        </div>

        <!-- User selection dropdown -->
        <select
            class="w-full rounded-md border bg-background px-3 py-2 text-sm"

            <!-- Always reset dropdown back to placeholder after selection -->
            :value="''"

            <!-- Switch active development user -->
            @change="switchUser"
        >
            <!-- Placeholder option -->
            <option value="">Select test user...</option>

            <!-- Available impersonation users -->
            <option
                v-for="testUser in testUsers"
                :key="testUser.person_code"
                :value="testUser.person_code"
            >
                {{ testUser.name }} — {{ testUser.person_code }}
            </option>
        </select>

        <!-- Clears impersonation and returns to the real user -->
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
// Vue computed helper
import { computed } from 'vue'

// Inertia helpers for page data and requests
import { router, usePage } from '@inertiajs/vue3'

// Access global Inertia page props
const page = usePage()

// Determines if the application is running in debug/development mode
const devDebug = computed(() => page.props.dev?.debug === true)

// List of available test users passed from the backend
const rawTestUsers = computed(() => page.props.dev?.testUsers ?? [])

// Filtered list of test users
const testUsers = computed(() => {

    // Current logged-in or impersonated user
    const current = currentPersonCode.value

    // Remove the currently active user from the dropdown
    return rawTestUsers.value.filter(
        (user) => user.person_code !== current
    )
})

// Current user's person code
const currentPersonCode = computed(() => {
    return page.props.auth?.user?.person_code ?? ''
})

// Switches the current impersonated user
function switchUser(event) {

    // Selected person code from dropdown
    const personCode = event.target.value

    // Ignore empty selections
    if (!personCode) {
        return
    }

    // Send impersonation request to backend
    router.post('/dev/switch-user', {
        person_code: personCode,
    })
}

// Clears impersonation and restores the original user
function clearUser() {
    router.post('/dev/clear-user')
}
</script>