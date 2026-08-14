<template>
    <div class="mx-auto w-full max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-[#3a3a3a]">Create Candidate</h1>
                <p class="mt-1 text-sm text-muted-foreground">Add candidate details and configure the complete workflow.</p>
            </div>
            <Button as-child variant="outline"><Link href="/portal/candidates">Back to List</Link></Button>
        </div>

        <form @submit.prevent="submit" class="grid gap-6 lg:grid-cols-[270px_minmax(0,1fr)]">
            <PortalSectionNav title="Candidate sections" :items="sections" v-model="activeSection" />

            <div class="min-w-0 space-y-6">
                <section v-show="activeSection === 'details'" class="rounded-xl border bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-semibold">Candidate Details</h2>
                    <p class="mb-6 text-sm text-muted-foreground">Person, position, status, submission, and start information.</p>
                    <div class="grid gap-5 md:grid-cols-2">
                        <FormSelect id="portal-candidate-person" label="Person" v-model="form.person_id" :error="form.errors.person_id" required>
                            <option value="">Select person</option>
                            <option v-for="person in people" :key="person.id" :value="person.id">{{ person.full_name ?? `${person.first_name} ${person.last_name}` }}</option>
                        </FormSelect>
                        <FormSelect id="portal-candidate-position" label="Position" v-model="form.position_id" :error="form.errors.position_id" required>
                            <option value="">Select position</option>
                            <option v-for="position in positions" :key="position.id" :value="position.id">{{ position.job_title ?? position.title ?? `Position #${position.id}` }}</option>
                        </FormSelect>
                        <FormSelect id="portal-candidate-status" label="Candidate Status" v-model="form.status" :error="form.errors.status" required>
                            <option value="submitted">Submitted</option><option value="selected">Selected</option><option value="approved">Approved</option><option value="assigned">Assigned</option>
                        </FormSelect>
                        <FormInput id="portal-candidate-fbr" label="Candidate FBR" v-model="form.candidate_fbr" type="number" step="0.01" :error="form.errors.candidate_fbr" />
                        <FormInput id="portal-candidate-submitted-at" label="Submitted At" v-model="form.submitted_at" type="datetime-local" :error="form.errors.submitted_at" />
                        <FormSelect id="portal-candidate-submitted-by" label="Submitted By" v-model="form.submitted_by_person_id" :error="form.errors.submitted_by_person_id">
                            <option value="">Select person</option>
                            <option v-for="person in people" :key="person.id" :value="person.id">{{ person.full_name ?? `${person.first_name} ${person.last_name}` }}</option>
                        </FormSelect>
                        <FormInput id="portal-candidate-start-date" label="Scheduled Start Date" v-model="form.scheduled_start_date" type="date" :error="form.errors.scheduled_start_date" />
                    </div>
                </section>

                <section v-show="activeSection === 'workflow'" class="rounded-xl border bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-semibold">Workflow</h2>
                    <p class="mb-6 text-sm text-muted-foreground">Select the workflow used to track this candidate.</p>
                    <div class="grid gap-5 md:grid-cols-2">
                        <FormSelect v-if="can('view_admin')" id="portal-candidate-workflow" label="Selected Workflow" v-model="selectedWorkflowId" @change="changeWorkflow">
                            <option v-for="item in workflows" :key="item.id" :value="item.id">{{ item.name }}{{ item.is_primary ? ' (Primary)' : '' }}</option>
                        </FormSelect>
                        <div v-else><p class="text-sm font-medium">Selected Workflow</p><div class="mt-2 rounded-md border bg-muted/40 px-3 py-2 text-sm">{{ workflow?.name || '—' }}</div></div>
                        <div><p class="text-sm font-medium">Workflow Code</p><div class="mt-2 rounded-md border bg-muted/40 px-3 py-2 text-sm">{{ workflow?.code || '—' }}</div></div>
                    </div>
                    <p v-if="form.errors.workflow_id" class="mt-3 text-sm text-destructive">{{ form.errors.workflow_id }}</p>
                </section>

                <section v-show="activeSection === 'steps'" class="rounded-xl border bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-semibold">Workflow Steps</h2>
                    <p class="mb-6 text-sm text-muted-foreground">Configure statuses, dates, owners, notes, and comments.</p>
                    <CandidateWorkflowEditor v-model="form.step_events" :workflow-steps="workflowSteps" :people="people" />
                </section>

                <div class="flex items-center gap-3 border-t pt-5">
                    <Button type="submit" :disabled="form.processing">Create Candidate</Button>
                    <Button as-child variant="outline"><Link href="/portal/candidates">Cancel</Link></Button>
                </div>
            </div>
        </form>
    </div>
