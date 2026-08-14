<template>
<div class="mx-auto w-full max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
<div class="flex items-center justify-between"><div><h1 class="text-2xl font-semibold">Edit Person</h1><p class="mt-1 text-sm text-muted-foreground">Update person details, assignments, access, and files.</p></div><Button as-child variant="outline"><Link href="/portal/people">Back to List</Link></Button></div>
<form @submit.prevent="submit">
<div class="grid gap-6 lg:grid-cols-[270px_minmax(0,1fr)]">
<PersonSectionNav v-model:active-section="activeSection" :sections="sections" />
<div class="min-w-0 space-y-6">
<section v-show="activeSection === 'details'"><Card>
<CardHeader><CardTitle>Person Details</CardTitle><CardDescription>Basic identity and employment information.</CardDescription></CardHeader>
<CardContent class="space-y-6">
<div class="grid gap-4 md:grid-cols-2">
<div class="space-y-2"><Label for="person_code">{{ label('person_code') }} <span class="text-red-500">*</span></Label><Input id="person_code" v-model="form.person_code" /><p v-if="form.errors.person_code" class="text-sm text-red-500">{{ form.errors.person_code }}</p></div>
<div class="space-y-2"><Label for="employment_status">Employment Status</Label><Input id="employment_status" v-model="form.employment_status" /></div>
<div class="space-y-2"><Label for="first_name">First Name <span class="text-red-500">*</span></Label><Input id="first_name" v-model="form.first_name" /><p v-if="form.errors.first_name" class="text-sm text-red-500">{{ form.errors.first_name }}</p></div>
<div class="space-y-2"><Label for="alternate_first_name">Alternate First Name</Label><Input id="alternate_first_name" v-model="form.alternate_first_name" /></div>
<div class="space-y-2"><Label for="preferred_name">Preferred Name</Label><Input id="preferred_name" v-model="form.preferred_name" /></div>
<div class="space-y-2"><Label for="last_name">Last Name <span class="text-red-500">*</span></Label><Input id="last_name" v-model="form.last_name" /><p v-if="form.errors.last_name" class="text-sm text-red-500">{{ form.errors.last_name }}</p></div>
<div class="space-y-2"><Label for="alternate_last_name">Alternate Last Name</Label><Input id="alternate_last_name" v-model="form.alternate_last_name" /></div>
<div class="space-y-2"><Label for="company_name">Company Name</Label><Input id="company_name" v-model="form.company_name" /></div>
<div class="space-y-2 md:col-span-2"><Label for="email">Email</Label><Input id="email" type="email" v-model="form.email" /><p v-if="form.errors.email" class="text-sm text-red-500">{{ form.errors.email }}</p></div>
</div>
</CardContent></Card></section>
<section v-show="activeSection === 'notes'"><PersonNotesPanel :person-id="props.person.id" :notes="personNotes" editable /></section>
<section v-show="activeSection === 'organization'"><AssignmentsEditor v-model:group-ids="form.group_ids" v-model:team-ids="form.team_ids" :groups="props.groups" :teams="props.teams" :errors="form.errors" /></section>
<section v-show="activeSection === 'contact'" class="space-y-6"><PhoneNumbersEditor ref="phoneNumbersRef" v-model="form.phone_numbers" :errors="form.errors" /><AddressesEditor ref="addressesRef" v-model="form.addresses" :errors="form.errors" /></section>
<section v-show="activeSection === 'other'"><CustomFieldsPanel v-model="form.custom_fields" :fields="props.customFields" :errors="form.errors" /></section>
<section v-show="activeSection === 'access'"><UserRoleEditor v-model="form.role_ids" :roles="props.roles" :errors="form.errors" /></section>
<section v-show="activeSection === 'attachments'"><AttachmentUploader ref="attachmentsRef" v-model="form.attachments" v-model:existingAttachments="form.existing_attachments" v-model:removeAttachmentIds="form.remove_attachment_ids" :errors="form.errors" :show-existing="isEdit" /></section>
<div class="flex gap-3 border-t pt-5"><Button type="submit" :disabled="form.processing">{{ form.processing ? 'Saving...' : 'Save Changes' }}</Button><Button as-child variant="outline"><Link href="/portal/people">Cancel</Link></Button></div>
</div></div></form></div>
</template>
<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import CustomFieldsPanel from '@/components/custom-fields/CustomFieldsPanel.vue'
import AttachmentUploader from '@/components/attachments/AttachmentUploader.vue'
import AddressesEditor from '@/components/forms/AddressesEditor.vue'
import AssignmentsEditor from '@/components/forms/AssignmentsEditor.vue'
import PhoneNumbersEditor from '@/components/forms/PhoneNumbersEditor.vue'
import PersonSectionNav from '@/components/portal/people/PersonSectionNav.vue'
import PersonNotesPanel from '@/components/portal/people/PersonNotesPanel.vue'
import UserRoleEditor from '@/components/portal/people/UserRoleEditor.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { useAppLabels } from '@/composables/useAppLabels'
const { label } = useAppLabels()
const activeSection = ref('details')
const phoneNumbersRef = ref(null)
const addressesRef = ref(null)
const attachmentsRef = ref(null)
const createEmptyPhoneNumber = (isPrimary=false) => ({ id:null, phone_number:'', phone_type:'', is_primary:isPrimary, extension:'', notes:'' })
const createEmptyAddress = (isPrimary=false) => ({ id:null, address_type:'', line_1:'', line_2:'', city:'', state:'', postal_code:'', country:'USA', is_primary:isPrimary, notes:'' })

