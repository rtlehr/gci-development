<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import CustomFieldAdminForm from '@/components/custom-fields/CustomFieldAdminForm.vue'
import FormActions from '@/components/forms/FormActions.vue'
import ValidationSummary from '@/components/forms/ValidationSummary.vue'
import PageContainer from '@/components/layout/PageContainer.vue'
import PageHeader from '@/components/layout/PageHeader.vue'

const props = defineProps<{ customField: Record<string, any> }>()

const form = useForm({
    entity_type: props.customField.entity_type,
    name: props.customField.name,
    field_type: props.customField.field_type,
    description: props.customField.description ?? '',
    placeholder: props.customField.placeholder ?? '',
    is_required: Boolean(props.customField.is_required),
    is_active: Boolean(props.customField.is_active),
    is_sensitive: Boolean(props.customField.is_sensitive),
    is_list_column: Boolean(props.customField.is_list_column),
    is_searchable: Boolean(props.customField.is_searchable),
    is_filterable: Boolean(props.customField.is_filterable),
    sort_order: props.customField.sort_order ?? 0,
    options: (props.customField.options ?? []).map((option: Record<string, any>) => ({
        id: option.id,
        label: option.label,
        is_active: Boolean(option.is_active),
    })),
})

function submit(): void {
    form.put(`/admin/custom-fields/${props.customField.id}`)
}
</script>

<template>
    <PageContainer size="default">
        <PageHeader
            :title="`Edit ${customField.name}`"
            :description="`System key: ${customField.key}`"
            eyebrow="Custom Fields"
            back-href="/admin/custom-fields"
            back-label="Custom Fields"
        />

        <form class="space-y-6" @submit.prevent="submit">
            <ValidationSummary :errors="form.errors" />
            <CustomFieldAdminForm :form="form" :type-locked="customField.values_count > 0" :sensitivity-locked="customField.values_count > 0" />
            <FormActions cancel-href="/admin/custom-fields" submit-label="Save Changes" :processing="form.processing" :dirty="form.isDirty" />
        </form>
    </PageContainer>
</template>
