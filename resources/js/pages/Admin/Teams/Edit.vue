<template>
    <div class="p-6 space-y-6">
        <div class="rounded-2xl border bg-background p-6 shadow-sm">
            <h1 class="text-2xl font-semibold">Edit Team</h1>
            <p class="mt-1 text-sm text-muted-foreground">
                Update this team.
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
                    Update Team
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

// Existing team record passed from the backend.
const props = defineProps({
    team: Object,
})

// Reactive Inertia form state initialized
// with the existing team values.
const form = useForm({
    team_name: props.team.team_name ?? '',
})

/**
 * Submits the updated team data
 * to the backend update endpoint.
 */
function submit() {
    form.put(`/admin/teams/${props.team.id}`)
}
</script>