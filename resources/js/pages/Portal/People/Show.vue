<script setup>
import { Link, router } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import { MoreHorizontal } from 'lucide-vue-next'
import CustomFieldsDisplay from '@/components/custom-fields/CustomFieldsDisplay.vue'
import AttachmentList from '@/components/attachments/AttachmentList.vue'
import DetailItem from '@/components/DetailItem.vue'
import PersonSectionNav from '@/components/portal/people/PersonSectionNav.vue'
import PersonNotesPanel from '@/components/portal/people/PersonNotesPanel.vue'
import { useAppLabels } from '@/composables/useAppLabels'
import { useAuth } from '@/composables/useAuth'
import { Permissions } from '@/constants/permissions'
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table'

const props = defineProps({
    person: { type: Object, required: true },
    customFieldDisplay: { type: Array, default: () => [] },
})

const { label } = useAppLabels()
const { can } = useAuth()
const activeSection = ref('details')
const deleteDialogOpen = ref(false)
const assignmentToDelete = ref(null)

const phoneNumbers = computed(() => {
    const phones = props.person.phone_numbers ?? props.person.phoneNumbers ?? []
    return [...phones].sort((a, b) => Number(Boolean(b.is_primary)) - Number(Boolean(a.is_primary)))
})

const addresses = computed(() => {
    const items = props.person.addresses ?? []
    return [...items].sort((a, b) => Number(Boolean(b.is_primary)) - Number(Boolean(a.is_primary)))
})

const groups = computed(() => props.person.groups ?? [])
const teams = computed(() => props.person.teams ?? [])
const roles = computed(() => props.person.user?.roles ?? [])
const attachments = computed(() => props.person.attachments ?? [])
const personNotes = computed(() => props.person.person_notes ?? props.person.personNotes ?? [])

const activeAssignments = computed(() => {
    return (props.person.assignments ?? []).filter((assignment) => {
        const status = String(assignment.assignment_status || '').toLowerCase()
        return status === 'active' || !assignment.end_date
    })
})

const sections = computed(() => [
    {
        id: 'details',
        title: 'Person Details',
        description: 'Identity and employment.',
        complete: Boolean(props.person.person_code && props.person.first_name && props.person.last_name),
    },
    {
        id: 'notes',
        title: 'Notes',
        description: 'Kudos, reprimands, and general notes.',
        complete: Boolean(personNotes.value.length),
    },
    {
        id: 'organization',
        title: 'Organization',
        description: 'Groups, teams, and assignments.',
        complete: Boolean(groups.value.length || teams.value.length || activeAssignments.value.length),
    },
    {
        id: 'contact',
        title: 'Contact Information',
        description: 'Phone numbers and addresses.',
        complete: Boolean(phoneNumbers.value.length || addresses.value.length),
    },
    {
        id: 'other',
        title: 'Other Information',
        description: 'Installation-specific fields.',
        complete: Boolean(props.customFieldDisplay.some((field) => Array.isArray(field.value) ? field.value.length : field.value)),
    },
    {
        id: 'access',
        title: 'Roles & Access',
        description: 'Linked account and roles.',
        complete: Boolean(roles.value.length),
    },
    {
        id: 'attachments',
        title: 'Attachments',
        description: 'Documents and files.',
        complete: Boolean(attachments.value.length),
    },
])

function fullName(person) {
    return `${person.preferred_name || person.first_name || ''} ${person.last_name || ''}`.trim() || 'Person Details'
}

function formatDate(value) {
    if (!value) return '—'
    const date = new Date(value)
    return Number.isNaN(date.getTime()) ? value : date.toLocaleDateString()
}

function formatValue(value) {
    return value || '—'
}

function formatType(value) {
    if (!value) return 'Other'
    const normalized = String(value).replaceAll('_', ' ')
    return normalized.charAt(0).toUpperCase() + normalized.slice(1)
}

function addressLines(address) {
    return [
        address.line_1,
        address.line_2,
        [address.city, address.state, address.postal_code].filter(Boolean).join(', ').replace(', ,', ','),
        address.country,
    ].filter(Boolean)
}

function openDeleteDialog(id) {
    assignmentToDelete.value = id
    deleteDialogOpen.value = true
}

function confirmDelete() {
    if (!assignmentToDelete.value) return
    router.delete(`/position-assignments/${assignmentToDelete.value}`, {
        preserveScroll: true,
        onFinish: () => {
            deleteDialogOpen.value = false
            assignmentToDelete.value = null
        },
    })
}
</script>

