<template>
    <div class="p-6 max-w-6xl space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold">Edit Person</h1>
                <p class="text-sm text-muted-foreground mt-1">
                    Update this person record.
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
                        Update the main details for this person.
                    </CardDescription>
                </CardHeader>

                <CardContent class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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
                                type="email"
                                v-model="form.email"
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
                v-model:existingAttachments="form.existing_attachments"
                v-model:removeAttachmentIds="form.remove_attachment_ids"
                :errors="form.errors"
            />

            <div class="flex gap-3">
                <Button type="submit" :disabled="form.processing">
                    {{ form.processing ? 'Saving...' : 'Save Changes' }}
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

// References to child form components.
// Used to trigger validation methods before submit.
const attachmentsRef = ref(null)
const phoneNumbersRef = ref(null)
const addressesRef = ref(null)

// Backend-provided person, group,
// and team data used by the form.
const props = defineProps({
    person: {
        type: Object,
        required: true,
    },
    groups: {
        type: Array,
        default: () => [],
    },
    teams: {
        type: Array,
        default: () => [],
    },
})

/**
 * Creates a new empty phone number object
 * for initializing or adding phone records.
 *
 * @param {boolean} isPrimary
 * @returns {Object}
 */
const createEmptyPhoneNumber = (isPrimary = false) => ({
    id: null,
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
    id: null,
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

// Normalized phone number data loaded from the backend.
// Ensures at least one primary phone exists.
const existingPhoneNumbers = (
    props.person.phone_numbers ??
    props.person.phoneNumbers ??
    []
).map((phone, index) => ({
    id: phone.id ?? null,
    phone_number: phone.phone_number ?? '',
    phone_type: phone.phone_type ?? '',
    is_primary: Boolean(phone.is_primary ?? false),
    extension: phone.extension ?? '',
    notes: phone.notes ?? '',
    sort_order: index,
}))

const normalizedPhoneNumbers = existingPhoneNumbers.length
    ? existingPhoneNumbers.map((phone, index) => ({
        ...phone,

        is_primary: existingPhoneNumbers.some((item) => item.is_primary)
            ? phone.is_primary
            : index === 0,
    }))
    : [createEmptyPhoneNumber(true)]

// Normalized address data loaded from the backend.
// Ensures at least one primary address exists.
const existingAddresses = (props.person.addresses ?? []).map((address) => ({
    id: address.id ?? null,
    address_type: address.address_type ?? '',
    line_1: address.line_1 ?? '',
    line_2: address.line_2 ?? '',
    city: address.city ?? '',
    state: address.state ?? '',
    postal_code: address.postal_code ?? '',
    country: address.country ?? 'USA',
    is_primary: Boolean(address.is_primary ?? false),
    notes: address.notes ?? '',
}))

const normalizedAddresses = existingAddresses.length
    ? existingAddresses.map((address, index) => ({
        ...address,

        is_primary: existingAddresses.some((item) => item.is_primary)
            ? address.is_primary
            : index === 0,
    }))
    : [createEmptyAddress(true)]

// Existing attachment records formatted
// for use in the attachment uploader component.
const existingAttachmentsSeed = (
    props.person.attachments_for_ui ??
    props.person.attachments ??
    []
).map((attachment) => ({
    id: attachment.id ?? null,
    original_name: attachment.original_name ?? '',
    category: attachment.category ?? '',
    description: attachment.description ?? '',
    is_primary: Boolean(attachment.is_primary ?? false),
    size: attachment.size ?? 0,
    url: attachment.url ?? null,
    marked_for_removal: false,
}))

// Existing group and team assignments.
const existingGroupIds = (props.person.groups ?? []).map((group) => group.id)
const existingTeamIds = (props.person.teams ?? []).map((team) => team.id)

// Reactive Inertia form state initialized
// with the existing person values.
const form = useForm({
    person_code: props.person.person_code ?? '',
    first_name: props.person.first_name ?? '',
    preferred_name: props.person.preferred_name ?? '',
    last_name: props.person.last_name ?? '',
    company_name: props.person.company_name ?? '',
    email: props.person.email ?? '',
    employment_status: props.person.employment_status ?? '',
    notes: props.person.notes ?? '',

    group_ids: existingGroupIds,
    team_ids: existingTeamIds,

    phone_numbers: normalizedPhoneNumbers,
    addresses: normalizedAddresses,

    attachments: [],
    existing_attachments: existingAttachmentsSeed,
    remove_attachment_ids: [],
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
 * Validates and submits the updated person record.
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

                _method: 'put',

                attachment_meta: data.attachments.map((attachment, index) => ({
                    category: attachment.category ?? '',
                    description: attachment.description ?? '',
                    is_primary: attachment.is_primary ? 1 : 0,
                    sort_order: index,
                })),

                new_attachments: data.attachments
                    .map((attachment) => attachment.file)
                    .filter(Boolean),

                remove_attachment_ids: data.remove_attachment_ids ?? [],
            }

            delete transformed.attachments
            delete transformed.existing_attachments

            return transformed
        })

        .post(`/people/${props.person.id}`, {
            forceFormData: true,
        })
}
</script>