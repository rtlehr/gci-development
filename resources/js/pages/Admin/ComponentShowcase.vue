<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import { ref } from 'vue'
import {
    Accessibility,
    Bell,
    BookOpen,
    BriefcaseBusiness,
    CheckCircle2,
    CircleAlert,
    ClipboardList,
    Columns3,
    FileText,
    Inbox,
    LayoutDashboard,
    ListFilter,
    Palette,
    PanelRight,
    Search,
    ShieldCheck,
    Sparkles,
    Users,
} from 'lucide-vue-next'
import CodeExample from '@/components/design-system/CodeExample.vue'
import GuidelineCard from '@/components/design-system/GuidelineCard.vue'
import EmptyState from '@/components/data/EmptyState.vue'
import StatCard from '@/components/data/StatCard.vue'
import StatusBadge from '@/components/data/StatusBadge.vue'
import BooleanField from '@/components/forms/BooleanField.vue'
import DisplayField from '@/components/forms/DisplayField.vue'
import FormActions from '@/components/forms/FormActions.vue'
import FormDivider from '@/components/forms/FormDivider.vue'
import FormField from '@/components/forms/FormField.vue'
import FormSection from '@/components/forms/FormSection.vue'
import InfoPanel from '@/components/forms/InfoPanel.vue'
import PageContainer from '@/components/layout/PageContainer.vue'
import PageHeader from '@/components/layout/PageHeader.vue'
import SectionHeader from '@/components/layout/SectionHeader.vue'
import ListFilters from '@/components/Lists/ListFilters.vue'
import ListPagination from '@/components/Lists/ListPagination.vue'
import ListRowActions from '@/components/Lists/ListRowActions.vue'
import ListTableShell from '@/components/Lists/ListTableShell.vue'
import ListToolbar from '@/components/Lists/ListToolbar.vue'
import DetailCard from '@/components/show/DetailCard.vue'
import FlagItem from '@/components/show/FlagItem.vue'
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { DropdownMenuItem } from '@/components/ui/dropdown-menu'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Textarea } from '@/components/ui/textarea'

const search = ref('')
const status = ref('all')
const remoteEligible = ref(true)

const sectionLinks = [
    ['principles', 'Principles'],
    ['layout', 'Layout'],
    ['lists', 'Lists'],
    ['show-pages', 'Show pages'],
    ['forms', 'Forms'],
    ['feedback', 'Feedback'],
    ['standards', 'Standards'],
    ['templates', 'Templates'],
]

const listTemplateCode = `<PageContainer>
    <ListToolbar
        title="Positions"
        description="Manage staffing requirements and assignments."
        create-label="Create Position"
        create-href="/positions/create"
        :can-create="true"
        :can-export="true"
    />

    <ListFilters v-model:search="filters.search" @apply="applyFilters" @reset="resetFilters" />
    <ListTableShell label="Positions results">...</ListTableShell>
    <ListPagination ... />
</PageContainer>`

const showTemplateCode = `<PageContainer>
    <PageHeader title="Position Details" description="Review position requirements and assignments." />

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
        <div class="space-y-6">
            <DetailCard title="Position information">...</DetailCard>
            <DetailCard title="Assignments">...</DetailCard>
        </div>
        <InfoPanel title="Information">...</InfoPanel>
    </div>
</PageContainer>`

const formTemplateCode = `<PageContainer>
    <PageHeader title="Edit Position" description="Update position details and requirements." />

    <form class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]" @submit.prevent="submit">
        <div class="space-y-6">
            <FormSection title="Primary information">...</FormSection>
            <FormSection title="Requirements">...</FormSection>
            <FormActions sticky :dirty="form.isDirty" :processing="form.processing" />
        </div>
        <InfoPanel title="Position summary">...</InfoPanel>
    </form>
</PageContainer>`
</script>