<template>
    <div class="space-y-6 p-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold">{{ fullName(person) }}</h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    {{ label('person_code') }}: {{ person.person_code || '—' }}
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <Button as-child variant="outline"><Link href="/portal/people">Back to List</Link></Button>
                <Button as-child v-if="can(Permissions.PEOPLE_UPDATE)"><Link :href="`/portal/people/${person.id}/edit`">Edit Person</Link></Button>
                <Button as-child v-if="can(Permissions.POSITIONS_UPDATE)" variant="outline"><Link :href="`/position-assignments/create?person_id=${person.id}`">Add Assignment</Link></Button>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-[264px_minmax(0,1fr)]">
            <PersonSectionNav v-model:active-section="activeSection" :sections="sections" />

            <div class="min-w-0 space-y-6">
                <section v-show="activeSection === 'details'">
                    <Card>
                        <CardHeader>
                            <CardTitle>Person Details</CardTitle>
                            <CardDescription>Identity, employment, and general information.</CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-6">
                            <div class="grid gap-5 md:grid-cols-2">
                                <DetailItem :label="label('person_code')" :value="formatValue(person.person_code)" />
                                <DetailItem label="Employment Status" :value="formatValue(person.employment_status)" />
                                <DetailItem label="First Name" :value="formatValue(person.first_name)" />
                                <DetailItem label="Alternate First Name" :value="formatValue(person.alternate_first_name)" />
                                <DetailItem label="Preferred Name" :value="formatValue(person.preferred_name)" />
                                <DetailItem label="Last Name" :value="formatValue(person.last_name)" />
                                <DetailItem label="Alternate Last Name" :value="formatValue(person.alternate_last_name)" />
                                <DetailItem label="Company Name" :value="formatValue(person.company_name)" />
                                <DetailItem label="Email" :value="formatValue(person.email)" />
                                <DetailItem label="Created" :value="formatDate(person.created_at)" />
                            </div>

                        </CardContent>
                    </Card>
                </section>

                <section v-show="activeSection === 'other'">
                    <CustomFieldsDisplay :fields="customFieldDisplay" />
                </section>

                <section v-show="activeSection === 'notes'">
                    <PersonNotesPanel :person-id="person.id" :notes="personNotes" />
                </section>

                <section v-show="activeSection === 'organization'" class="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Organization</CardTitle>
                            <CardDescription>Groups and teams assigned to this person.</CardDescription>
                        </CardHeader>
                        <CardContent class="grid gap-6 md:grid-cols-2">
                            <div>
                                <p class="text-sm font-medium">Groups</p>
                                <div v-if="groups.length" class="mt-3 flex flex-wrap gap-2">
                                    <Badge v-for="group in groups" :key="group.id" variant="secondary">{{ group.group_name }}</Badge>
                                </div>
                                <p v-else class="mt-2 text-sm text-muted-foreground">No groups assigned.</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium">Teams</p>
                                <div v-if="teams.length" class="mt-3 flex flex-wrap gap-2">
                                    <Badge v-for="team in teams" :key="team.id" variant="secondary">{{ team.team_name }}</Badge>
                                </div>
                                <p v-else class="mt-2 text-sm text-muted-foreground">No teams assigned.</p>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Current Assignments</CardTitle>
                            <CardDescription>Active position assignments for this person.</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div v-if="activeAssignments.length" class="space-y-3">
                                <div v-for="assignment in activeAssignments" :key="assignment.id" class="rounded-lg border p-4">
                                    <div class="flex items-start justify-between gap-4">
                                        <div>
                                            <p class="font-medium">{{ assignment.position?.job_title || 'Unnamed Position' }}</p>
                                            <p class="mt-1 text-sm text-muted-foreground">Code: {{ assignment.position?.position_code || '—' }}</p>
                                            <p class="text-sm text-muted-foreground">Status: {{ assignment.assignment_status || '—' }}</p>
                                            <p class="text-sm text-muted-foreground">Start Date: {{ formatDate(assignment.start_date) }}</p>
                                        </div>
                                        <DropdownMenu v-if="can(Permissions.PEOPLE_UPDATE)">
                                            <DropdownMenuTrigger as-child><Button variant="ghost" size="icon" aria-label="Open actions menu"><MoreHorizontal class="h-4 w-4" aria-hidden="true" /></Button></DropdownMenuTrigger>
                                            <DropdownMenuContent align="end">
                                                <DropdownMenuLabel>Actions</DropdownMenuLabel>
                                                <DropdownMenuSeparator />
                                                <DropdownMenuItem as-child><Link :href="`/position-assignments/${assignment.id}/edit?return_to=/portal/people/${person.id}`">Edit</Link></DropdownMenuItem>
                                                <DropdownMenuSeparator />
                                                <DropdownMenuItem class="text-destructive focus:text-destructive" @click="openDeleteDialog(assignment.id)">Delete</DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </div>
                                </div>
                            </div>
                            <p v-else class="text-sm text-muted-foreground">No active assignments found.</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader><CardTitle>Assignment History</CardTitle></CardHeader>
                        <CardContent>
                            <div v-if="person.assignments?.length" class="overflow-x-auto">
                                <Table>
                                    <TableHeader><TableRow><TableHead>Position</TableHead><TableHead>Code</TableHead><TableHead>Status</TableHead><TableHead>Type</TableHead><TableHead>Start</TableHead><TableHead>End</TableHead></TableRow></TableHeader>
                                    <TableBody>
                                        <TableRow v-for="assignment in person.assignments" :key="assignment.id">
                                            <TableCell>{{ assignment.position?.job_title || '—' }}</TableCell>
                                            <TableCell>{{ assignment.position?.position_code || '—' }}</TableCell>
                                            <TableCell>{{ assignment.assignment_status || '—' }}</TableCell>
                                            <TableCell>{{ assignment.assignment_type || '—' }}</TableCell>
                                            <TableCell>{{ formatDate(assignment.start_date) }}</TableCell>
                                            <TableCell>{{ formatDate(assignment.end_date) }}</TableCell>
                                        </TableRow>
                                    </TableBody>
                                </Table>
                            </div>
                            <p v-else class="text-sm text-muted-foreground">No assignment history found.</p>
                        </CardContent>
                    </Card>
                </section>

                <section v-show="activeSection === 'contact'" class="space-y-6">
                    <Card>
                        <CardHeader><CardTitle>Phone Numbers</CardTitle><CardDescription>Phone contact information.</CardDescription></CardHeader>
                        <CardContent>
                            <div v-if="phoneNumbers.length" class="grid gap-3 md:grid-cols-2">
                                <div v-for="phone in phoneNumbers" :key="phone.id" class="rounded-lg border p-4">
                                    <div class="flex items-start justify-between gap-3">
                                        <div><p class="font-medium">{{ phone.phone_number || '—' }}</p><p class="mt-1 text-sm text-muted-foreground">{{ formatType(phone.phone_type) }}<span v-if="phone.extension"> · Ext. {{ phone.extension }}</span></p><p v-if="phone.notes" class="mt-2 text-sm text-muted-foreground">{{ phone.notes }}</p></div>
                                        <Badge v-if="phone.is_primary">Primary</Badge>
                                    </div>
                                </div>
                            </div>
                            <p v-else class="text-sm text-muted-foreground">No phone numbers available.</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader><CardTitle>Addresses</CardTitle><CardDescription>Mailing and physical addresses.</CardDescription></CardHeader>
                        <CardContent>
                            <div v-if="addresses.length" class="grid gap-3 md:grid-cols-2">
                                <div v-for="address in addresses" :key="address.id" class="rounded-lg border p-4">
                                    <div class="flex items-start justify-between gap-3">
                                        <div><p class="font-medium">{{ formatType(address.address_type) }}</p><div class="mt-2 text-sm text-muted-foreground"><p v-for="(line, index) in addressLines(address)" :key="index">{{ line }}</p></div><p v-if="address.notes" class="mt-2 text-sm text-muted-foreground">{{ address.notes }}</p></div>
                                        <Badge v-if="address.is_primary">Primary</Badge>
                                    </div>
                                </div>
                            </div>
                            <p v-else class="text-sm text-muted-foreground">No addresses available.</p>
                        </CardContent>
                    </Card>
                </section>

                <section v-show="activeSection === 'access'" class="space-y-6">
                    <Card>
                        <CardHeader><CardTitle>Linked User Account</CardTitle><CardDescription>The application account connected to this person.</CardDescription></CardHeader>
                        <CardContent>
                            <div v-if="person.user" class="grid gap-5 md:grid-cols-2">
                                <DetailItem label="User Name" :value="formatValue(person.user.name)" />
                                <DetailItem label="Email" :value="formatValue(person.user.email || person.email)" />
                            </div>
                            <p v-else class="rounded-lg border border-dashed p-6 text-sm text-muted-foreground">No user account is linked.</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader><CardTitle>Roles & Access</CardTitle><CardDescription>Application roles assigned to the linked user.</CardDescription></CardHeader>
                        <CardContent>
                            <div v-if="roles.length" class="grid gap-3 md:grid-cols-2">
                                <div v-for="role in roles" :key="role.id" class="rounded-lg border bg-muted/20 p-4">
                                    <p class="font-medium">{{ role.label || role.name }}</p>
                                    <p v-if="role.description" class="mt-1 text-sm text-muted-foreground">{{ role.description }}</p>
                                    <p v-else-if="role.label" class="mt-1 text-xs text-muted-foreground">{{ role.name }}</p>
                                </div>
                            </div>
                            <p v-else class="text-sm text-muted-foreground">No application roles assigned.</p>
                        </CardContent>
                    </Card>
                </section>

                <section v-show="activeSection === 'attachments'">
                    <Card>
                        <CardHeader><CardTitle>Attachments</CardTitle><CardDescription>Documents and files associated with this person.</CardDescription></CardHeader>
                        <CardContent><AttachmentList :attachments="attachments" /></CardContent>
                    </Card>
                </section>
            </div>
        </div>

        <AlertDialog :open="deleteDialogOpen" @update:open="deleteDialogOpen = $event">
            <AlertDialogContent>
                <AlertDialogHeader><AlertDialogTitle>Delete Assignment?</AlertDialogTitle><AlertDialogDescription>This action cannot be undone.</AlertDialogDescription></AlertDialogHeader>
                <AlertDialogFooter><AlertDialogCancel>Cancel</AlertDialogCancel><AlertDialogAction class="bg-destructive text-destructive-foreground hover:bg-destructive/90" @click="confirmDelete">Delete</AlertDialogAction></AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </div>
</template>
