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
                                AIN Number <span class="text-red-500">*</span>
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

            <Card class="rounded-xl">
                <CardHeader>
                    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <div>
                            <CardTitle>Phone Numbers</CardTitle>
                            <CardDescription>
                                Update one or more phone numbers. Only one phone number can be primary.
                            </CardDescription>
                        </div>

                        <Button type="button" variant="outline" @click="addPhoneNumber">
                            Add Phone Number
                        </Button>
                    </div>
                </CardHeader>

                <CardContent class="space-y-4">
                    <div
                        v-if="form.phone_numbers.length"
                        class="space-y-4"
                    >
                        <div
                            v-for="(phone, index) in form.phone_numbers"
                            :key="phone.id ?? `phone-${index}`"
                            class="rounded-xl border p-4 space-y-4"
                        >
                            <div class="flex items-center justify-between">
                                <h3 class="font-medium">Phone {{ index + 1 }}</h3>

                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    @click="removePhoneNumber(index)"
                                >
                                    Remove
                                </Button>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                                <div class="space-y-2 xl:col-span-2">
                                    <Label :for="`phone-number-${index}`">
                                        Phone Number <span class="text-red-500">*</span>
                                    </Label>
                                    <Input
                                        :id="`phone-number-${index}`"
                                        v-model="phone.phone_number"
                                        :class="phoneFieldError(index, 'phone_number') ? 'border-red-500' : ''"
                                    />
                                    <p
                                        v-if="phoneFieldError(index, 'phone_number')"
                                        class="text-sm text-red-500"
                                    >
                                        {{ phoneFieldError(index, 'phone_number') }}
                                    </p>
                                </div>

                                <div class="space-y-2">
                                    <Label :for="`phone-type-${index}`">Phone Type</Label>
                                    <select
                                        :id="`phone-type-${index}`"
                                        v-model="phone.phone_type"
                                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                        :class="phoneFieldError(index, 'phone_type') ? 'border-red-500' : ''"
                                    >
                                        <option value="">Select type</option>
                                        <option value="mobile">Mobile</option>
                                        <option value="work">Work</option>
                                        <option value="home">Home</option>
                                        <option value="fax">Fax</option>
                                        <option value="other">Other</option>
                                    </select>
                                    <p
                                        v-if="phoneFieldError(index, 'phone_type')"
                                        class="text-sm text-red-500"
                                    >
                                        {{ phoneFieldError(index, 'phone_type') }}
                                    </p>
                                </div>

                                <div class="space-y-2">
                                    <Label :for="`phone-extension-${index}`">Extension</Label>
                                    <Input
                                        :id="`phone-extension-${index}`"
                                        v-model="phone.extension"
                                        :class="phoneFieldError(index, 'extension') ? 'border-red-500' : ''"
                                    />
                                    <p
                                        v-if="phoneFieldError(index, 'extension')"
                                        class="text-sm text-red-500"
                                    >
                                        {{ phoneFieldError(index, 'extension') }}
                                    </p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="space-y-2">
                                    <Label :for="`phone-notes-${index}`">Notes</Label>
                                    <Textarea
                                        :id="`phone-notes-${index}`"
                                        v-model="phone.notes"
                                        rows="3"
                                        :class="phoneFieldError(index, 'notes') ? 'border-red-500' : ''"
                                    />
                                    <p
                                        v-if="phoneFieldError(index, 'notes')"
                                        class="text-sm text-red-500"
                                    >
                                        {{ phoneFieldError(index, 'notes') }}
                                    </p>
                                </div>

                                <div class="space-y-2">
                                    <Label>Primary Number</Label>

                                    <div class="flex items-center gap-3 rounded-md border p-3">
                                        <input
                                            :id="`phone-primary-${index}`"
                                            :checked="phone.is_primary"
                                            type="checkbox"
                                            class="h-4 w-4"
                                            @change="setPrimaryPhone(index)"
                                        />
                                        <Label :for="`phone-primary-${index}`" class="cursor-pointer">
                                            Make this the primary phone number
                                        </Label>
                                    </div>

                                    <p
                                        v-if="phoneFieldError(index, 'is_primary')"
                                        class="text-sm text-red-500"
                                    >
                                        {{ phoneFieldError(index, 'is_primary') }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-else class="rounded-xl border border-dashed p-6 text-sm text-muted-foreground">
                        No phone numbers added yet.
                    </div>

                    <p v-if="form.errors.phone_numbers" class="text-sm text-red-500">
                        {{ form.errors.phone_numbers }}
                    </p>
                </CardContent>
            </Card>

            <Card class="rounded-xl">
                <CardHeader>
                    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <div>
                            <CardTitle>Addresses</CardTitle>
                            <CardDescription>
                                Update one or more addresses. Only one address can be primary.
                            </CardDescription>
                        </div>

                        <Button type="button" variant="outline" @click="addAddress">
                            Add Address
                        </Button>
                    </div>
                </CardHeader>

                <CardContent class="space-y-4">
                    <div
                        v-if="form.addresses.length"
                        class="space-y-4"
                    >
                        <div
                            v-for="(address, index) in form.addresses"
                            :key="address.id ?? `address-${index}`"
                            class="rounded-xl border p-4 space-y-4"
                        >
                            <div class="flex items-center justify-between">
                                <h3 class="font-medium">Address {{ index + 1 }}</h3>

                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    @click="removeAddress(index)"
                                >
                                    Remove
                                </Button>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                                <div class="space-y-2">
                                    <Label :for="`address-type-${index}`">Address Type</Label>
                                    <select
                                        :id="`address-type-${index}`"
                                        v-model="address.address_type"
                                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                        :class="addressFieldError(index, 'address_type') ? 'border-red-500' : ''"
                                    >
                                        <option value="">Select type</option>
                                        <option value="home">Home</option>
                                        <option value="work">Work</option>
                                        <option value="mailing">Mailing</option>
                                        <option value="other">Other</option>
                                    </select>
                                    <p
                                        v-if="addressFieldError(index, 'address_type')"
                                        class="text-sm text-red-500"
                                    >
                                        {{ addressFieldError(index, 'address_type') }}
                                    </p>
                                </div>

                                <div class="space-y-2 xl:col-span-3">
                                    <Label :for="`address-line1-${index}`">
                                        Address Line 1 <span class="text-red-500">*</span>
                                    </Label>
                                    <Input
                                        :id="`address-line1-${index}`"
                                        v-model="address.line_1"
                                        :class="addressFieldError(index, 'line_1') ? 'border-red-500' : ''"
                                    />
                                    <p
                                        v-if="addressFieldError(index, 'line_1')"
                                        class="text-sm text-red-500"
                                    >
                                        {{ addressFieldError(index, 'line_1') }}
                                    </p>
                                </div>

                                <div class="space-y-2 xl:col-span-4">
                                    <Label :for="`address-line2-${index}`">Address Line 2</Label>
                                    <Input
                                        :id="`address-line2-${index}`"
                                        v-model="address.line_2"
                                        :class="addressFieldError(index, 'line_2') ? 'border-red-500' : ''"
                                    />
                                    <p
                                        v-if="addressFieldError(index, 'line_2')"
                                        class="text-sm text-red-500"
                                    >
                                        {{ addressFieldError(index, 'line_2') }}
                                    </p>
                                </div>

                                <div class="space-y-2">
                                    <Label :for="`address-city-${index}`">City</Label>
                                    <Input
                                        :id="`address-city-${index}`"
                                        v-model="address.city"
                                        :class="addressFieldError(index, 'city') ? 'border-red-500' : ''"
                                    />
                                    <p
                                        v-if="addressFieldError(index, 'city')"
                                        class="text-sm text-red-500"
                                    >
                                        {{ addressFieldError(index, 'city') }}
                                    </p>
                                </div>

                                <div class="space-y-2">
                                    <Label :for="`address-state-${index}`">State</Label>
                                    <Input
                                        :id="`address-state-${index}`"
                                        v-model="address.state"
                                        :class="addressFieldError(index, 'state') ? 'border-red-500' : ''"
                                    />
                                    <p
                                        v-if="addressFieldError(index, 'state')"
                                        class="text-sm text-red-500"
                                    >
                                        {{ addressFieldError(index, 'state') }}
                                    </p>
                                </div>

                                <div class="space-y-2">
                                    <Label :for="`address-postal-${index}`">Postal Code</Label>
                                    <Input
                                        :id="`address-postal-${index}`"
                                        v-model="address.postal_code"
                                        :class="addressFieldError(index, 'postal_code') ? 'border-red-500' : ''"
                                    />
                                    <p
                                        v-if="addressFieldError(index, 'postal_code')"
                                        class="text-sm text-red-500"
                                    >
                                        {{ addressFieldError(index, 'postal_code') }}
                                    </p>
                                </div>

                                <div class="space-y-2">
                                    <Label :for="`address-country-${index}`">Country</Label>
                                    <Input
                                        :id="`address-country-${index}`"
                                        v-model="address.country"
                                        :class="addressFieldError(index, 'country') ? 'border-red-500' : ''"
                                    />
                                    <p
                                        v-if="addressFieldError(index, 'country')"
                                        class="text-sm text-red-500"
                                    >
                                        {{ addressFieldError(index, 'country') }}
                                    </p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="space-y-2">
                                    <Label :for="`address-notes-${index}`">Notes</Label>
                                    <Textarea
                                        :id="`address-notes-${index}`"
                                        v-model="address.notes"
                                        rows="3"
                                        :class="addressFieldError(index, 'notes') ? 'border-red-500' : ''"
                                    />
                                    <p
                                        v-if="addressFieldError(index, 'notes')"
                                        class="text-sm text-red-500"
                                    >
                                        {{ addressFieldError(index, 'notes') }}
                                    </p>
                                </div>

                                <div class="space-y-2">
                                    <Label>Primary Address</Label>

                                    <div class="flex items-center gap-3 rounded-md border p-3">
                                        <input
                                            :id="`address-primary-${index}`"
                                            :checked="address.is_primary"
                                            type="checkbox"
                                            class="h-4 w-4"
                                            @change="setPrimaryAddress(index)"
                                        />
                                        <Label :for="`address-primary-${index}`" class="cursor-pointer">
                                            Make this the primary address
                                        </Label>
                                    </div>

                                    <p
                                        v-if="addressFieldError(index, 'is_primary')"
                                        class="text-sm text-red-500"
                                    >
                                        {{ addressFieldError(index, 'is_primary') }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-else class="rounded-xl border border-dashed p-6 text-sm text-muted-foreground">
                        No addresses added yet.
                    </div>

                    <p v-if="form.errors.addresses" class="text-sm text-red-500">
                        {{ form.errors.addresses }}
                    </p>
                </CardContent>
            </Card>

            <Card class="rounded-xl">
                <CardHeader>
                    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <div>
                            <CardTitle>Attachments</CardTitle>
                            <CardDescription>
                                Upload additional files and manage existing attachments.
                            </CardDescription>
                        </div>

                        <Button type="button" variant="outline" @click="addAttachment">
                            Add File
                        </Button>
                    </div>
                </CardHeader>

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
import { computed } from 'vue'
import { ref } from 'vue'
import AttachmentUploader from '@/components/attachments/AttachmentUploader.vue'
import { Link, useForm } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'

const attachmentsRef = ref(null)

const props = defineProps({
    person: {
        type: Object,
        required: true,
    },
})

const createEmptyPhoneNumber = (isPrimary = false) => ({
    id: null,
    phone_number: '',
    phone_type: '',
    is_primary: isPrimary,
    extension: '',
    notes: '',
})

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

const createEmptyAttachment = () => ({
    file: null,
    category: '',
    description: '',
    is_primary: false,
})

const existingPhoneNumbers = (props.person.phone_numbers ?? props.person.phoneNumbers ?? []).map((phone, index) => ({
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

const existingAttachmentsSeed = (props.person.attachments_for_ui ?? props.person.attachments ?? []).map((attachment) => ({
    id: attachment.id ?? null,
    original_name: attachment.original_name ?? '',
    category: attachment.category ?? '',
    description: attachment.description ?? '',
    is_primary: Boolean(attachment.is_primary ?? false),
    size: attachment.size ?? 0,
    url: attachment.url ?? null,
    marked_for_removal: false,
}))

const form = useForm({
    person_code: props.person.person_code ?? '',
    first_name: props.person.first_name ?? '',
    preferred_name: props.person.preferred_name ?? '',
    last_name: props.person.last_name ?? '',
    company_name: props.person.company_name ?? '',
    email: props.person.email ?? '',
    employment_status: props.person.employment_status ?? '',
    notes: props.person.notes ?? '',
    phone_numbers: normalizedPhoneNumbers,
    addresses: normalizedAddresses,
    attachments: [],
    existing_attachments: existingAttachmentsSeed,
    remove_attachment_ids: [],
})

const existingAttachments = computed(() => form.existing_attachments ?? [])

function addPhoneNumber() {
    form.phone_numbers.push(createEmptyPhoneNumber(form.phone_numbers.length === 0))
}

function removePhoneNumber(index) {
    const removedWasPrimary = form.phone_numbers[index]?.is_primary ?? false

    form.phone_numbers.splice(index, 1)

    if (removedWasPrimary && form.phone_numbers.length > 0) {
        form.phone_numbers = form.phone_numbers.map((phone, phoneIndex) => ({
            ...phone,
            is_primary: phoneIndex === 0,
        }))
    }
}

function setPrimaryPhone(index) {
    form.phone_numbers = form.phone_numbers.map((phone, phoneIndex) => ({
        ...phone,
        is_primary: phoneIndex === index,
    }))
}

function phoneFieldError(index, field) {
    return form.errors[`phone_numbers.${index}.${field}`]
}

function validatePhoneNumbers() {
    let hasError = false

    const filledPhoneRows = form.phone_numbers.filter((phone) => {
        return (
            phone.phone_number?.trim() !== '' ||
            phone.phone_type?.trim() !== '' ||
            phone.extension?.trim() !== '' ||
            phone.notes?.trim() !== ''
        )
    })

    if (filledPhoneRows.length === 0) {
        return false
    }

    form.phone_numbers.forEach((phone, index) => {
        const hasAnyValue =
            phone.phone_number?.trim() !== '' ||
            phone.phone_type?.trim() !== '' ||
            phone.extension?.trim() !== '' ||
            phone.notes?.trim() !== ''

        if (hasAnyValue && (!phone.phone_number || phone.phone_number.trim() === '')) {
            form.setError(`phone_numbers.${index}.phone_number`, 'Phone number is required.')
            hasError = true
        }
    })

    const primaryCount = filledPhoneRows.filter((phone) => phone.is_primary).length

    if (filledPhoneRows.length > 0 && primaryCount !== 1) {
        form.setError('phone_numbers', 'Exactly one phone number must be marked as primary.')
        hasError = true
    }

    return hasError
}

function addAddress() {
    form.addresses.push(createEmptyAddress(form.addresses.length === 0))
}

function removeAddress(index) {
    const removedWasPrimary = form.addresses[index]?.is_primary ?? false

    form.addresses.splice(index, 1)

    if (removedWasPrimary && form.addresses.length > 0) {
        form.addresses = form.addresses.map((address, addressIndex) => ({
            ...address,
            is_primary: addressIndex === 0,
        }))
    }
}

function setPrimaryAddress(index) {
    form.addresses = form.addresses.map((address, addressIndex) => ({
        ...address,
        is_primary: addressIndex === index,
    }))
}

function addressFieldError(index, field) {
    return form.errors[`addresses.${index}.${field}`]
}

function validateAddresses() {
    let hasError = false

    const filledRows = form.addresses.filter((address) => {
        return (
            address.line_1?.trim() !== '' ||
            address.line_2?.trim() !== '' ||
            address.city?.trim() !== '' ||
            address.state?.trim() !== '' ||
            address.postal_code?.trim() !== '' ||
            address.country?.trim() !== '' ||
            address.notes?.trim() !== ''
        )
    })

    if (filledRows.length === 0) {
        return false
    }

    form.addresses.forEach((address, index) => {
        const hasAnyValue =
            address.line_1?.trim() !== '' ||
            address.line_2?.trim() !== '' ||
            address.city?.trim() !== '' ||
            address.state?.trim() !== '' ||
            address.postal_code?.trim() !== '' ||
            address.country?.trim() !== '' ||
            address.notes?.trim() !== ''

        if (hasAnyValue && (!address.line_1 || address.line_1.trim() === '')) {
            form.setError(`addresses.${index}.line_1`, 'Address line 1 is required.')
            hasError = true
        }
    })

    const primaryCount = filledRows.filter((address) => address.is_primary).length

    if (filledRows.length > 0 && primaryCount !== 1) {
        form.setError('addresses', 'Exactly one address must be marked as primary.')
        hasError = true
    }

    return hasError
}

function addAttachment() {
    form.attachments.push(createEmptyAttachment())
}

function removeAttachment(index) {
    form.attachments.splice(index, 1)
}

function handleFileChange(event, index) {
    const file = event.target.files?.[0] ?? null
    form.attachments[index].file = file
}

function setPrimaryAttachment(index) {
    form.attachments = form.attachments.map((attachment, attachmentIndex) => ({
        ...attachment,
        is_primary: attachmentIndex === index,
    }))
}

function attachmentFieldError(index, field) {
    return form.errors[`attachments.${index}.${field}`]
}

function validateAttachments() {
    let hasError = false

    form.attachments.forEach((attachment, index) => {
        if (!attachment.file) {
            form.setError(`attachments.${index}.file`, 'A file is required.')
            hasError = true
        }
    })

    return hasError
}

function markAttachmentForRemoval(index) {
    const attachment = form.existing_attachments[index]
    if (!attachment) return

    attachment.marked_for_removal = true

    if (!form.remove_attachment_ids.includes(attachment.id)) {
        form.remove_attachment_ids.push(attachment.id)
    }
}

function undoAttachmentRemoval(index) {
    const attachment = form.existing_attachments[index]
    if (!attachment) return

    attachment.marked_for_removal = false
    form.remove_attachment_ids = form.remove_attachment_ids.filter((id) => id !== attachment.id)
}

function formatCategory(value) {
    if (!value) return 'Other'
    const normalized = String(value).replaceAll('_', ' ')
    return normalized.charAt(0).toUpperCase() + normalized.slice(1)
}

function formatFileSize(size) {
    const value = Number(size ?? 0)

    if (!value) return ''

    if (value < 1024) return `${value} B`
    if (value < 1024 * 1024) return `${(value / 1024).toFixed(1)} KB`
    return `${(value / (1024 * 1024)).toFixed(1)} MB`
}

function submit() {
    form.clearErrors()

    let hasError = false

    if (!form.person_code || form.person_code.trim() === '') {
        form.setError('person_code', 'Person code is required.')
        hasError = true
    }

    if (!form.first_name || form.first_name.trim() === '') {
        form.setError('first_name', 'First name is required.')
        hasError = true
    }

    if (!form.last_name || form.last_name.trim() === '') {
        form.setError('last_name', 'Last name is required.')
        hasError = true
    }

    if (validatePhoneNumbers()) {
        hasError = true
    }

    if (validateAddresses()) {
        hasError = true
    }

    if (validateAttachments()) {
        hasError = true
    }

    if (attachmentsRef.value && !attachmentsRef.value.validate()) {
        hasError = true
    }

    if (hasError) return

    form.transform((data) => {
        const transformed = {
            ...data,
            _method: 'put',
            attachment_meta: data.attachments.map((attachment, index) => ({
                category: attachment.category ?? '',
                description: attachment.description ?? '',
                is_primary: attachment.is_primary ? 1 : 0,
                sort_order: index,
            })),
            new_attachments: data.attachments.map((attachment) => attachment.file).filter(Boolean),
            remove_attachment_ids: data.remove_attachment_ids ?? [],
        }

        delete transformed.attachments
        delete transformed.existing_attachments

        return transformed
    }).post(`/people/${props.person.id}`, {
        forceFormData: true,
    })
}
</script>