<template>
    <!-- Main page container -->
    <div class="p-6 space-y-6">

        <!-- Page header card -->
        <div class="rounded-2xl border bg-background p-6 shadow-sm">

            <!-- Page title -->
            <h1 class="text-2xl font-semibold">Create Group</h1>

            <!-- Page description -->
            <p class="mt-1 text-sm text-muted-foreground">
                Add a new group.
            </p>
        </div>

        <!-- Group creation form -->
        <form @submit.prevent="submit" class="space-y-6">

            <!-- Main form card -->
            <div class="rounded-2xl border bg-background p-6 shadow-sm space-y-4">

                <!-- Group Name field -->
                <div>
                    <Label for="group_name">Group Name</Label>

                    <!-- Two-way bound input field -->
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

                    <!-- Disable button while form is submitting -->
                    Save Group
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

// Reactive Inertia form object
const form = useForm({

    // Group name field
    group_name: '',
})

// Handles form submission
function submit() {

    // Submit form data to the backend create route
    form.post('/admin/groups')
}
</script>