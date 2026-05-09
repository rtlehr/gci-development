<template>
    <!-- Main wrapper card for the address management component -->
    <Card class="rounded-xl">
        <CardHeader>
            <!-- Header section with title/description and Add button -->
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div>
                    <CardTitle>{{ title }}</CardTitle>

                    <!-- Component description text -->
                    <CardDescription>
                        {{ description }}
                    </CardDescription>
                </div>

                <!-- Adds a new address entry -->
                <Button type="button" variant="outline" @click="addAddress">
                    {{ addButtonLabel }}
                </Button>
            </div>
        </CardHeader>

        <CardContent class="space-y-4">

            <!-- Only show address list if addresses exist -->
            <div
                v-if="internalValue.length"
                class="space-y-4"
            >
                <!-- Loop through all addresses -->
                <div
                    v-for="(address, index) in internalValue"
                    :key="address.id ?? `address-${index}`"
                    class="rounded-xl border p-4 space-y-4"
                >
                    <!-- Address item header -->
                    <div class="flex items-center justify-between">
                        <h3 class="font-medium">{{ itemLabel }} {{ index + 1 }}</h3>

                        <!-- Remove current address -->
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            @click="removeAddress(index)"
                        >
                            Remove
                        </Button>
                    </div>

                    <!-- Main address fields grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">

                        <!-- Address Type -->
                        <div class="space-y-2">
                            <Label :for="`address-type-${uid}-${index}`">Address Type</Label>

                            <select
                                :id="`address-type-${uid}-${index}`"
                                v-model="address.address_type"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                :class="fieldError(index, 'address_type') ? 'border-red-500' : ''"
                            >
                                <option value="">Select type</option>

                                <!-- Populate address type dropdown -->
                                <option
                                    v-for="option in addressTypeOptions"
                                    :key="option.value"
                                    :value="option.value"
                                >
                                    {{ option.label }}
                                </option>
                            </select>

                            <!-- Validation error display -->
                            <p
                                v-if="fieldError(index, 'address_type')"
                                class="text-sm text-red-500"
                            >
                                {{ fieldError(index, 'address_type') }}
                            </p>
                        </div>

                        <!-- Address Line 1 -->
                        <div class="space-y-2 xl:col-span-3">
                            <Label :for="`address-line1-${uid}-${index}`">
                                Address Line 1 <span class="text-red-500">*</span>
                            </Label>

                            <Input
                                :id="`address-line1-${uid}-${index}`"
                                v-model="address.line_1"
                                :class="fieldError(index, 'line_1') ? 'border-red-500' : ''"
                            />

                            <!-- Validation error display -->
                            <p
                                v-if="fieldError(index, 'line_1')"
                                class="text-sm text-red-500"
                            >
                                {{ fieldError(index, 'line_1') }}
                            </p>
                        </div>

                        <!-- Address Line 2 -->
                        <div class="space-y-2 xl:col-span-4">
                            <Label :for="`address-line2-${uid}-${index}`">Address Line 2</Label>

                            <Input
                                :id="`address-line2-${uid}-${index}`"
                                v-model="address.line_2"
                                :class="fieldError(index, 'line_2') ? 'border-red-500' : ''"
                            />

                            <!-- Validation error display -->
                            <p
                                v-if="fieldError(index, 'line_2')"
                                class="text-sm text-red-500"
                            >
                                {{ fieldError(index, 'line_2') }}
                            </p>
                        </div>

                        <!-- City -->
                        <div class="space-y-2">
                            <Label :for="`address-city-${uid}-${index}`">City</Label>

                            <Input
                                :id="`address-city-${uid}-${index}`"
                                v-model="address.city"
                                :class="fieldError(index, 'city') ? 'border-red-500' : ''"
                            />

                            <!-- Validation error display -->
                            <p
                                v-if="fieldError(index, 'city')"
                                class="text-sm text-red-500"
                            >
                                {{ fieldError(index, 'city') }}
                            </p>
                        </div>

                        <!-- State -->
                        <div class="space-y-2">
                            <Label :for="`address-state-${uid}-${index}`">State</Label>

                            <Input
                                :id="`address-state-${uid}-${index}`"
                                v-model="address.state"
                                :class="fieldError(index, 'state') ? 'border-red-500' : ''"
                            />

                            <!-- Validation error display -->
                            <p
                                v-if="fieldError(index, 'state')"
                                class="text-sm text-red-500"
                            >
                                {{ fieldError(index, 'state') }}
                            </p>
                        </div>

                        <!-- Postal Code -->
                        <div class="space-y-2">
                            <Label :for="`address-postal-${uid}-${index}`">Postal Code</Label>

                            <Input
                                :id="`address-postal-${uid}-${index}`"
                                v-model="address.postal_code"
                                :class="fieldError(index, 'postal_code') ? 'border-red-500' : ''"
                            />

                            <!-- Validation error display -->
                            <p
                                v-if="fieldError(index, 'postal_code')"
                                class="text-sm text-red-500"
                            >
                                {{ fieldError(index, 'postal_code') }}
                            </p>
                        </div>

                        <!-- Country -->
                        <div class="space-y-2">
                            <Label :for="`address-country-${uid}-${index}`">Country</Label>

                            <Input
                                :id="`address-country-${uid}-${index}`"
                                v-model="address.country"
                                :class="fieldError(index, 'country') ? 'border-red-500' : ''"
                            />

                            <!-- Validation error display -->
                            <p
                                v-if="fieldError(index, 'country')"
                                class="text-sm text-red-500"
                            >
                                {{ fieldError(index, 'country') }}
                            </p>
                        </div>
                    </div>

                    <!-- Notes and Primary Address section -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                        <!-- Notes -->
                        <div class="space-y-2">
                            <Label :for="`address-notes-${uid}-${index}`">Notes</Label>

                            <Textarea
                                :id="`address-notes-${uid}-${index}`"
                                v-model="address.notes"
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

                        <!-- Primary Address Toggle -->
                        <div class="space-y-2">
                            <Label>Primary Address</Label>

                            <div class="flex items-center gap-3 rounded-md border p-3">
                                <input
                                    :id="`address-primary-${uid}-${index}`"
                                    :checked="address.is_primary"
                                    type="checkbox"
                                    class="h-4 w-4"

                                    <!-- Ensures only one address is marked primary -->
                                    @change="setPrimaryAddress(index)"
                                />

                                <Label :for="`address-primary-${uid}-${index}`" class="cursor-pointer">
                                    Make this the primary address
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

            <!-- Empty state when no addresses exist -->
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

    // Bound address array from parent component
    modelValue: {
        type: Array,
        default: () => [],
    },

    // Validation errors passed from parent form
    errors: {
        type: Object,
        default: () => ({}),
    },

    // Display title
    title: {
        type: String,
        default: 'Addresses',
    },

    // Description shown under title
    description: {
        type: String,
        default: 'Add one or more addresses. Only one address can be primary.',
    },

    // Add button label
    addButtonLabel: {
        type: String,
        default: 'Add Address',
    },

    // Label used for each address card
    itemLabel: {
        type: String,
        default: 'Address',
    },

    // Message displayed when no addresses exist
    emptyMessage: {
        type: String,
        default: 'No addresses added yet.',
    },

    // Root validation key
    rootErrorKey: {
        type: String,
        default: 'addresses',
    },

    // Dropdown options for address type
    addressTypeOptions: {
        type: Array,
        default: () => [
            { value: 'home', label: 'Home' },
            { value: 'work', label: 'Work' },
            { value: 'mailing', label: 'Mailing' },
            { value: 'other', label: 'Other' },
        ],
    },
})

