<script setup lang="ts">
import FormField from '@/components/forms/FormField.vue'
import FormSection from '@/components/forms/FormSection.vue'
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'

 type GenericRecord = Record<string, any>

const props = withDefaults(defineProps<{
    fields?: GenericRecord[]
    modelValue?: Record<string, any>
    errors?: Record<string, string>
}>(), {
    fields: () => [],
    modelValue: () => ({}),
    errors: () => ({}),
})

const emit = defineEmits<{
    'update:modelValue': [value: Record<string, any>]
}>()

function fieldKey(field: GenericRecord): string {
    return String(field.id)
}

function errorFor(field: GenericRecord): string | undefined {
    return props.errors[`custom_fields.${field.id}`]
}

function valueFor(field: GenericRecord): any {
    const value = props.modelValue[fieldKey(field)]
    return field.field_type === 'checkbox' ? (Array.isArray(value) ? value : []) : (value ?? '')
}

function updateValue(field: GenericRecord, value: any): void {
    emit('update:modelValue', {
        ...props.modelValue,
        [fieldKey(field)]: value,
    })
}

function onCheckboxChange(field: GenericRecord, optionValue: string, event: Event): void {
    toggleCheckbox(field, optionValue, (event.target as HTMLInputElement).checked)
}

function toggleCheckbox(field: GenericRecord, optionValue: string, checked: boolean): void {
    const current = [...valueFor(field)]
    const next = checked
        ? Array.from(new Set([...current, optionValue]))
        : current.filter((value) => value !== optionValue)

    updateValue(field, next)
}
</script>

<template>
    <FormSection
        title="Other Information"
        description="Additional information configured by your organization."
    >
        <div v-if="fields.length" class="grid gap-6 md:grid-cols-2">
            <div
                v-for="field in fields"
                :key="field.id"
                :class="field.field_type === 'textarea' || field.field_type === 'checkbox' || field.field_type === 'radio' ? 'md:col-span-2' : ''"
            >
                <FormField
                    :label="field.name"
                    :for-id="`custom_field_${field.id}`"
                    :error="errorFor(field)"
                    :description="field.description || undefined"
                    :required="Boolean(field.is_required)"
                >
                    <template #default="{ describedBy, invalid }">
                        <Input
                            v-if="field.field_type === 'text'"
                            :id="`custom_field_${field.id}`"
                            :model-value="valueFor(field)"
                            type="text"
                            :placeholder="field.placeholder || undefined"
                            :aria-describedby="describedBy"
                            :aria-invalid="invalid"
                            @update:model-value="updateValue(field, $event)"
                        />

                        <Textarea
                            v-else-if="field.field_type === 'textarea'"
                            :id="`custom_field_${field.id}`"
                            :model-value="valueFor(field)"
                            :placeholder="field.placeholder || undefined"
                            rows="5"
                            :aria-describedby="describedBy"
                            :aria-invalid="invalid"
                            @update:model-value="updateValue(field, $event)"
                        />

                        <Input
                            v-else-if="field.field_type === 'date'"
                            :id="`custom_field_${field.id}`"
                            :model-value="valueFor(field)"
                            type="date"
                            :aria-describedby="describedBy"
                            :aria-invalid="invalid"
                            @update:model-value="updateValue(field, $event)"
                        />

                        <div
                            v-else-if="field.field_type === 'radio'"
                            :id="`custom_field_${field.id}`"
                            class="grid gap-2 sm:grid-cols-2"
                            role="radiogroup"
                            :aria-labelledby="`custom_field_${field.id}-label`"
                            :aria-required="Boolean(field.is_required)"
                            :aria-invalid="invalid"
                            :aria-describedby="describedBy"
                        >
                            <label
                                v-for="option in (field.active_options ?? field.activeOptions ?? field.options ?? [])"
                                :key="option.id"
                                :for="`custom_field_${field.id}_option_${option.id}`"
                                class="flex cursor-pointer items-center gap-3 rounded-lg border p-3 text-sm hover:bg-muted/40 focus-within:ring-2 focus-within:ring-ring"
                            >
                                <input
                                    :id="`custom_field_${field.id}_option_${option.id}`"
                                    type="radio"
                                    :name="`custom_field_${field.id}`"
                                    :value="option.value"
                                    :checked="valueFor(field) === option.value"
                                    @change="updateValue(field, option.value)"
                                >
                                <span>{{ option.label }}</span>
                            </label>
                        </div>

                        <div
                            v-else-if="field.field_type === 'checkbox'"
                            :id="`custom_field_${field.id}`"
                            class="grid gap-2 sm:grid-cols-2"
                            role="group"
                            :aria-labelledby="`custom_field_${field.id}-label`"
                            :aria-required="Boolean(field.is_required)"
                            :aria-invalid="invalid"
                            :aria-describedby="describedBy"
                        >
                            <label
                                v-for="option in (field.active_options ?? field.activeOptions ?? field.options ?? [])"
                                :key="option.id"
                                :for="`custom_field_${field.id}_option_${option.id}`"
                                class="flex cursor-pointer items-center gap-3 rounded-lg border p-3 text-sm hover:bg-muted/40 focus-within:ring-2 focus-within:ring-ring"
                            >
                                <input
                                    :id="`custom_field_${field.id}_option_${option.id}`"
                                    type="checkbox"
                                    :value="option.value"
                                    :checked="valueFor(field).includes(option.value)"
                                    @change="onCheckboxChange(field, option.value, $event)"
                                >
                                <span>{{ option.label }}</span>
                            </label>
                        </div>
                    </template>
                </FormField>
            </div>
        </div>

        <p v-else class="text-sm text-muted-foreground">
            No custom fields are configured for this record type.
        </p>
    </FormSection>
</template>
