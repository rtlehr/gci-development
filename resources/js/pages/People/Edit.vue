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
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'

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
})

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

    if (hasError) return

    form.put(`/people/${props.person.id}`)
}
</script>