// Emit used for v-model support
const emit = defineEmits(['update:modelValue'])

// Unique ID used for form field accessibility
const uid = Math.random().toString(36).slice(2, 9)

// Computed wrapper around modelValue for two-way binding
const internalValue = computed({
    get: () => props.modelValue ?? [],
    set: (value) => emit('update:modelValue', value),
})

// Returns a blank address object
function createEmptyAddress(isPrimary = false) {
    return {
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
    }
}

// Adds a new address to the list
// First address automatically becomes primary
function addAddress() {
    internalValue.value = [
        ...internalValue.value,
        createEmptyAddress(internalValue.value.length === 0),
    ]
}

// Removes an address from the list
function removeAddress(index) {
    const updated = [...internalValue.value]

    // Check if the removed address was primary
    const removedWasPrimary = updated[index]?.is_primary ?? false

    updated.splice(index, 1)

    // If the primary address was removed,
    // automatically set the first remaining address as primary
    if (removedWasPrimary && updated.length > 0) {
        internalValue.value = updated.map((address, addressIndex) => ({
            ...address,
            is_primary: addressIndex === 0,
        }))

        return
    }

    internalValue.value = updated
}

// Sets a single address as primary
// All others are automatically cleared
function setPrimaryAddress(index) {
    internalValue.value = internalValue.value.map((address, addressIndex) => ({
        ...address,
        is_primary: addressIndex === index,
    }))
}

// Returns validation error for a specific field
function fieldError(index, field) {
    return props.errors?.[`${props.rootErrorKey}.${index}.${field}`]
}

// Local component validation helper
function validate() {
    let hasError = false

    // Determine which rows actually contain data
    const filledRows = internalValue.value.filter((address) => {
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

    // Empty address sections are considered valid
    if (filledRows.length === 0) {
        return true
    }

    // Validate required fields on partially completed rows
    internalValue.value.forEach((address) => {
        const hasAnyValue =
            address.line_1?.trim() !== '' ||
            address.line_2?.trim() !== '' ||
            address.city?.trim() !== '' ||
            address.state?.trim() !== '' ||
            address.postal_code?.trim() !== '' ||
            address.country?.trim() !== '' ||
            address.notes?.trim() !== ''

        // Address Line 1 is required if any address data exists
        if (hasAnyValue && (!address.line_1 || address.line_1.trim() === '')) {
            hasError = true
        }
    })

    // Ensure exactly one primary address exists
    const primaryCount = filledRows.filter((address) => address.is_primary).length

    if (filledRows.length > 0 && primaryCount !== 1) {
        hasError = true
    }

    return !hasError
}

// Expose validation method to parent components
defineExpose({
    validate,
})
</script>