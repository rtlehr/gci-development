<script setup lang="ts">
import { computed, ref } from 'vue'
import { ArrowDown, ArrowUp, Plus, Trash2 } from 'lucide-vue-next'
import FormField from '@/components/forms/FormField.vue'
import FormSection from '@/components/forms/FormSection.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select'

type GenericRecord = Record<string, any>

const props = defineProps<{
    form: GenericRecord
    typeLocked?: boolean
}>()

const usesOptions = computed(() => ['radio', 'checkbox'].includes(props.form.field_type))
const announcement = ref('')

function announce(message: string): void {
    announcement.value = ''
    requestAnimationFrame(() => { announcement.value = message })
}

function addOption(): void {
    props.form.options.push({ id: null, label: '', is_active: true })
    announce(`Option ${props.form.options.length} added.`)
}

function removeOption(index: number): void {
    props.form.options.splice(index, 1)
    announce(`Option ${index + 1} removed.`)
}

function moveOption(index: number, direction: -1 | 1): void {
    const target = index + direction
    if (target < 0 || target >= props.form.options.length) return
    const [option] = props.form.options.splice(index, 1)
    props.form.options.splice(target, 0, option)
    announce(`Option moved to position ${target + 1}.`)
}
</script>

<template>
    <div class="space-y-6">
        <p class="sr-only" aria-live="polite" aria-atomic="true">{{ announcement }}</p>
        <FormSection
            title="Field Details"
            description="Choose where the field appears and how users enter information."
        >
            <div class="grid gap-5 md:grid-cols-2">
                <FormField label="Applies To" for-id="entity_type" :error="form.errors.entity_type" required>
                    <Select v-model="form.entity_type" :disabled="typeLocked">
                        <SelectTrigger id="entity_type"><SelectValue placeholder="Select record type" /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="person">Person</SelectItem>
                            <SelectItem value="position">Position</SelectItem>
                        </SelectContent>
                    </Select>
                </FormField>

                <FormField label="Field Type" for-id="field_type" :error="form.errors.field_type" required>
                    <Select v-model="form.field_type" :disabled="typeLocked">
                        <SelectTrigger id="field_type"><SelectValue placeholder="Select field type" /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="text">Text Field</SelectItem>
                            <SelectItem value="textarea">Multiline Text</SelectItem>
                            <SelectItem value="radio">Radio Group</SelectItem>
                            <SelectItem value="checkbox">Checkbox Group</SelectItem>
                            <SelectItem value="date">Date Picker</SelectItem>
                        </SelectContent>
                    </Select>
                    <p v-if="typeLocked" class="mt-1 text-xs text-muted-foreground">
                        Field type is locked because this field already contains saved values.
                    </p>
                </FormField>

                <FormField label="Field Label" for-id="name" :error="form.errors.name" required>
                    <Input id="name" v-model="form.name" autocomplete="off" />
                </FormField>

                <FormField label="Display Order" for-id="sort_order" :error="form.errors.sort_order" description="Lower numbers appear first.">
                    <Input id="sort_order" v-model.number="form.sort_order" type="number" min="0" max="9999" />
                </FormField>

                <div class="md:col-span-2">
                    <FormField label="Description / Help Text" for-id="description" :error="form.errors.description">
                        <Textarea id="description" v-model="form.description" rows="3" />
                    </FormField>
                </div>

                <div v-if="['text', 'textarea'].includes(form.field_type)" class="md:col-span-2">
                    <FormField label="Placeholder" for-id="placeholder" :error="form.errors.placeholder" description="Optional example or hint shown inside the field.">
                        <Input id="placeholder" v-model="form.placeholder" />
                    </FormField>
                </div>
            </div>

            <div class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                <label class="flex cursor-pointer items-start gap-3 rounded-lg border p-4">
                    <input id="custom-field-required" v-model="form.is_required" type="checkbox" class="mt-1">
                    <span>
                        <span class="block text-sm font-medium">Required</span>
                        <span class="block text-xs text-muted-foreground">Users must complete this field before saving the record.</span>
                    </span>
                </label>

                <label class="flex cursor-pointer items-start gap-3 rounded-lg border p-4">
                    <input id="custom-field-active" v-model="form.is_active" type="checkbox" class="mt-1">
                    <span>
                        <span class="block text-sm font-medium">Active</span>
                        <span class="block text-xs text-muted-foreground">Active fields appear in Person or Position screens.</span>
                    </span>
                </label>

                <label class="flex cursor-pointer items-start gap-3 rounded-lg border p-4">
                    <input id="custom-field-list-column" v-model="form.is_list_column" type="checkbox" class="mt-1">
                    <span>
                        <span class="block text-sm font-medium">Available as List Column</span>
                        <span class="block text-xs text-muted-foreground">Makes this field available in Person or Position column settings and CSV exports.</span>
                    </span>
                </label>

                <label class="flex cursor-pointer items-start gap-3 rounded-lg border p-4" :class="!form.is_list_column ? 'opacity-60' : ''">
                    <input id="custom-field-searchable" v-model="form.is_searchable" type="checkbox" class="mt-1" :disabled="!form.is_list_column">
                    <span>
                        <span class="block text-sm font-medium">Searchable</span>
                        <span class="block text-xs text-muted-foreground">Includes this field in the list search when the column is visible.</span>
                    </span>
                </label>

                <label class="flex cursor-pointer items-start gap-3 rounded-lg border p-4">
                    <input id="custom-field-filterable" v-model="form.is_filterable" type="checkbox" class="mt-1">
                    <span>
                        <span class="block text-sm font-medium">Filterable</span>
                        <span class="block text-xs text-muted-foreground">Adds a dedicated filter for this field on the Person or Position list.</span>
                    </span>
                </label>
            </div>
        </FormSection>

        <FormSection
            v-if="usesOptions"
            title="Field Options"
            description="Add the choices users can select. Existing choices can be deactivated without losing historical values."
        >
            <div class="space-y-3">
                <div
                    v-for="(option, index) in form.options"
                    :key="option.id ?? `new-${index}`"
                    class="grid gap-3 rounded-lg border p-4 sm:grid-cols-[minmax(0,1fr)_auto_auto_auto] sm:items-center"
                >
                    <Input :id="`custom-field-option-${index}-label`" v-model="option.label" :aria-label="`Option ${index + 1} label`" :placeholder="`Option ${index + 1}`" />
                    <label class="flex items-center gap-2 text-sm" :for="`custom-field-option-${index}-active`">
                        <input :id="`custom-field-option-${index}-active`" v-model="option.is_active" type="checkbox">
                        Active
                    </label>
                    <div class="flex items-center gap-1">
                        <Button type="button" variant="ghost" size="icon" :aria-label="`Move option ${index + 1} up`" :disabled="index === 0" @click="moveOption(index, -1)">
                            <ArrowUp class="h-4 w-4" aria-hidden="true" />
                        </Button>
                        <Button type="button" variant="ghost" size="icon" :aria-label="`Move option ${index + 1} down`" :disabled="index === form.options.length - 1" @click="moveOption(index, 1)">
                            <ArrowDown class="h-4 w-4" aria-hidden="true" />
                        </Button>
                    </div>
                    <Button type="button" variant="ghost" size="icon" :aria-label="`Remove option ${index + 1}`" @click="removeOption(index)">
                        <Trash2 class="h-4 w-4" aria-hidden="true" />
                    </Button>
                </div>

                <Button type="button" variant="outline" @click="addOption">
                    <Plus class="mr-2 h-4 w-4" aria-hidden="true" />
                    Add Option
                </Button>
            </div>
        </FormSection>
    </div>
</template>
