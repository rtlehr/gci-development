<template>
    <div class="p-6 space-y-6">
        <div class="rounded-2xl border bg-background p-6 shadow-sm">
            <h1 class="text-2xl font-semibold">Create Team</h1>
            <p class="mt-1 text-sm text-muted-foreground">
                Add a new team.
            </p>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <div class="rounded-2xl border bg-background p-6 shadow-sm space-y-4">
                <div>
                    <Label for="team_name">Team Name</Label>

                    <Input
                        id="team_name"
                        v-model="form.team_name"
                        type="text"
                        class="mt-1"
                    />

                    <p v-if="form.errors.team_name" class="mt-1 text-sm text-red-600">
                        {{ form.errors.team_name }}
                    </p>
                </div>
            </div>

            <div class="flex gap-2">
                <Button type="submit" :disabled="form.processing">
                    Save Team
                </Button>

                <Link href="/admin/teams">
                    <Button type="button" variant="outline">
                        Cancel
                    </Button>
                </Link>
            </div>
        </form>
    </div>
</template>

<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'

// Reactive Inertia form state.
// Tracks field values, validation errors, and submission state.
const form = useForm({
    team_name: '',
})

/**
 * Submits the new team record
 * to the backend create endpoint.
 */
function submit() {
    form.post('/admin/teams')
}
</script>