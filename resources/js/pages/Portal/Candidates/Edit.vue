<template>
    <div class="mx-auto w-full max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div><h1 class="text-2xl font-semibold text-[#3a3a3a]">Edit Candidate</h1><p class="mt-1 text-sm text-muted-foreground">Update candidate details and workflow progress.</p></div>
            <Button as-child variant="outline"><Link href="/portal/candidates">Back to List</Link></Button>
        </div>
        <form @submit.prevent="submit" class="grid gap-6 lg:grid-cols-[270px_minmax(0,1fr)]">
            <PortalSectionNav title="Candidate sections" :items="sections" v-model="activeSection" />
            <div class="min-w-0 space-y-6">
                <section v-show="activeSection === 'details'" class="rounded-xl border bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-semibold">Candidate Details</h2><p class="mb-6 text-sm text-muted-foreground">Person, position, status, submission, and start information.</p>
                    <div class="grid gap-5 md:grid-cols-2">
                        <FormField label="Person" for-id="portal-candidate-person" :error="form.errors.person_id" required>
                            <template #default="{ describedBy, invalid }">
                                <select id="portal-candidate-person" v-model="form.person_id" class="h-10 w-full rounded-md border bg-background px-3 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring" :aria-describedby="describedBy" :aria-invalid="invalid" required>
                                    <option value="">Select person</option>
                                    <option v-for="person in people" :key="person.id" :value="person.id">{{ person.full_name ?? `${person.first_name} ${person.last_name}` }}</option>
                                </select>
                            </template>
                        </FormField>

                        <FormField label="Position" for-id="portal-candidate-position" :error="form.errors.position_id" required>
                            <template #default="{ describedBy, invalid }">
                                <select id="portal-candidate-position" v-model="form.position_id" class="h-10 w-full rounded-md border bg-background px-3 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring" :aria-describedby="describedBy" :aria-invalid="invalid" required>
                                    <option value="">Select position</option>
                                    <option v-for="position in positions" :key="position.id" :value="position.id">{{ position.job_title ?? position.title ?? `Position #${position.id}` }}</option>
                                </select>
                            </template>
                        </FormField>

                        <FormField label="Status" for-id="portal-candidate-status" :error="form.errors.status" required>
                            <template #default="{ describedBy, invalid }">
                                <select id="portal-candidate-status" v-model="form.status" class="h-10 w-full rounded-md border bg-background px-3 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring" :aria-describedby="describedBy" :aria-invalid="invalid" required>
                                    <option value="submitted">Submitted</option>
                                    <option value="selected">Selected</option>
                                    <option value="approved">Approved</option>
                                    <option value="assigned">Assigned</option>
                                </select>
                            </template>
                        </FormField>

                        <FormField label="Candidate FBR" for-id="portal-candidate-fbr" :error="form.errors.candidate_fbr">
                            <template #default="{ describedBy, invalid }">
                                <input id="portal-candidate-fbr" v-model="form.candidate_fbr" type="number" step="0.01" class="h-10 w-full rounded-md border bg-background px-3 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring" :aria-describedby="describedBy" :aria-invalid="invalid" />
                            </template>
                        </FormField>

                        <FormField label="Submitted At" for-id="portal-candidate-submitted-at" :error="form.errors.submitted_at">
                            <template #default="{ describedBy, invalid }">
                                <input id="portal-candidate-submitted-at" v-model="form.submitted_at" type="datetime-local" class="h-10 w-full rounded-md border bg-background px-3 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring" :aria-describedby="describedBy" :aria-invalid="invalid" />
                            </template>
                        </FormField>

                        <FormField label="Submitted By" for-id="portal-candidate-submitted-by" :error="form.errors.submitted_by_person_id">
                            <template #default="{ describedBy, invalid }">
                                <select id="portal-candidate-submitted-by" v-model="form.submitted_by_person_id" class="h-10 w-full rounded-md border bg-background px-3 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring" :aria-describedby="describedBy" :aria-invalid="invalid">
                                    <option value="">Select person</option>
                                    <option v-for="person in people" :key="person.id" :value="person.id">{{ person.full_name ?? `${person.first_name} ${person.last_name}` }}</option>
                                </select>
                            </template>
                        </FormField>

                        <FormField label="Scheduled Start Date" for-id="portal-candidate-start-date" :error="form.errors.scheduled_start_date">
                            <template #default="{ describedBy, invalid }">
                                <input id="portal-candidate-start-date" v-model="form.scheduled_start_date" type="date" class="h-10 w-full rounded-md border bg-background px-3 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring" :aria-describedby="describedBy" :aria-invalid="invalid" />
                            </template>
                        </FormField>

                        <div>
                            <p class="text-sm font-medium">Workflow</p>
                            <div class="mt-2 rounded-md border bg-muted/40 px-3 py-2 text-sm">{{ workflow?.name }} ({{ workflow?.code }})</div>
                        </div>
                    </div>
                </section>
                <section v-show="activeSection === 'steps'" class="rounded-xl border bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-semibold">Workflow Steps</h2><p class="mb-6 text-sm text-muted-foreground">Update status, dates, owners, notes, and comments.</p>
                    <CandidateWorkflowEditor v-model="form.step_events" :workflow-steps="workflowSteps" :existing-events="candidate.step_events ?? []" :people="people" />
                </section>
                <div class="flex items-center gap-3 border-t pt-5"><Button type="submit" :disabled="form.processing">Update Candidate</Button><Button as-child variant="outline"><Link href="/portal/candidates">Cancel</Link></Button></div>
            </div>
        </form>
    </div>
</template>
<script setup>
import { computed, ref } from 'vue'
import { Link, useForm, usePage } from '@inertiajs/vue3'
import { ListChecks, UserRound } from 'lucide-vue-next'
import CandidateWorkflowEditor from '@/components/forms/CandidateWorkflowEditor.vue'
import FormField from '@/components/forms/FormField.vue'
import PortalSectionNav from '@/components/portal/PortalSectionNav.vue'
import { Button } from '@/components/ui/button'
const props=defineProps({candidate:Object,people:Array,positions:Array,workflow:Object,workflowSteps:Array})
const page = usePage()
const requestedSection = new URLSearchParams(page.url.split('?')[1] ?? '').get('section')
const activeSection=ref(requestedSection === 'steps' ? 'steps' : 'details')
const sections=computed(()=>[{id:'details',label:'Candidate Details',description:'Person, position, and status.',icon:UserRound},{id:'steps',label:'Workflow Steps',description:'Status, dates, and notes.',icon:ListChecks,badge:props.workflowSteps?.length||undefined}])
const norm=v=>v?(v.length>=16?v.slice(0,16):v):''
const form=useForm({person_id:props.candidate.person_id??'',position_id:props.candidate.position_id??'',status:props.candidate.status??'submitted',candidate_fbr:props.candidate.candidate_fbr??'',submitted_at:norm(props.candidate.submitted_at),submitted_by_person_id:props.candidate.submitted_by_person_id??'',scheduled_start_date:props.candidate.scheduled_start_date??'',step_events:[]})
function submit(){form.put(`/portal/candidates/${props.candidate.id}`)}
</script>
