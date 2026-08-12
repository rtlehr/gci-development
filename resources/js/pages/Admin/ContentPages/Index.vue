<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import ConfirmActionDialog from '@/components/ConfirmActionDialog.vue';
import ListFilters from '@/components/Lists/ListFilters.vue';
import ListRowActions from '@/components/Lists/ListRowActions.vue';
import ListTableShell from '@/components/Lists/ListTableShell.vue';
import ListToolbar from '@/components/Lists/ListToolbar.vue';
import PageContainer from '@/components/layout/PageContainer.vue';
import { DropdownMenuItem } from '@/components/ui/dropdown-menu';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

const props = defineProps<{
    pages: any;
    filters: { search: string };
}>();

const search = ref(props.filters.search ?? '');
const deleteDialogOpen = ref(false);
const pendingDeleteId = ref<number | null>(null);

const typeLabels: Record<string, string> = {
    standard: 'Standard',
    faq: 'FAQ',
    contact_directory: 'Contacts',
    resource_library: 'Resources',
    announcement: 'Announcement',
    policy: 'Policy / Documentation',
};

function apply(): void {
    router.get(
        '/admin/content-pages',
        { search: search.value },
        { preserveState: true, replace: true },
    );
}

function remove(id: number): void {
    pendingDeleteId.value = id;
    deleteDialogOpen.value = true;
}

function confirmDelete(): void {
    if (!pendingDeleteId.value) return;

    router.delete(`/admin/content-pages/${pendingDeleteId.value}`, {
        preserveScroll: true,
        onFinish: () => {
            deleteDialogOpen.value = false;
            pendingDeleteId.value = null;
        },
    });
}
</script>

<template>
    <Head title="Content Pages" />

    <PageContainer class="space-y-6">
        <ListToolbar
            eyebrow="Content and Help"
            title="Content Pages"
            description="Manage templated program information, contacts, resources, FAQs, policies, documentation, announcements, and help links."
            create-label="Create Page"
            create-href="/admin/content-pages/create"
            :can-create="true"
            :show-column-settings="false"
        />

        <ListFilters
            v-model:search="search"
            search-placeholder="Search title, slug, navigation label, or template..."
            apply-label="Search"
            @apply="apply"
            @reset="search = ''; apply()"
        />

        <ListTableShell label="Content page results">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Page</TableHead>
                        <TableHead>Template</TableHead>
                        <TableHead>Visibility</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead>Active</TableHead>
                        <TableHead>Menu</TableHead>
                        <TableHead>Dates</TableHead>
                        <TableHead class="text-right">Actions</TableHead>
                    </TableRow>
                </TableHeader>

                <TableBody>
                    <TableRow v-if="!pages.data?.length">
                        <TableCell colspan="8" class="py-8 text-center text-muted-foreground">
                            No content pages found.
                        </TableCell>
                    </TableRow>

                    <TableRow v-for="page in pages.data" :key="page.id" class="hover:bg-muted/50">
                        <TableCell>
                            <div class="font-semibold">{{ page.title }}</div>
                            <div class="text-xs text-muted-foreground">/pages/{{ page.slug }}</div>
                        </TableCell>
                        <TableCell>
                            {{ typeLabels[page.page_type] ?? 'Standard' }}
                            <div v-if="page.page_type === 'faq'" class="text-xs text-muted-foreground">
                                {{ page.faq_items_count ?? 0 }} questions
                            </div>
                        </TableCell>
                        <TableCell class="capitalize">{{ page.visibility }}</TableCell>
                        <TableCell class="capitalize">{{ page.status }}</TableCell>
                        <TableCell>
                            <span
                                class="inline-flex rounded-full border px-2 py-1 text-xs font-medium"
                                :class="page.is_active
                                    ? 'border-emerald-200 bg-emerald-50 text-emerald-700'
                                    : 'border-slate-200 bg-slate-50 text-slate-600'"
                            >
                                {{ page.is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </TableCell>
                        <TableCell class="capitalize">{{ page.menu_location }}</TableCell>
                        <TableCell class="text-xs text-muted-foreground">
                            <div v-if="page.effective_at">Starts {{ new Date(page.effective_at).toLocaleString() }}</div>
                            <div v-if="page.expires_at">Ends {{ new Date(page.expires_at).toLocaleString() }}</div>
                            <span v-if="!page.effective_at && !page.expires_at">Always</span>
                        </TableCell>
                        <TableCell class="text-right">
                            <ListRowActions :aria-label="`Actions for ${page.title}`">
                                <DropdownMenuItem as-child>
                                    <Link :href="`/admin/content-pages/${page.id}/edit`">Edit</Link>
                                </DropdownMenuItem>
                                <DropdownMenuItem class="text-destructive focus:text-destructive" @click="remove(page.id)">
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
            title="Delete Content Page?"
            description="This content page will be permanently deleted. This action cannot be undone."
            confirm-label="Delete"
            destructive
            @confirm="confirmDelete"
        />
    </PageContainer>
</template>
