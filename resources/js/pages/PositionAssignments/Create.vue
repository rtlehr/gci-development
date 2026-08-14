<template>
    <div class="p-6 max-w-4xl space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold">Create Assignment</h1>
                <p class="text-sm text-muted-foreground mt-1">
                    Assign a person to a position.
                </p>
            </div>

            <Button as-child variant="outline"><Link href="/people">Back</Link></Button>
        </div>

        <div class="border rounded-xl p-6 bg-background">
            <form @submit.prevent="submit" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <Label for="person_id">
                            Person <span class="text-red-500">*</span>
                        </Label>
                        <select
                            id="person_id"
                            v-model="form.person_id"
                            :class="[
                                'flex h-10 w-full rounded-md border bg-background px-3 py-2 text-sm',
                                form.errors.person_id ? 'border-red-500' : 'border-input'
                            ]"
                        >
                            <option value="">Select a person</option>
                            <option
                                v-for="person in people"
                                :key="person.id"
                                :value="person.id"
                            >
                                {{ person.last_name }}, {{ person.first_name }}
                                <span v-if="person.person_code">({{ person.person_code }})</span>
                            </option>
                        </select>
                        <p v-if="form.errors.person_id" class="text-sm text-red-500">
                            {{ form.errors.person_id }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="position_id">
                            Position <span class="text-red-500">*</span>
                        </Label>
                        <select
                            id="position_id"
                            v-model="form.position_id"
                            :class="[
                                'flex h-10 w-full rounded-md border bg-background px-3 py-2 text-sm',
                                form.errors.position_id ? 'border-red-500' : 'border-input'
                            ]"
                        >
                            <option value="">Select a position</option>
                            <option
                                v-for="position in positions"
                                :key="position.id"
                                :value="position.id"
                            >
                                {{ position.job_title }}
                                <span v-if="position.position_code">({{ position.position_code }})</span>
                            </option>
                        </select>
                        <p v-if="form.errors.position_id" class="text-sm text-red-500">
                            {{ form.errors.position_id }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="start_date">
                            Start Date <span class="text-red-500">*</span>
                        </Label>
                        <Input
                            id="start_date"
                            type="date"
                            v-model="form.start_date"
                            :class="form.errors.start_date ? 'border-red-500' : ''"
                        />
                        <p v-if="form.errors.start_date" class="text-sm text-red-500">
                            {{ form.errors.start_date }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="end_date">End Date</Label>
                        <Input
                            id="end_date"
                            type="date"
                            v-model="form.end_date"
                            :class="form.errors.end_date ? 'border-red-500' : ''"
                        />
                        <p v-if="form.errors.end_date" class="text-sm text-red-500">
                            {{ form.errors.end_date }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="assignment_status">
                            Assignment Status <span class="text-red-500">*</span>
                        </Label>
                        <select
                            id="assignment_status"
                            v-model="form.assignment_status"
                            :class="[
                                'flex h-10 w-full rounded-md border bg-background px-3 py-2 text-sm',
                                form.errors.assignment_status ? 'border-red-500' : 'border-input'
                            ]"
                        >
                            <option value="">Select status</option>
                            <option value="active">active</option>
                            <option value="planned">planned</option>
                            <option value="ended">ended</option>
                        </select>
                        <p v-if="form.errors.assignment_status" class="text-sm text-red-500">
                            {{ form.errors.assignment_status }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="assignment_type">Assignment Type</Label>
                        <Input
                            id="assignment_type"
                            v-model="form.assignment_type"
                            :class="form.errors.assignment_type ? 'border-red-500' : ''"
                        />
                        <p v-if="form.errors.assignment_type" class="text-sm text-red-500">
                            {{ form.errors.assignment_type }}
                        </p>
                    </div>
                </div>

                <div class="space-y-2">
                    <Label for="notes">Notes</Label>
                    <Textarea
                        id="notes"
                        v-model="form.notes"
                        rows="5"
                        :class="form.errors.notes ? 'border-red-500' : ''"
                    />
                    <p v-if="form.errors.notes" class="text-sm text-red-500">
                        {{ form.errors.notes }}
                    </p>
                </div>

                <div class="flex gap-3">
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Saving...' : 'Create Assignment' }}
                    </Button>

                    <Button as-child variant="outline"><Link href="/people">Cancel</Link></Button>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { Link, useForm } from '@inertiajs/vue3'

import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'

// Backend-provided people, positions,
// and optional prefilled assignment values.
const props = defineProps({
    people: {
        type: Array,
        default: () => [],
    },
    positions: {
        type: Array,
        default: () => [],
    },
    prefill: {
        type: Object,
        default: () => ({
            person_id: '',
            position_id: '',
        }),
    },
})

/**
 * Returns today's date formatted for
 * use in an HTML date input field.
 *
 * Adjusts for local timezone offsets
 * to avoid UTC date shifting issues.
 *
 * @returns {string}
 */
function getTodayDate() {
    const d = new Date()
    const offset = d.getTimezoneOffset()

    const local = new Date(
        d.getTime() - offset * 60 * 1000
    )

    return local.toISOString().split('T')[0]
}

// Reactive Inertia form state.
// Stores assignment form values.
const form = useForm({
    person_id: props.prefill?.person_id ?? '',
    position_id: props.prefill?.position_id ?? '',
    start_date: getTodayDate(),
    end_date: '',
    assignment_status: 'active',
    assignment_type: '',
    notes: '',
})

/**
 * Validates and submits the new assignment record
 * to the backend create endpoint.
 */
function submit() {

    form.clearErrors()

    let hasError = false

    // Required field validation.
    if (!form.person_id) {
        form.setError('person_id', 'Person is required.')
        hasError = true
    }

    if (!form.position_id) {
        form.setError('position_id', 'Position is required.')
        hasError = true
    }

    if (!form.start_date) {
        form.setError('start_date', 'Start date is required.')
        hasError = true
    }

    if (!form.assignment_status) {
        form.setError('assignment_status', 'Assignment status is required.')
        hasError = true
    }

    // Stop submission if validation failed.
    if (hasError) return

    form.post('/position-assignments')
}
</script>