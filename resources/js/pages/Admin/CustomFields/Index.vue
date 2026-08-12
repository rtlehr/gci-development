<script setup lang="ts">
import { Link, router, useForm } from '@inertiajs/vue3'
import ListTableShell from '@/components/Lists/ListTableShell.vue'
import { computed, reactive, ref } from 'vue'
import { Download, MoreHorizontal, Plus, Upload } from 'lucide-vue-next'
import PageContainer from '@/components/layout/PageContainer.vue'
import PageHeader from '@/components/layout/PageHeader.vue'
import { useAuth } from '@/composables/useAuth'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import {
    Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/components/ui/select'
import {
    Table, TableBody, TableCell, TableHead, TableHeader, TableRow,
} from '@/components/ui/table'
import {
    DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuLabel,
    DropdownMenuSeparator, DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import {
    AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent,
    AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle,
} from '@/components/ui/alert-dialog'

const props = defineProps<{
    fields: Record<string, any>
    filters?: { search?: string; entity_type?: string }
}>()

const { can } = useAuth()
const filterForm = reactive({
    search: props.filters?.search ?? '',
    entity_type: props.filters?.entity_type || 'all',
})
const deleteDialogOpen = ref(false)
const selectedField = ref<Record<string, any> | null>(null)

const rows = computed(() => props.fields?.data ?? [])

const importFileInput = ref<HTMLInputElement | null>(null)
const importForm = useForm({ custom_fields_file: null as File | null })

function exportDefinitions(): void {
    window.location.href = '/admin/custom-fields/export'
}

function chooseImportFile(): void {
    importForm.clearErrors()
    importFileInput.value?.click()
}

function importDefinitions(event: Event): void {
    const input = event.target as HTMLInputElement
    const file = input.files?.[0]
    if (!file) return

    importForm.custom_fields_file = file
    importForm.post('/admin/custom-fields/import', {
        forceFormData: true,
        preserveScroll: true,
        onFinish: () => {
            importForm.custom_fields_file = null
            if (importFileInput.value) importFileInput.value.value = ''
        },
    })
}


function applyFilters(): void {
    router.get('/admin/custom-fields', {
        search: filterForm.search || undefined,
        entity_type: filterForm.entity_type === 'all' ? undefined : filterForm.entity_type,
    }, { preserveState: true, replace: true })
}

function resetFilters(): void {
    filterForm.search = ''
    filterForm.entity_type = 'all'
    router.get('/admin/custom-fields')
}

function openDelete(field: Record<string, any>): void {
    selectedField.value = field
    deleteDialogOpen.value = true
}

function confirmDelete(): void {
    if (!selectedField.value) return
    router.delete(`/admin/custom-fields/${selectedField.value.id}`, {
        preserveScroll: true,
        onFinish: () => {
            deleteDialogOpen.value = false
            selectedField.value = null
        },
    })
}

function typeLabel(type: string): string {
    return ({ text: 'Text Field', textarea: 'Multiline Text', radio: 'Radio Group', checkbox: 'Checkbox Group', date: 'Date Picker' } as Record<string, string>)[type] ?? type
}
</script>

<template>
    <PageContainer size="wide">
        <PageHeader
            title="Custom Fields"
            description="Configure installation-specific fields that appear on Person and Position records."
            eyebrow="Configuration"
            back-href="/admin"
            back-label="Admin"
        >
            <template #actions>
                <div v-if="can('manage_custom_fields')" class="flex flex-wrap gap-2">
                    <input ref="importFileInput" type="file" accept="application/json,.json" class="hidden" @change="importDefinitions">
                    <Button type="button" variant="outline" @click="exportDefinitions">
                        <Download class="mr-2 h-4 w-4" />Export Definitions
                    </Button>
                    <Button type="button" variant="outline" :disabled="importForm.processing" @click="chooseImportFile">
                        <Upload class="mr-2 h-4 w-4" />{{ importForm.processing ? 'Importing...' : 'Import Definitions' }}
                    </Button>
                    <Link href="/admin/custom-fields/create">
                        <Button><Plus class="mr-2 h-4 w-4" />Add Custom Field</Button>
                    </Link>
                </div>
            </template>
        </PageHeader>

        <div class="space-y-6">
            <p v-if="importForm.errors.custom_fields_file" class="rounded-lg border border-destructive/30 bg-destructive/5 p-3 text-sm text-destructive">{{ importForm.errors.custom_fields_file }}</p>
            <form class="grid gap-3 rounded-xl border bg-background p-4 md:grid-cols-[minmax(0,1fr)_220px_auto_auto]" @submit.prevent="applyFilters">
                <Input v-model="filterForm.search" placeholder="Search fields..." />
                <Select v-model="filterForm.entity_type">
                    <SelectTrigger><SelectValue /></SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">People and Positions</SelectItem>
                        <SelectItem value="person">Person</SelectItem>
                        <SelectItem value="position">Position</SelectItem>
                    </SelectContent>
                </Select>
                <Button type="submit">Search</Button>
                <Button type="button" variant="outline" @click="resetFilters">Reset</Button>
            </form>

            <ListTableShell label="Custom field results">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Field</TableHead>
                            <TableHead>Applies To</TableHead>
                            <TableHead>Type</TableHead>
                            <TableHead>Required</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead>List / Search / Filter</TableHead>
                            <TableHead>Order</TableHead>
                            <TableHead class="text-right">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-if="!rows.length">
                            <TableCell colspan="8" class="py-10 text-center text-muted-foreground">No custom fields found.</TableCell>
                        </TableRow>
                        <TableRow v-for="field in rows" :key="field.id">
                            <TableCell>
                                <p class="font-medium">{{ field.name }}</p>
                                <p class="text-xs text-muted-foreground">{{ field.key }}</p>
                            </TableCell>
                            <TableCell>{{ field.entity_type === 'person' ? 'Person' : 'Position' }}</TableCell>
                            <TableCell>{{ typeLabel(field.field_type) }}</TableCell>
                            <TableCell>{{ field.is_required ? 'Yes' : 'No' }}</TableCell>
                            <TableCell><Badge :variant="field.is_active ? 'default' : 'secondary'">{{ field.is_active ? 'Active' : 'Inactive' }}</Badge></TableCell>
                            <TableCell class="text-xs text-muted-foreground">
                                {{ field.is_list_column ? 'Column' : '—' }}<span v-if="field.is_searchable"> · Search</span><span v-if="field.is_filterable"> · Filter</span>
                            </TableCell>
                            <TableCell>{{ field.sort_order }}</TableCell>
                            <TableCell class="text-right">
                                <DropdownMenu v-if="can('manage_custom_fields')">
                                    <DropdownMenuTrigger as-child><Button variant="ghost" size="icon"><MoreHorizontal class="h-4 w-4" /></Button></DropdownMenuTrigger>
                                    <DropdownMenuContent align="end">
                                        <DropdownMenuLabel>Actions</DropdownMenuLabel>
                                        <DropdownMenuSeparator />
                                        <DropdownMenuItem as-child><Link :href="`/admin/custom-fields/${field.id}/edit`">Edit</Link></DropdownMenuItem>
                                        <DropdownMenuItem class="text-destructive focus:text-destructive" @click="openDelete(field)">
                                            {{ field.values_count > 0 ? 'Deactivate / Remove' : 'Delete' }}
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </ListTableShell>

            <div v-if="fields.last_page > 1" class="flex items-center justify-between text-sm">
                <span class="text-muted-foreground">Showing {{ fields.from ?? 0 }}–{{ fields.to ?? 0 }} of {{ fields.total ?? 0 }}</span>
                <div class="flex gap-2">
                    <Link v-if="fields.prev_page_url" :href="fields.prev_page_url"><Button size="sm" variant="outline">Previous</Button></Link>
                    <Link v-if="fields.next_page_url" :href="fields.next_page_url"><Button size="sm" variant="outline">Next</Button></Link>
                </div>
            </div>
        </div>

        <AlertDialog :open="deleteDialogOpen" @update:open="deleteDialogOpen = $event">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>{{ selectedField?.values_count > 0 ? 'Deactivate Custom Field?' : 'Delete Custom Field?' }}</AlertDialogTitle>
                    <AlertDialogDescription>
                        <template v-if="selectedField?.values_count > 0">
                            This field already contains saved data. It will be deactivated so historical information is preserved.
                        </template>
                        <template v-else>
                            This field has no saved values and can be permanently deleted.
                        </template>
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel @click="deleteDialogOpen = false">Cancel</AlertDialogCancel>
                    <AlertDialogAction @click="confirmDelete">Continue</AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </PageContainer>
</template>
