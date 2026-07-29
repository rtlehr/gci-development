<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Pencil, Plus, Search, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';
import PageContainer from '@/components/layout/PageContainer.vue';
import PageHeader from '@/components/layout/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

const props = defineProps<{
    pages: any;
    filters: { search: string };
}>();

const search = ref(props.filters.search ?? '');

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
    if (confirm('Delete this content page?')) {
        router.delete(`/admin/content-pages/${id}`);
    }
}
</script>

<template>
    <Head title="Content Pages" />

    <PageContainer class="space-y-6">
        <PageHeader
            eyebrow="Content and Help"
            title="Content Pages"
            description="Manage templated program information, contacts, resources, FAQs, policies, documentation, announcements, and help links."
        >
            <template #actions>
                <Button as-child>
                    <Link href="/admin/content-pages/create">
                        <Plus class="mr-2 h-4 w-4" />
                        Create Page
                    </Link>
                </Button>
            </template>
        </PageHeader>

        <form class="flex gap-2 rounded-xl border bg-card p-4" @submit.prevent="apply">
            <Input
                v-model="search"
                placeholder="Search title, slug, navigation label, or template..."
            />
            <Button type="submit">
                <Search class="mr-2 h-4 w-4" />
                Search
            </Button>
        </form>

        <div class="overflow-hidden rounded-xl border bg-card">
            <table class="w-full text-sm">
                <thead class="bg-muted/40 text-left">
                    <tr>
                        <th class="p-3">Page</th>
                        <th class="p-3">Template</th>
                        <th class="p-3">Visibility</th>
                        <th class="p-3">Status</th>
                        <th class="p-3">Active</th>
                        <th class="p-3">Menu</th>
                        <th class="p-3">Dates</th>
                        <th class="p-3 text-right">Actions</th>
                    </tr>
                </thead>

                <tbody>
                    <tr
                        v-for="page in pages.data"
                        :key="page.id"
                        class="border-t"
                    >
                        <td class="p-3">
                            <div class="font-semibold">{{ page.title }}</div>
                            <div class="text-xs text-muted-foreground">
                                /pages/{{ page.slug }}
                            </div>
                        </td>

                        <td class="p-3">
                            {{ typeLabels[page.page_type] ?? 'Standard' }}
                            <div
                                v-if="page.page_type === 'faq'"
                                class="text-xs text-muted-foreground"
                            >
                                {{ page.faq_items_count ?? 0 }} questions
                            </div>
                        </td>

                        <td class="p-3 capitalize">{{ page.visibility }}</td>
                        <td class="p-3 capitalize">{{ page.status }}</td>
                        <td class="p-3">
                            <span
                                class="inline-flex rounded-full border px-2 py-1 text-xs font-medium"
                                :class="page.is_active
                                    ? 'border-emerald-200 bg-emerald-50 text-emerald-700'
                                    : 'border-slate-200 bg-slate-50 text-slate-600'"
                            >
                                {{ page.is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="p-3 capitalize">{{ page.menu_location }}</td>

                        <td class="p-3 text-xs text-muted-foreground">
                            <div v-if="page.effective_at">
                                Starts {{ new Date(page.effective_at).toLocaleString() }}
                            </div>
                            <div v-if="page.expires_at">
                                Ends {{ new Date(page.expires_at).toLocaleString() }}
                            </div>
                            <span v-if="!page.effective_at && !page.expires_at">
                                Always
                            </span>
                        </td>

                        <td class="p-3">
                            <div class="flex justify-end gap-2">
                                <Button variant="outline" size="sm" as-child>
                                    <Link :href="`/admin/content-pages/${page.id}/edit`">
                                        <Pencil class="h-4 w-4" />
                                    </Link>
                                </Button>

                                <Button
                                    variant="outline"
                                    size="sm"
                                    aria-label="Delete page"
                                    @click="remove(page.id)"
                                >
                                    <Trash2 class="h-4 w-4" />
                                </Button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </PageContainer>
</template>
