<template>
    <!-- Main page container -->
    <div class="p-6 space-y-6">

        <!-- Page header card -->
        <div class="rounded-2xl border bg-background p-6 shadow-sm">
            <h1 class="text-2xl font-semibold">Edit Group</h1>

            <!-- Page description -->
            <p class="mt-1 text-sm text-muted-foreground">
                Update this group.
            </p>
        </div>

        <!-- Group edit form -->
        <form @submit.prevent="submit" class="space-y-6">

            <!-- Main form card -->
            <div class="rounded-2xl border bg-background p-6 shadow-sm space-y-4">

                <!-- Group Name field -->
                <div>
                    <Label for="group_name">Group Name</Label>

                    <!-- Two-way bound input field initialized from the existing group -->
                    <Input
                        id="group_name"
                        v-model="form.group_name"
                        type="text"
                        class="mt-1"
                    />

                    <!-- Validation error message -->
                    <p v-if="form.errors.group_name" class="mt-1 text-sm text-red-600">
                        {{ form.errors.group_name }}
                    </p>
                </div>
            </div>

            <!-- Form action buttons -->
            <div class="flex gap-2">

                <!-- Submit button -->
                <Button type="submit" :disabled="form.processing">
                    Update Group
                </Button>

                <!-- Cancel button navigates back to group list -->
                <Link href="/admin/groups">
                    <Button type="button" variant="outline">
                        Cancel
                    </Button>
                </Link>
            </div>
        </form>
    </div>
</template>

<script setup>
// Inertia helpers for navigation and form handling
import { Link, useForm } from '@inertiajs/vue3'

// Shared UI components
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'

// Existing group record passed in from the backend
const props = defineProps({
    group: Object,
})

// Reactive Inertia form object
const form = useForm({

    // Start with the current group name, or blank if missing
    group_name: props.group.group_name ?? '',
})

// Handles form submission
function submit() {

    // Submit updated group data to the backend update route
    form.put(`/admin/groups/${props.group.id}`)
}
</script>