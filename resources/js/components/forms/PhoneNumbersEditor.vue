<template>
    <Card class="rounded-xl">
        <CardHeader>
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div>
                    <CardTitle>{{ title }}</CardTitle>
                    <CardDescription>
                        {{ description }}
                    </CardDescription>
                </div>

                <Button type="button" variant="outline" @click="addPhoneNumber">
                    {{ addButtonLabel }}
                </Button>
            </div>
        </CardHeader>

        <CardContent class="space-y-4">
            <div
                v-if="internalValue.length"
                class="space-y-4"
            >
                <div
                    v-for="(phone, index) in internalValue"
                    :key="phone.id ?? `phone-${index}`"
                    class="rounded-xl border p-4 space-y-4"
                >
                    <div class="flex items-center justify-between">
                        <h3 class="font-medium">{{ itemLabel }} {{ index + 1 }}</h3>

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
                            <Label :for="`phone-number-${uid}-${index}`">
                                Phone Number <span class="text-red-500">*</span>
                            </Label>
                            <Input
                                :id="`phone-number-${uid}-${index}`"
                                v-model="phone.phone_number"
                                :class="fieldError(index, 'phone_number') ? 'border-red-500' : ''"
                            />
                            <p
                                v-if="fieldError(index, 'phone_number')"
                                class="text-sm text-red-500"
                            >
                                {{ fieldError(index, 'phone_number') }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label :for="`phone-type-${uid}-${index}`">Phone Type</Label>
                            <select
                                :id="`phone-type-${uid}-${index}`"
                                v-model="phone.phone_type"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                :class="fieldError(index, 'phone_type') ? 'border-red-500' : ''"
                            >
                                <option value="">Select type</option>
                                <option
                                    v-for="option in phoneTypeOptions"
                                    :key="option.value"
                                    :value="option.value"
                                >
                                    {{ option.label }}
                                </option>
                            </select>
                            <p
                                v-if="fieldError(index, 'phone_type')"
                                class="text-sm text-red-500"
                            >
                                {{ fieldError(index, 'phone_type') }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label :for="`phone-extension-${uid}-${index}`">Extension</Label>
                            <Input
                                :id="`phone-extension-${uid}-${index}`"
                                v-model="phone.extension"
                                :class="fieldError(index, 'extension') ? 'border-red-500' : ''"
                            />
                            <p
                                v-if="fieldError(index, 'extension')"
                                class="text-sm text-red-500"
                            >
                                {{ fieldError(index, 'extension') }}
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label :for="`phone-notes-${uid}-${index}`">Notes</Label>
                            <Textarea
                                :id="`phone-notes-${uid}-${index}`"
                                v-model="phone.notes"
                                rows="3"
                                :class="fieldError(index, 'notes') ? 'border-red-500' : ''"
                            />
                            <p
                                v-if="fieldError(index, 'notes')"
                                class="text-sm text-red-500"
                            >
                                {{ fieldError(index, 'notes') }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label>Primary Number</Label>

                            <div class="flex items-center gap-3 rounded-md border p-3">
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

            <div v-else class="rounded-xl border border-dashed p-6 text-sm text-muted-foreground">
                {{ emptyMessage }}
            </div>

            <p v-if="errors?.[rootErrorKey]" class="text-sm text-red-500">
                {{ errors[rootErrorKey] }}
            </p>
        </CardContent>
    </Card>
</template>

<script setup>
import { computed } from 'vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'

const props = defineProps({
    modelValue: {
        type: Array,
        default: () => [],
    },
    errors: {
        type: Object,
        default: () => ({}),
    },
    title: {
        type: String,
        default: 'Phone Numbers',
    },
    description: {
        type: String,
        default: 'Add one or more phone numbers. Only one phone number can be primary.',
    },
    addButtonLabel: {
        type: String,
        default: 'Add Phone Number',
    },
    itemLabel: {
        type: String,
        default: 'Phone',
    },
    emptyMessage: {
        type: String,
        default: 'No phone numbers added yet.',
    },
    rootErrorKey: {
        type: String,
        default: 'phone_numbers',
    },
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

const emit = defineEmits(['update:modelValue'])

const uid = Math.random().toString(36).slice(2, 9)

const internalValue = computed({
    get: () => props.modelValue ?? [],
    set: (value) => emit('update:modelValue', value),
})

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

function addPhoneNumber() {
    internalValue.value = [
        ...internalValue.value,
        createEmptyPhoneNumber(internalValue.value.length === 0),
    ]
}

function removePhoneNumber(index) {
    const updated = [...internalValue.value]
    const removedWasPrimary = updated[index]?.is_primary ?? false

    updated.splice(index, 1)

    if (removedWasPrimary && updated.length > 0) {
        internalValue.value = updated.map((phone, phoneIndex) => ({
            ...phone,
            is_primary: phoneIndex === 0,
        }))
        return
    }

    internalValue.value = updated
}

function setPrimaryPhone(index) {
    internalValue.value = internalValue.value.map((phone, phoneIndex) => ({
        ...phone,
        is_primary: phoneIndex === index,
    }))
}

function fieldError(index, field) {
    return props.errors?.[`${props.rootErrorKey}.${index}.${field}`]
}

function validate() {
    let hasError = false

    const filledPhoneRows = internalValue.value.filter((phone) => {
        return (
            phone.phone_number?.trim() !== '' ||
            phone.phone_type?.trim() !== '' ||
            phone.extension?.trim() !== '' ||
            phone.notes?.trim() !== ''
        )
    })

    if (filledPhoneRows.length === 0) {
        return true
    }

    internalValue.value.forEach((phone) => {
        const hasAnyValue =
            phone.phone_number?.trim() !== '' ||
            phone.phone_type?.trim() !== '' ||
            phone.extension?.trim() !== '' ||
            phone.notes?.trim() !== ''

        if (hasAnyValue && (!phone.phone_number || phone.phone_number.trim() === '')) {
            hasError = true
        }
    })

    const primaryCount = filledPhoneRows.filter((phone) => phone.is_primary).length

    if (filledPhoneRows.length > 0 && primaryCount !== 1) {
        hasError = true
    }

    return !hasError
}

defineExpose({
    validate,
})
</script>