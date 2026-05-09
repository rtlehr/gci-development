<template>
    <!-- Main wrapper card for managing phone numbers -->
    <Card class="rounded-xl">
        <CardHeader>

            <!-- Header section with title/description and Add button -->
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div>
                    <CardTitle>{{ title }}</CardTitle>

                    <!-- Description text shown under the title -->
                    <CardDescription>
                        {{ description }}
                    </CardDescription>
                </div>

                <!-- Adds a new phone number row -->
                <Button type="button" variant="outline" @click="addPhoneNumber">
                    {{ addButtonLabel }}
                </Button>
            </div>
        </CardHeader>

        <CardContent class="space-y-4">

            <!-- Only display phone list if entries exist -->
            <div
                v-if="internalValue.length"
                class="space-y-4"
            >

                <!-- Loop through each phone number -->
                <div
                    v-for="(phone, index) in internalValue"
                    :key="phone.id ?? `phone-${index}`"
                    class="rounded-xl border p-4 space-y-4"
                >
                    <!-- Phone entry header -->
                    <div class="flex items-center justify-between">
                        <h3 class="font-medium">{{ itemLabel }} {{ index + 1 }}</h3>

                        <!-- Remove current phone number -->
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            @click="removePhoneNumber(index)"
                        >
                            Remove
                        </Button>
                    </div>

                    <!-- Main phone number fields -->
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">

                        <!-- Phone Number -->
                        <div class="space-y-2 xl:col-span-2">
                            <Label :for="`phone-number-${uid}-${index}`">
                                Phone Number <span class="text-red-500">*</span>
                            </Label>

                            <Input
                                :id="`phone-number-${uid}-${index}`"
                                v-model="phone.phone_number"
                                :class="fieldError(index, 'phone_number') ? 'border-red-500' : ''"
                            />

                            <!-- Validation error display -->
                            <p
                                v-if="fieldError(index, 'phone_number')"
                                class="text-sm text-red-500"
                            >
                                {{ fieldError(index, 'phone_number') }}
                            </p>
                        </div>

                        <!-- Phone Type -->
                        <div class="space-y-2">
                            <Label :for="`phone-type-${uid}-${index}`">Phone Type</Label>

                            <select
                                :id="`phone-type-${uid}-${index}`"
                                v-model="phone.phone_type"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                :class="fieldError(index, 'phone_type') ? 'border-red-500' : ''"
                            >
                                <option value="">Select type</option>

                                <!-- Populate phone type dropdown -->
                                <option
                                    v-for="option in phoneTypeOptions"
                                    :key="option.value"
                                    :value="option.value"
                                >
                                    {{ option.label }}
                                </option>
                            </select>

                            <!-- Validation error display -->
                            <p
                                v-if="fieldError(index, 'phone_type')"
                                class="text-sm text-red-500"
                            >
                                {{ fieldError(index, 'phone_type') }}
                            </p>
                        </div>

                        <!-- Extension -->
                        <div class="space-y-2">
                            <Label :for="`phone-extension-${uid}-${index}`">Extension</Label>

                            <Input
                                :id="`phone-extension-${uid}-${index}`"
                                v-model="phone.extension"
                                :class="fieldError(index, 'extension') ? 'border-red-500' : ''"
                            />

                            <!-- Validation error display -->
                            <p
                                v-if="fieldError(index, 'extension')"
                                class="text-sm text-red-500"
                            >
                                {{ fieldError(index, 'extension') }}
                            </p>
                        </div>
                    </div>

                    <!-- Notes and Primary Number section -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                        <!-- Notes -->
                        <div class="space-y-2">
                            <Label :for="`phone-notes-${uid}-${index}`">Notes</Label>

                            <Textarea
                                :id="`phone-notes-${uid}-${index}`"
                                v-model="phone.notes"
                                rows="3"
                                :class="fieldError(index, 'notes') ? 'border-red-500' : ''"
                            />

                            <!-- Validation error display -->
                            <p
                                v-if="fieldError(index, 'notes')"
                                class="text-sm text-red-500"
                            >
                                {{ fieldError(index, 'notes') }}
                            </p>
                        </div>

                        <!-- Primary Phone Number -->
                        <div class="space-y-2">
                            <Label>Primary Number</Label>

                            <div class="flex items-center gap-3 rounded-md border p-3">

                                <!-- Only one phone number can be primary -->
                                <input
                                    :id="`phone-primary-${uid}-${index}`"
                                    :checked="phone.is_primary"
                                    type="checkbox"
                                    class="h-4 w-4"
                                    @change="setPrimaryPhone(index)"
                                />

                                <Label :for="`phone-primary-${uid}-${index}`" class="cursor-pointer">
                                    Make this the primary phone number
                                </Label>
                            </div>

                            <!-- Validation error display -->
                            <p
                                v-if="fieldError(index, 'is_primary')"
                                class="text-sm text-red-500"
                            >
                                {{ fieldError(index, 'is_primary') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty state shown when no phone numbers exist -->
            <div v-else class="rounded-xl border border-dashed p-6 text-sm text-muted-foreground">
                {{ emptyMessage }}
            </div>

            <!-- Root-level validation error -->
            <p v-if="errors?.[rootErrorKey]" class="text-sm text-red-500">
                {{ errors[rootErrorKey] }}
            </p>
        </CardContent>
    </Card>
</template>

<script setup>
// Vue computed helper used for two-way binding
import { computed } from 'vue'

// Shared UI components
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'

// Component props definition
const props = defineProps({

    // Bound phone number array from the parent component
    modelValue: {
        type: Array,
        default: () => [],
    },

    // Validation errors passed down from the parent form
    errors: {
        type: Object,
        default: () => ({}),
    },

    // Card title
    title: {
        type: String,
        default: 'Phone Numbers',
    },

    // Card description
    description: {
        type: String,
        default: 'Add one or more phone numbers. Only one phone number can be primary.',
    },

    // Add button label
    addButtonLabel: {
        type: String,
        default: 'Add Phone Number',
    },

    // Individual item label
    itemLabel: {
        type: String,
        default: 'Phone',
    },

    // Empty-state message
    emptyMessage: {
        type: String,
        default: 'No phone numbers added yet.',
    },

    // Root validation key used for nested errors
    rootErrorKey: {
        type: String,
        default: 'phone_numbers',
    },

    // Available phone type dropdown options
    phoneTypeOptions: {
        type: Array,
        default: () => [
            { value: 'mobile', label: 'Mobile' },
            { value: 'work', label: 'Work' },
            { value: 'home', label: 'Home' },
            { value: 'fax', label: 'Fax' },
            { value: 'other', label: 'Other' },
        ],
    },
})

// Emit used for v-model updates
const emit = defineEmits(['update:modelValue'])

// Generates a unique ID prefix for form accessibility
const uid = Math.random().toString(36).slice(2, 9)

// Computed wrapper around modelValue for two-way binding
const internalValue = computed({
    get: () => props.modelValue ?? [],
    set: (value) => emit('update:modelValue', value),
})

// Returns a new blank phone number object
function createEmptyPhoneNumber(isPrimary = false) {
    return {
        id: null,
        phone_number: '',
        phone_type: '',
        is_primary: isPrimary,
        extension: '',
        notes: '',
    }
}

// Adds a new phone number row
// The first phone number automatically becomes primary
function addPhoneNumber() {
    internalValue.value = [
        ...internalValue.value,
        createEmptyPhoneNumber(internalValue.value.length === 0),
    ]
}

// Removes a phone number from the list
function removePhoneNumber(index) {
    const updated = [...internalValue.value]

    // Track whether the removed number was the primary number
    const removedWasPrimary = updated[index]?.is_primary ?? false

    updated.splice(index, 1)

    // If the primary number was removed,
    // automatically assign the first remaining number as primary
    if (removedWasPrimary && updated.length > 0) {
        internalValue.value = updated.map((phone, phoneIndex) => ({
            ...phone,
            is_primary: phoneIndex === 0,
        }))

        return
    }

    internalValue.value = updated
}

// Sets one phone number as primary and clears all others
function setPrimaryPhone(index) {
    internalValue.value = internalValue.value.map((phone, phoneIndex) => ({
        ...phone,
        is_primary: phoneIndex === index,
    }))
}

// Returns a validation error for a specific field
function fieldError(index, field) {
    return props.errors?.[`${props.rootErrorKey}.${index}.${field}`]
}

// Local validation helper used by parent forms
function validate() {
    let hasError = false

    // Determine which rows actually contain user data
    const filledPhoneRows = internalValue.value.filter((phone) => {
        return (
            phone.phone_number?.trim() !== '' ||
            phone.phone_type?.trim() !== '' ||
            phone.extension?.trim() !== '' ||
            phone.notes?.trim() !== ''
        )
    })

    // Empty phone section is considered valid
    if (filledPhoneRows.length === 0) {
        return true
    }

    // Validate required fields for partially completed rows
    internalValue.value.forEach((phone) => {
        const hasAnyValue =
            phone.phone_number?.trim() !== '' ||
            phone.phone_type?.trim() !== '' ||
            phone.extension?.trim() !== '' ||
            phone.notes?.trim() !== ''

        // Phone number is required if any phone data exists
        if (hasAnyValue && (!phone.phone_number || phone.phone_number.trim() === '')) {
            hasError = true
        }
    })

    // Ensure exactly one primary phone number exists
    const primaryCount = filledPhoneRows.filter((phone) => phone.is_primary).length

    if (filledPhoneRows.length > 0 && primaryCount !== 1) {
        hasError = true
    }

    return !hasError
}

// Expose validation method to parent components
defineExpose({
    validate,
})
</script>