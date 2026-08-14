<script setup lang="ts">
import { computed, ref } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import { BriefcaseBusiness, ListChecks } from 'lucide-vue-next'
import PortalSectionNav from '@/components/portal/PortalSectionNav.vue'
import BooleanField from '@/components/forms/BooleanField.vue'
import FormField from '@/components/forms/FormField.vue'
import FormGrid from '@/components/forms/FormGrid.vue'
import FormSection from '@/components/forms/FormSection.vue'
import ValidationSummary from '@/components/forms/ValidationSummary.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'

const props = defineProps<{ jobTitle: { id: number; name: string; description?: string | null; is_active?: boolean; sort_order?: number } }>()
const activeSection = ref<'details' | 'requirements'>('details')
const form = useForm({ name: props.jobTitle.name ?? '', description: props.jobTitle.description ?? '', is_active: props.jobTitle.is_active ?? true, sort_order: props.jobTitle.sort_order ?? 0 })
const sections = computed(() => [
    { id: 'details', title: 'Job Title Details', description: 'Name, description, order, and status.', icon: BriefcaseBusiness, complete: Boolean(form.name) },
    { id: 'requirements', title: 'Skills & Tasks', description: 'Manage default requirements and duties.', icon: ListChecks, badge: 'Manage' },
])
function setSection(value: string) { if (value === 'requirements') window.location.href = `/portal/job-titles/${props.jobTitle.id}#skills`; else activeSection.value = 'details' }
function submit(): void { form.put(`/portal/job-titles/${props.jobTitle.id}`) }
</script>

<template>
    <div class="mx-auto w-full max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"><div><div class="flex items-center gap-3"><h1 class="text-2xl font-semibold">Edit Job Title</h1><Badge :variant="jobTitle.is_active ? 'default' : 'secondary'">{{ jobTitle.is_active ? 'Active' : 'Inactive' }}</Badge></div><p class="mt-1 text-sm text-muted-foreground">Update {{ jobTitle.name }} and manage its default workflow requirements.</p></div><Button as-child variant="outline"><Link href="/portal/job-titles">Back to List</Link></Button></div>
        <form @submit.prevent="submit"><div class="grid gap-6 lg:grid-cols-[270px_minmax(0,1fr)]"><PortalSectionNav title="Job Title sections" :sections="sections" :active-section="activeSection" @update:active-section="setSection" /><div class="min-w-0 space-y-6"><ValidationSummary :errors="form.errors" /><FormSection title="Job Title Details" description="Update the title, description, display order, and availability."><FormGrid><FormField label="Name" for-id="name" :error="form.errors.name" required><template #default="{ describedBy, invalid }"><Input id="name" v-model="form.name" :aria-describedby="describedBy" :aria-invalid="invalid" /></template></FormField><FormField label="Sort Order" for-id="sort_order" :error="form.errors.sort_order" description="Lower numbers appear first."><template #default="{ describedBy, invalid }"><Input id="sort_order" v-model="form.sort_order" type="number" :aria-describedby="describedBy" :aria-invalid="invalid" /></template></FormField></FormGrid><FormField label="Description" for-id="description" :error="form.errors.description"><template #default="{ describedBy, invalid }"><Textarea id="description" v-model="form.description" rows="5" :aria-describedby="describedBy" :aria-invalid="invalid" /></template></FormField><BooleanField id="is_active" v-model="form.is_active" label="Active" description="Active Job Titles are available for positions." /></FormSection><div class="flex gap-3 border-t pt-5"><Button type="submit" :disabled="form.processing">{{ form.processing ? 'Saving…' : 'Update Job Title' }}</Button><Button as-child variant="outline"><Link href="/portal/job-titles">Cancel</Link></Button></div></div></div></form>
    </div>
</template>