</template>

<script setup>
import { computed, defineComponent, h, ref } from 'vue'
import { Link, router, useForm } from '@inertiajs/vue3'
import { ClipboardList, ListChecks, UserRound } from 'lucide-vue-next'
import CandidateWorkflowEditor from '@/components/forms/CandidateWorkflowEditor.vue'
import PortalSectionNav from '@/components/portal/PortalSectionNav.vue'
import { useAuth } from '@/composables/useAuth'
import { Button } from '@/components/ui/button'

const { can } = useAuth()
const props = defineProps({ people:{type:Array,default:()=>[]}, positions:{type:Array,default:()=>[]}, workflows:{type:Array,default:()=>[]}, workflow:{type:Object,required:true}, workflowSteps:{type:Array,default:()=>[]} })
const activeSection=ref('details')
const selectedWorkflowId=ref(props.workflow?.id ?? '')
const sections=computed(()=>[
 {id:'details',label:'Candidate Details',description:'Person, position, and status.',icon:UserRound},
 {id:'workflow',label:'Workflow',description:'Workflow selection.',icon:ClipboardList},
 {id:'steps',label:'Workflow Steps',description:'Status, dates, and notes.',icon:ListChecks,badge:props.workflowSteps.length||undefined},
])
const form=useForm({person_id:'',position_id:'',workflow_id:props.workflow?.id??'',status:'submitted',candidate_fbr:'',submitted_at:'',submitted_by_person_id:'',scheduled_start_date:'',step_events:[]})
function changeWorkflow(){router.get('/portal/candidates/create',{workflow_id:selectedWorkflowId.value},{preserveState:false,replace:true})}
function submit(){form.workflow_id=selectedWorkflowId.value;form.post('/portal/candidates')}
const FormInput = defineComponent({
    props: {
        id: { type: String, required: true },
        label: String,
        modelValue: [String, Number],
        error: String,
        type: { type: String, default: 'text' },
        step: String,
        required: Boolean,
    },
    emits: ['update:modelValue'],
    setup(p, { emit }) {
        return () => h('div', [
            h('label', { class: 'text-sm font-medium', for: p.id }, [
                p.label,
                p.required ? h('span', { class: 'ml-1 text-destructive', 'aria-hidden': 'true' }, '*') : null,
                p.required ? h('span', { class: 'sr-only' }, ' (required)') : null,
            ]),
            h('input', {
                id: p.id,
                class: 'mt-2 flex h-10 w-full rounded-md border bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring',
                type: p.type,
                step: p.step,
                value: p.modelValue,
                required: p.required,
                'aria-invalid': p.error ? 'true' : undefined,
                'aria-describedby': p.error ? `${p.id}-error` : undefined,
                onInput: e => emit('update:modelValue', e.target.value),
            }),
            p.error ? h('p', { id: `${p.id}-error`, class: 'mt-1 text-sm text-destructive', role: 'alert' }, p.error) : null,
        ])
    },
})

const FormSelect = defineComponent({
    props: {
        id: { type: String, required: true },
        label: String,
        modelValue: [String, Number],
        error: String,
        required: Boolean,
    },
    emits: ['update:modelValue', 'change'],
    setup(p, { emit, slots }) {
        return () => h('div', [
            h('label', { class: 'text-sm font-medium', for: p.id }, [
                p.label,
                p.required ? h('span', { class: 'ml-1 text-destructive', 'aria-hidden': 'true' }, '*') : null,
                p.required ? h('span', { class: 'sr-only' }, ' (required)') : null,
            ]),
            h('select', {
                id: p.id,
                class: 'mt-2 flex h-10 w-full rounded-md border bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring',
                value: p.modelValue,
                required: p.required,
                'aria-invalid': p.error ? 'true' : undefined,
                'aria-describedby': p.error ? `${p.id}-error` : undefined,
                onChange: e => {
                    emit('update:modelValue', e.target.value)
                    emit('change', e)
                },
            }, slots.default?.()),
            p.error ? h('p', { id: `${p.id}-error`, class: 'mt-1 text-sm text-destructive', role: 'alert' }, p.error) : null,
        ])
    },
})

</script>
