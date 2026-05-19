<template>
    <div class="max-w-6xl space-y-6 p-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold">Create Person</h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    Add a new person record.
                </p>
            </div>

            <Link href="/people">
                <Button variant="outline">Back to List</Button>
            </Link>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <Card class="rounded-xl">
                <CardHeader>
                    <CardTitle>Person Details</CardTitle>
                    <CardDescription>
                        Basic information for the new person record.
                    </CardDescription>
                </CardHeader>

                <CardContent class="space-y-6">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="person_code">

                                {{ label('person_code') }}  

                                <span class="text-red-500">*</span>

                            </Label>
                            <Input
                                id="person_code"
                                v-model="form.person_code"
                                :class="form.errors.person_code ? 'border-red-500' : ''"
                            />
                            <p v-if="form.errors.person_code" class="text-sm text-red-500">
                                {{ form.errors.person_code }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="employment_status">Employment Status</Label>
                            <Input
                                id="employment_status"
                                v-model="form.employment_status"
                                :class="form.errors.employment_status ? 'border-red-500' : ''"
                            />
                            <p v-if="form.errors.employment_status" class="text-sm text-red-500">
                                {{ form.errors.employment_status }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="first_name">
                                First Name <span class="text-red-500">*</span>
                            </Label>
                            <Input
                                id="first_name"
                                v-model="form.first_name"
                                :class="form.errors.first_name ? 'border-red-500' : ''"
                            />
                            <p v-if="form.errors.first_name" class="text-sm text-red-500">
                                {{ form.errors.first_name }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="preferred_name">Preferred Name</Label>
                            <Input
                                id="preferred_name"
                                v-model="form.preferred_name"
                                :class="form.errors.preferred_name ? 'border-red-500' : ''"
                            />
                            <p v-if="form.errors.preferred_name" class="text-sm text-red-500">
                                {{ form.errors.preferred_name }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="last_name">
                                Last Name <span class="text-red-500">*</span>
                            </Label>
                            <Input
                                id="last_name"
                                v-model="form.last_name"
                                :class="form.errors.last_name ? 'border-red-500' : ''"
                            />
                            <p v-if="form.errors.last_name" class="text-sm text-red-500">
                                {{ form.errors.last_name }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="company_name">Company Name</Label>
                            <Input
                                id="company_name"
                                v-model="form.company_name"
                                :class="form.errors.company_name ? 'border-red-500' : ''"
                            />
                            <p v-if="form.errors.company_name" class="text-sm text-red-500">
                                {{ form.errors.company_name }}
                            </p>
                        </div>

                        <div class="space-y-2 md:col-span-2">
                            <Label for="email">Email</Label>
                            <Input
                                id="email"
                                v-model="form.email"
                                type="email"
                                :class="form.errors.email ? 'border-red-500' : ''"
                            />
                            <p v-if="form.errors.email" class="text-sm text-red-500">
                                {{ form.errors.email }}
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
                </CardContent>
            </Card>

            <AssignmentsEditor
                v-model:group-ids="form.group_ids"
                v-model:team-ids="form.team_ids"
                :groups="groups"
                :teams="teams"
                :errors="form.errors"
            />

            <PhoneNumbersEditor
                ref="phoneNumbersRef"
                v-model="form.phone_numbers"
                :errors="form.errors"
            />

            <AddressesEditor
                ref="addressesRef"
                v-model="form.addresses"
                :errors="form.errors"
            />

            <AttachmentUploader
                ref="attachmentsRef"
                v-model="form.attachments"
                :errors="form.errors"
                :show-existing="false"
            />

            <div class="flex gap-3">
                <Button type="submit" :disabled="form.processing">
                    {{ form.processing ? 'Saving...' : 'Create Person' }}
                </Button>

                <Link href="/people">
                    <Button type="button" variant="outline">Cancel</Button>
                </Link>
            </div>
        </form>
    </div>
</template>

<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'

import AttachmentUploader from '@/components/attachments/AttachmentUploader.vue'
import AddressesEditor from '@/components/forms/AddressesEditor.vue'
import AssignmentsEditor from '@/components/forms/AssignmentsEditor.vue'
import PhoneNumbersEditor from '@/components/forms/PhoneNumbersEditor.vue'

import { Button } from '@/components/ui/button'

import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card'

import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'

import { useAppLabels } from '@/composables/useAppLabels'

const { label } = useAppLabels()

// Backend-provided group and team lists
// used by the assignments editor.
const props = defineProps({
    groups: {
        type: Array,
        default: () => [],
    },
    teams: {
        type: Array,
        default: () => [],
    },
})

// References to child form components.
// Used to trigger validation methods before submit.
const phoneNumbersRef = ref(null)
const attachmentsRef = ref(null)
const addressesRef = ref(null)

/**
 * Creates a new empty phone number object
 * for initializing or adding phone records.
 *
 * @param {boolean} isPrimary
 * @returns {Object}
 */
const createEmptyPhoneNumber = (isPrimary = false) => ({
    phone_number: '',
    phone_type: '',
    is_primary: isPrimary,
    extension: '',
    notes: '',
})

/**
 * Creates a new empty address object
 * for initializing or adding address records.
 *
 * @param {boolean} isPrimary
 * @returns {Object}
 */
const createEmptyAddress = (isPrimary = false) => ({
    address_type: '',
    line_1: '',
    line_2: '',
    city: '',
    state: '',
    postal_code: '',
    country: 'USA',
    is_primary: isPrimary,
    notes: '',
})

// Reactive Inertia form state.
// Stores all person-related form data.
const form = useForm({
    person_code: '',
    first_name: '',
    preferred_name: '',
    last_name: '',
    company_name: '',
    email: '',
    employment_status: '',
    notes: '',
    group_ids: [],
    team_ids: [],
    phone_numbers: [createEmptyPhoneNumber(true)],
    addresses: [createEmptyAddress(true)],
    attachments: [],
})

// Local references for cleaner template access.
const groups = props.groups
const teams = props.teams

/**
 * Validates uploaded attachments
 * to ensure required files exist.
 *
 * @returns {boolean}
 */
function validateAttachments() {

    let hasError = false

    form.attachments.forEach((attachment, index) => {

        if (!attachment.file) {

            form.setError(
                `attachments.${index}.file`,
                'A file is required.'
            )

            hasError = true
        }
    })

    return hasError
}

/**
 * Validates and submits the new person record.
 *
 * Also transforms attachment data into a structure
 * compatible with multipart/form-data uploads.
 */
function submit() {

    form.clearErrors()

    let hasError = false

    // Basic required field validation.
    if (!form.person_code || form.person_code.trim() === '') {

        form.setError(
            'person_code',
            'Person code is required.'
        )

        hasError = true
    }

    if (!form.first_name || form.first_name.trim() === '') {

        form.setError(
            'first_name',
            'First name is required.'
        )

        hasError = true
    }

    if (!form.last_name || form.last_name.trim() === '') {

        form.setError(
            'last_name',
            'Last name is required.'
        )

        hasError = true
    }

    // Trigger child component validation methods.
    if (phoneNumbersRef.value && !phoneNumbersRef.value.validate()) {
        hasError = true
    }

    if (addressesRef.value && !addressesRef.value.validate()) {
        hasError = true
    }

    if (validateAttachments()) {
        hasError = true
    }

    if (attachmentsRef.value && !attachmentsRef.value.validate()) {
        hasError = true
    }

    // Stop submission if any validation failed.
    if (hasError) return

    form
        .transform((data) => {

            // Convert attachment data into separate
            // metadata and file upload arrays.
            const transformed = {
                ...data,

                attachment_meta: data.attachments.map((attachment, index) => ({
                    category: attachment.category ?? '',
                    description: attachment.description ?? '',
                    is_primary: attachment.is_primary ? 1 : 0,
                    sort_order: index,
                })),

                new_attachments: data.attachments
                    .map((attachment) => attachment.file)
                    .filter(Boolean),
            }

            delete transformed.attachments

            return transformed
        })

        .post('/people', {
            forceFormData: true,
        })
}
</script>