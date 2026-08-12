<template>
    <PageContainer>
        <ListToolbar
            title="Page Help"
            description="Manage help content shown in the help panel."
            create-label="Create Help Page"
            create-href="/admin/page-help/create"
            :can-create="true"
            :can-export="true"
            export-label="Export Help"
            :show-column-settings="false"
            @export="exportHelp"
        >
            <template #before-actions>
                <input
                    ref="importFileInput"
                    type="file"
                    accept=".json,application/json"
                    class="hidden"
                    @change="importHelp"
                />

                <Button
                    type="button"
                    variant="outline"
                    :disabled="importForm.processing"
                    @click="chooseImportFile"
                >
                    <Upload class="mr-2 h-4 w-4" aria-hidden="true" />
                    {{ importForm.processing ? 'Importing...' : 'Import Help' }}
                </Button>
            </template>
        </ListToolbar>

        <Alert v-if="importForm.errors.help_file" variant="destructive">
            <CircleAlert class="h-4 w-4" aria-hidden="true" />
            <AlertTitle>Import failed</AlertTitle>
            <AlertDescription>
                {{ importForm.errors.help_file }}
            </AlertDescription>
        </Alert>

        <Alert>
            <Info class="h-4 w-4" aria-hidden="true" />
            <AlertTitle>Protect your help content</AlertTitle>
            <AlertDescription>
                Export Help downloads all Page Help content as a JSON backup. After a database rebuild,
                use Import Help to restore the file. Existing help pages with the same help key are updated;
                other help pages are left in place.
            </AlertDescription>
        </Alert>

        <ListFilters
            v-model:search="filterForm.search"
            search-placeholder="Search by key or title..."
            apply-label="Search"
            @apply="applyFilters"
            @reset="resetFilters"
        />

        <ListTableShell label="Page help results">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Help Key</TableHead>
                        <TableHead>Title</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead class="text-right">Actions</TableHead>
                    </TableRow>
                </TableHeader>

                <TableBody>
                    <TableRow v-if="!helpPages?.data?.length">
                        <TableCell colspan="4" class="py-8 text-center text-muted-foreground">
                            No help pages found.
                        </TableCell>
                    </TableRow>

                    <TableRow
                        v-for="item in helpPages.data"
                        :key="item.id"
                    >
                        <TableCell>{{ item.help_key }}</TableCell>
                        <TableCell>{{ item.title }}</TableCell>
                        <TableCell>{{ item.is_active ? 'Active' : 'Inactive' }}</TableCell>
                        <TableCell class="text-right">
                            <ListRowActions :aria-label="`Actions for ${item.title}`">
                                <DropdownMenuItem as-child>
                                    <Link :href="`/admin/page-help/${item.id}/edit`">Edit</Link>
                                </DropdownMenuItem>
                                <DropdownMenuItem class="text-destructive focus:text-destructive" @click="deleteItem(item.id)">
                                    Delete
                                </DropdownMenuItem>
                            </ListRowActions>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </ListTableShell>
        <ConfirmActionDialog
            v-model:open="deleteDialogOpen"
            title="Delete Help Page?"
            description="This help page will be permanently deleted. This action cannot be undone."
            confirm-label="Delete"
            destructive
            @confirm="confirmDelete"
        />
    </PageContainer>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { Link, router, useForm } from '@inertiajs/vue3'
import { CircleAlert, Info, Upload } from 'lucide-vue-next'
import ConfirmActionDialog from '@/components/ConfirmActionDialog.vue'
import ListRowActions from '@/components/Lists/ListRowActions.vue'
import ListTableShell from '@/components/Lists/ListTableShell.vue'
import PageContainer from '@/components/layout/PageContainer.vue'
import ListToolbar from '@/components/Lists/ListToolbar.vue'
import ListFilters from '@/components/Lists/ListFilters.vue'
import {
    Alert,
    AlertDescription,
    AlertTitle,
} from '@/components/ui/alert'
import { Button } from '@/components/ui/button'
import { DropdownMenuItem } from '@/components/ui/dropdown-menu'
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table'

// Backend-provided help page list and current filter state.
const props = defineProps({
    helpPages: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({
            search: '',
        }),
    },
})

// Local filter form state.
// Initialized from backend filters so the search input reflects the current URL/query state.
const filterForm = reactive({
    search: props.filters?.search ?? '',
})

const importFileInput = ref(null)
const deleteDialogOpen = ref(false)
const pendingDeleteId = ref(null)
const importForm = useForm({
    help_file: null,
})

/**
 * Applies the current search filter and reloads the help page list.
 */
function applyFilters() {
    router.get('/admin/page-help', {
        search: filterForm.search,
    }, {
        preserveState: true,
        replace: true,
    })
}

/**
 * Clears the search filter and reloads the default help page list.
 */
function resetFilters() {
    filterForm.search = ''

    router.get('/admin/page-help', {}, {
        preserveState: true,
        replace: true,
    })
}

/**
 * Downloads a portable JSON backup of every Page Help record.
 */
function exportHelp() {
    window.location.href = '/admin/page-help/export'
}

/**
 * Opens the native file chooser for a previously exported Page Help JSON file.
 */
function chooseImportFile() {
    importForm.clearErrors()
    importFileInput.value?.click()
}

/**
 * Uploads the selected Page Help JSON file.
 *
 * Existing records are matched by help_key and updated. Records not present
 * in the import file are intentionally preserved.
 */
function importHelp(event) {
    const file = event.target.files?.[0]

    if (!file) return

    importForm.help_file = file

    importForm.post('/admin/page-help/import', {
        forceFormData: true,
        preserveScroll: true,
        onFinish: () => {
            importForm.help_file = null

            if (importFileInput.value) {
                importFileInput.value.value = ''
            }
        },
    })
}

/**
 * Confirms deletion with the user, then deletes the selected help page.
 *
 * @param {number|string} id
 */
function deleteItem(id) {
    pendingDeleteId.value = id
    deleteDialogOpen.value = true
}

function confirmDelete() {
    if (!pendingDeleteId.value) return

    router.delete(`/admin/page-help/${pendingDeleteId.value}`, {
        preserveScroll: true,
        onFinish: () => {
            deleteDialogOpen.value = false
            pendingDeleteId.value = null
        },
    })
}
</script>
