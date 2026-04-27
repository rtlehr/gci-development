<template>
    <div v-if="devDebug" class="rounded-lg border p-3 space-y-2 bg-muted/40">
        <div class="text-xs font-semibold text-muted-foreground">
            Development User Switcher
        </div>

        <select
            class="w-full rounded-md border bg-background px-3 py-2 text-sm"
            :value="currentPersonCode"
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
            Reset to default dev user
        </button>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'

const page = usePage()

const devDebug = computed(() => page.props.dev?.debug === true)
const testUsers = computed(() => page.props.dev?.testUsers ?? [])

const currentPersonCode = computed(() => {
    return page.props.auth?.user?.person_code ?? ''
})

function switchUser(event) {
    const personCode = event.target.value

    if (!personCode) {
        return
    }

    router.post('/dev/switch-user', {
        person_code: personCode,
    })
}

function clearUser() {
    router.post('/dev/clear-user')
}
</script>