const isEdit = true
const props = defineProps({ person:{type:Object,required:true}, selectedRoleIds:{type:Array,default:()=>[]}, roles:{type:Array,default:()=>[]}, groups:{type:Array,default:()=>[]}, teams:{type:Array,default:()=>[]}, customFields:{type:Array,default:()=>[]}, customFieldValues:{type:Object,default:()=>({})} })
const phones=(props.person.phone_numbers??props.person.phoneNumbers??[]).map((p,i)=>({...createEmptyPhoneNumber(),...p,is_primary:Boolean(p.is_primary),sort_order:i})); if(phones.length&&!phones.some(p=>p.is_primary))phones[0].is_primary=true
const addresses=(props.person.addresses??[]).map(a=>({...createEmptyAddress(),...a,is_primary:Boolean(a.is_primary)})); if(addresses.length&&!addresses.some(a=>a.is_primary))addresses[0].is_primary=true
const existing=(props.person.attachments_for_ui??props.person.attachments??[]).map(a=>({...a,marked_for_removal:false}))
const personNotes=computed(() => props.person.person_notes??props.person.personNotes??[])
const form = useForm({ person_code:props.person.person_code??'', first_name:props.person.first_name??'', alternate_first_name:props.person.alternate_first_name??'', preferred_name:props.person.preferred_name??'', last_name:props.person.last_name??'', alternate_last_name:props.person.alternate_last_name??'', company_name:props.person.company_name??'', email:props.person.email??'', employment_status:props.person.employment_status??'', user_id:props.person.user_id??null, group_ids:(props.person.groups??[]).map(x=>x.id), team_ids:(props.person.teams??[]).map(x=>x.id), role_ids:(props.selectedRoleIds??[]).map(Number), phone_numbers:phones.length?phones:[createEmptyPhoneNumber(true)], addresses:addresses.length?addresses:[createEmptyAddress(true)], attachments:[], existing_attachments:existing, remove_attachment_ids:[], custom_fields:{...(props.customFieldValues??{})} })
const sections = computed(() => [
{id:'details',title:'Person Details',description:'Identity and employment.',complete:Boolean(form.person_code && form.first_name && form.last_name)},
{id:'notes',title:'Notes',description:'Kudos, reprimands, and general notes.',complete:Boolean(personNotes.value.length)},
{id:'organization',title:'Organization',description:'Groups and teams.',complete:Boolean(form.group_ids.length || form.team_ids.length)},
{id:'contact',title:'Contact Information',description:'Phone numbers and addresses.',complete:Boolean(form.phone_numbers.some(p=>p.phone_number) || form.addresses.some(a=>a.line_1))},
{id:'other',title:'Other Information',description:'Installation-specific fields.',complete:Object.values(form.custom_fields).some(v=>Array.isArray(v)?v.length:Boolean(v))},
{id:'access',title:'Roles & Access',description:'Application roles.',complete:Boolean(form.role_ids.length)},
{id:'attachments',title:'Attachments',description:'Documents and files.',complete:Boolean(form.existing_attachments.length || form.attachments.length)},])
function validate(){ form.clearErrors(); let error=false; if(!form.person_code?.trim()){form.setError('person_code','Person code is required.');error=true} if(!form.first_name?.trim()){form.setError('first_name','First name is required.');error=true} if(!form.last_name?.trim()){form.setError('last_name','Last name is required.');error=true} if(phoneNumbersRef.value&&!phoneNumbersRef.value.validate())error=true; if(addressesRef.value&&!addressesRef.value.validate())error=true; if(attachmentsRef.value&&!attachmentsRef.value.validate())error=true; return !error }
function submit(){ if(!validate())return; form.transform(data=>{const transformed={...data,_method:'put',attachment_meta:data.attachments.map((a,i)=>({category:a.category??'',description:a.description??'',is_primary:a.is_primary?1:0,sort_order:i})),new_attachments:data.attachments.map(a=>a.file).filter(Boolean),remove_attachment_ids:data.remove_attachment_ids??[]};delete transformed.attachments;delete transformed.existing_attachments;return transformed}).post(`/portal/people/${props.person.id}`,{forceFormData:true}) }

</script>
