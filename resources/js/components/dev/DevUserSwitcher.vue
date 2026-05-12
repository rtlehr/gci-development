<template>
    <div v-if="devDebug" class="rounded-lg border p-3 space-y-2 bg-muted/40">
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
                {{ testUser.name }} — {{ testUser.person_code }}
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

// Determines whether the development user switcher should be visible.
const devDebug = computed(() => page.props.dev?.debug === true)

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

    return rawTestUsers.value.filter(
        (user) => user.person_code !== current
    )
})

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