<template>
    <Head title="IRAD Design System" />

    <PageContainer>
        <PageHeader
            eyebrow="Design System"
            title="IRAD Interface Standards"
            description="The official living reference for building consistent, accessible, and maintainable IRAD pages."
            back-href="/admin"
            back-label="Admin"
        >
            <template #actions>
                <Badge variant="outline" class="gap-2 py-1.5">
                    <Sparkles class="h-3.5 w-3.5" aria-hidden="true" />
                    Version 2
                </Badge>
            </template>
        </PageHeader>

        <nav class="sticky top-0 z-20 -mx-4 overflow-x-auto border-y bg-background/95 px-4 py-3 backdrop-blur sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8" aria-label="Design system sections">
            <div class="flex min-w-max gap-2">
                <Button v-for="([id, label]) in sectionLinks" :key="id" variant="ghost" size="sm" as-child>
                    <a :href="`#${id}`">{{ label }}</a>
                </Button>
            </div>
        </nav>

        <section id="principles" class="scroll-mt-24 space-y-4">
            <SectionHeader title="Design principles" description="Every IRAD interface decision should support these four goals." />
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <GuidelineCard title="Clear hierarchy" description="Users should immediately understand what the page is, what matters most, and what action comes next." :icon="LayoutDashboard" />
                <GuidelineCard title="One responsibility" description="Shared components should solve one problem well instead of accumulating unrelated behavior." :icon="Columns3" />
                <GuidelineCard title="Accessible by default" description="Meaning never depends on color alone, controls have labels, and keyboard use remains fully supported." :icon="Accessibility" />
                <GuidelineCard title="Reusable patterns" description="New modules should assemble approved patterns rather than inventing a new visual language." :icon="BookOpen" />
            </div>
        </section>

        <section id="layout" class="scroll-mt-24 space-y-4">
            <SectionHeader title="Layout and page width" description="Use the available display space while preserving readable content and predictable structure." />
            <div class="grid gap-4 lg:grid-cols-3">
                <GuidelineCard title="Wide — standard" description="Use PageContainer’s default wide size for most list, show, and form pages." :icon="Columns3">
                    <p>Desktop width: approximately 90%. Mobile and tablet width: 100% with responsive padding.</p>
                </GuidelineCard>
                <GuidelineCard title="Default — focused" description="Use the narrower default size for simple workflows and text-heavy pages." :icon="FileText">
                    <p>Best for small settings forms, confirmations, and content that benefits from shorter line lengths.</p>
                </GuidelineCard>
                <GuidelineCard title="Full — exceptional" description="Reserve full width for dense dashboards or tables that genuinely require maximum horizontal space." :icon="LayoutDashboard">
                    <p>Do not use full width to compensate for poorly grouped content or excessive columns.</p>
                </GuidelineCard>
            </div>

            <Alert>
                <CircleAlert class="h-4 w-4" />
                <AlertTitle>Horizontal scrolling is a fallback, not the layout strategy</AlertTitle>
                <AlertDescription>
                    Prefer the 90% content container, concise columns, responsive hiding, and grouped information. Keep table overflow enabled only for genuinely narrow screens.
                </AlertDescription>
            </Alert>
        </section>

        <section class="space-y-4">
            <SectionHeader title="Statistics" description="Use compact summary cards when totals help users understand the page before reviewing details." />
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <StatCard label="Active People" value="128" description="12 added this month" :icon="Users" />
                <StatCard label="Open Positions" value="10" description="3 require attention" :icon="BriefcaseBusiness" />
                <StatCard label="Completed" value="94%" description="Current workflow rate" :icon="CheckCircle2" />
                <StatCard label="At Risk" value="3" description="Review before Friday" :icon="CircleAlert" />
            </div>
        </section>

        <section id="lists" class="scroll-mt-24 space-y-5">
            <SectionHeader title="List-page pattern" description="Lists follow one predictable sequence: header, optional statistics, filters, results, and pagination." />

            <div class="space-y-5 rounded-xl border bg-muted/10 p-4 sm:p-6">
                <ListToolbar
                    eyebrow="Workforce"
                    title="Positions"
                    description="Manage authorized positions, staffing requirements, and current assignments."
                    create-label="Create Position"
                    create-href="/positions/create"
                    :can-create="true"
                    :can-export="true"
                />

                <ListFilters v-model:search="search" search-placeholder="Search by title, code, or organization..." @apply="() => undefined" @reset="search = ''">
                    <template #filters>
                        <div class="w-full space-y-2 lg:w-52">
                            <Label for="showcase-status">Status</Label>
                            <Select v-model="status">
                                <SelectTrigger id="showcase-status">
                                    <SelectValue placeholder="All statuses" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All statuses</SelectItem>
                                    <SelectItem value="open">Open</SelectItem>
                                    <SelectItem value="in-process">In Process</SelectItem>
                                    <SelectItem value="closed">Closed</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </template>
                </ListFilters>

                <ListTableShell label="Example positions">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Position</TableHead>
                                <TableHead>Organization</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>Location</TableHead>
                                <TableHead class="text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow>
                                <TableCell class="font-medium">Frontend Developer</TableCell>
                                <TableCell>Digital Solutions</TableCell>
                                <TableCell><StatusBadge label="Open" tone="success" /></TableCell>
                                <TableCell>Norfolk, VA</TableCell>
                                <TableCell class="text-right">
                                    <ListRowActions aria-label="Actions for Frontend Developer">
                                        <DropdownMenuItem>View</DropdownMenuItem>
                                        <DropdownMenuItem>Edit</DropdownMenuItem>
                                    </ListRowActions>
                                </TableCell>
                            </TableRow>
                            <TableRow>
                                <TableCell class="font-medium">Program Manager</TableCell>
                                <TableCell>Program Operations</TableCell>
                                <TableCell><StatusBadge label="In Process" tone="info" /></TableCell>
                                <TableCell>Remote</TableCell>
                                <TableCell class="text-right">
                                    <ListRowActions aria-label="Actions for Program Manager">
                                        <DropdownMenuItem>View</DropdownMenuItem>
                                        <DropdownMenuItem>Edit</DropdownMenuItem>
                                    </ListRowActions>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </ListTableShell>

                <ListPagination :current-page="1" :last-page="3" :from="1" :to="10" :total="24" item-label="positions" :pages="[1, 2, 3]" />
            </div>

            <div class="grid gap-4 lg:grid-cols-3">
                <GuidelineCard title="Toolbar ownership" description="Create, export, column settings, and future bulk actions belong in ListToolbar." :icon="ClipboardList" />
                <GuidelineCard title="Page Help ownership" description="The PageHeader owns Page Help. Never duplicate the question-mark action in the list toolbar." :icon="BookOpen" />
                <GuidelineCard title="Filter discipline" description="Show common filters first and place infrequent criteria in an advanced area." :icon="ListFilter" />
            </div>
            <CodeExample :code="listTemplateCode" label="Approved list-page template" />
        </section>

        <section id="show-pages" class="scroll-mt-24 space-y-5">
            <SectionHeader title="Show-page pattern" description="Show pages prioritize identity, current state, related data, and concise metadata." />
            <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
                <div class="grid gap-6 md:grid-cols-2">
                    <DetailCard title="Position information" description="Core details users need most often." :icon="BriefcaseBusiness">
                        <dl class="grid gap-5 sm:grid-cols-2">
                            <DisplayField label="Position code" value="POS-1024" />
                            <DisplayField label="Grade" value="Senior" />
                            <DisplayField label="Organization" value="Digital Solutions" />
                            <DisplayField label="Clearance" value="Secret" />
                        </dl>
                    </DetailCard>
                    <DetailCard title="Operational flags" description="Binary conditions remain readable without relying on color." :icon="ShieldCheck">
                        <div class="grid gap-3">
                            <FlagItem label="Funded" :active="true" />
                            <FlagItem label="Remote eligible" :active="true" />
                            <FlagItem label="Travel required" :active="false" />
                        </div>
                    </DetailCard>
                </div>
                <InfoPanel title="Information" description="Supporting metadata and current state." :sticky="false">
                    <DisplayField label="Status"><StatusBadge label="Open" tone="success" /></DisplayField>
                    <DisplayField label="Created" value="July 12, 2026" />
                    <DisplayField label="Last updated" value="Today at 8:42 AM" />
                    <DisplayField label="Owner" value="Sherman Potter" />
                    <FormDivider />
                    <Button variant="outline" class="w-full">View audit history</Button>
                </InfoPanel>
            </div>
            <CodeExample :code="showTemplateCode" label="Approved show-page template" />
        </section>

        <section id="forms" class="scroll-mt-24 space-y-5">
            <SectionHeader title="Form pattern" description="Forms are grouped by purpose, use clear field guidance, and provide a live summary on complex workflows." />
            <form class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]" @submit.prevent>
                <div class="space-y-6">
                    <FormSection title="Primary information" description="Place the most important and most frequently edited fields first.">
                        <div class="grid gap-5 md:grid-cols-2">
                            <FormField label="Position title" for-id="showcase-title" required description="Use the approved organizational title.">
                                <template #default="{ describedBy }">
                                    <Input id="showcase-title" value="Frontend Developer" :aria-describedby="describedBy" />
                                </template>
                            </FormField>
                            <FormField label="Position code" for-id="showcase-code" error="A unique position code is required.">
                                <template #default="{ describedBy }">
                                    <Input id="showcase-code" aria-invalid="true" :aria-describedby="describedBy" />
                                </template>
                            </FormField>
                        </div>
                        <FormField label="Mission summary" for-id="showcase-description" description="Summarize the purpose and primary responsibilities.">
                            <template #default="{ describedBy }">
                                <Textarea id="showcase-description" rows="4" :aria-describedby="describedBy" />
                            </template>
                        </FormField>
                    </FormSection>

                    <FormSection title="Work arrangement" description="Boolean choices use a full-row target with supporting text.">
                        <BooleanField id="showcase-remote" v-model="remoteEligible" label="Remote eligible" description="This position may be performed remotely when customer requirements allow." />
                    </FormSection>

                    <FormActions cancel-href="/admin" submit-label="Save Example" :dirty="true" sticky />
                </div>

                <InfoPanel title="Position summary" description="Summaries update while the user edits the form." :sticky="false">
                    <DisplayField label="Title" value="Frontend Developer" />
                    <DisplayField label="Status"><StatusBadge label="Open" tone="success" /></DisplayField>
                    <DisplayField label="Organization" value="Digital Solutions" />
                    <DisplayField label="Remote eligible" :value="remoteEligible ? 'Yes' : 'No'" />
                </InfoPanel>
            </form>
            <CodeExample :code="formTemplateCode" label="Approved create/edit template" />
        </section>

        <section id="feedback" class="scroll-mt-24 space-y-5">
            <SectionHeader title="Feedback and state" description="Use explicit messages that explain what happened and what the user should do next." />
            <div class="grid gap-4 lg:grid-cols-2">
                <Alert>
                    <CheckCircle2 class="h-4 w-4" />
                    <AlertTitle>Position saved</AlertTitle>
                    <AlertDescription>The updated position information is now available to authorized users.</AlertDescription>
                </Alert>
                <Alert variant="destructive">
                    <CircleAlert class="h-4 w-4" />
                    <AlertTitle>Unable to save</AlertTitle>
                    <AlertDescription>Correct the highlighted fields and submit the form again.</AlertDescription>
                </Alert>
            </div>

            <div class="space-y-4">
                <div class="flex flex-wrap gap-2 rounded-xl border bg-background p-5">
                    <StatusBadge label="Active" tone="success" />
                    <StatusBadge label="Pending" tone="warning" />
                    <StatusBadge label="Blocked" tone="danger" />
                    <StatusBadge label="In Review" tone="info" />
                    <StatusBadge label="Archived" tone="neutral" />
                </div>
                <EmptyState
                    title="No positions match these filters"
                    description="Try changing the search terms or clear the active filters to see more results."
                    :icon="Inbox"
                >
                    <template #actions>
                        <Button variant="outline">Clear Filters</Button>
                        <Button>Create Position</Button>
                    </template>
                </EmptyState>
            </div>
        </section>

        <section id="standards" class="scroll-mt-24 space-y-4">
            <SectionHeader title="Visual and interaction standards" description="These rules keep independent modules feeling like one application." />
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <GuidelineCard title="Typography" description="Page titles use text-2xl/text-3xl. Section titles use text-lg. Field labels stay concise and sentence case." :icon="FileText" />
                <GuidelineCard title="Spacing" description="Use gap-6 between major sections, gap-5 inside forms, and p-5 or p-6 inside cards." :icon="Columns3" />
                <GuidelineCard title="Buttons" description="Primary actions appear last. Cancel is outline. Destructive actions remain visually separated from Save." :icon="ClipboardList" />
                <GuidelineCard title="Icons" description="Icons clarify meaning; they do not replace visible labels for primary actions." :icon="Palette" />
                <GuidelineCard title="Status color" description="Always pair color with status text, and use the shared StatusBadge tones." :icon="ShieldCheck" />
                <GuidelineCard title="Notifications" description="Messages state the outcome, affected record, and next step where one is required." :icon="Bell" />
                <GuidelineCard title="Row actions" description="View, Edit, Delete, Manage, and other record actions belong in the shared actions dropdown, not separate table buttons." :icon="ClipboardList" />
                <GuidelineCard title="Confirmations" description="Never use browser alert() or confirm(). Use IRAD Alert/AlertDialog components so feedback is accessible and visually consistent." :icon="CircleAlert" />
            </div>
        </section>

        <section id="templates" class="scroll-mt-24 space-y-4">
            <SectionHeader title="Approved page templates" description="Start new modules from these patterns rather than copying an arbitrary existing page." />
            <div class="grid gap-4 lg:grid-cols-3">
                <GuidelineCard title="List page" description="Header → optional statistics → filters → table → pagination." :icon="Search">
                    <p>Use for searchable collections of records and administrative indexes.</p>
                </GuidelineCard>
                <GuidelineCard title="Show page" description="Identity → statistics → grouped details → information panel → related records." :icon="PanelRight">
                    <p>Use for read-focused pages where current state and relationships matter.</p>
                </GuidelineCard>
                <GuidelineCard title="Create/Edit page" description="Header → grouped form sections → live summary → sticky actions." :icon="FileText">
                    <p>Use for complex records. Small forms may omit the summary panel.</p>
                </GuidelineCard>
            </div>

            <Alert>
                <BookOpen class="h-4 w-4" />
                <AlertTitle>Use this page as the source of truth</AlertTitle>
                <AlertDescription>
                    When an existing module conflicts with this design system, treat the existing module as migration work—not as a second approved pattern.
                </AlertDescription>
            </Alert>
        </section>
    </PageContainer>
</template>
