<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import CustomFieldAdminForm from '@/components/custom-fields/CustomFieldAdminForm.vue'
import FormActions from '@/components/forms/FormActions.vue'
import ValidationSummary from '@/components/forms/ValidationSummary.vue'
import PageContainer from '@/components/layout/PageContainer.vue'
import PageHeader from '@/components/layout/PageHeader.vue'

const form = useForm({
    entity_type: 'person',
    name: '',
    field_type: 'text',
    description: '',
    placeholder: '',
    is_required: false,
    is_active: true,
    is_list_column: false,
    is_searchable: false,
    is_filterable: false,
    sort_order: 0,
    options: [],
})

function submit(): void {
    form.post('/admin/custom-fields')
}
</script>

<template>
    <PageContainer size="default">
        <PageHeader
            title="Create Custom Field"
            description="Add installation-specific information to Person or Position records without changing the application code."
            eyebrow="Administration"
            back-href="/admin/custom-fields"
            back-label="Custom Fields"
        />

        <form class="space-y-6" @submit.prevent="submit">
            <ValidationSummary :errors="form.errors" />
            <CustomFieldAdminForm :form="form" />
            <FormActions cancel-href="/admin/custom-fields" submit-label="Create Field" :processing="form.processing" :dirty="form.isDirty" />
        </form>
    </PageContainer>
</template>
