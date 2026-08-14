<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Save } from 'lucide-vue-next';
import { computed } from 'vue';
import ContentPageFaqEditor, {
    type FaqEditorItem,
} from '@/components/content/ContentPageFaqEditor.vue';
import RichContentEditor from '@/components/content/RichContentEditor.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

type PageTypeOption = {
    label: string;
    description: string;
};

const props = defineProps<{
    page?: any;
    pageTypes: Record<string, PageTypeOption>;
}>();

const form = useForm({
    title: props.page?.title ?? '',
    slug: props.page?.slug ?? '',
    navigation_label: props.page?.navigation_label ?? '',
    summary: props.page?.summary ?? '',
    content_html: props.page?.content_html ?? '',
    page_type: props.page?.page_type ?? 'standard',
    visibility: props.page?.visibility ?? 'both',
    status: props.page?.status ?? 'draft',
    menu_location: props.page?.menu_location ?? 'header',
    is_active: props.page?.is_active ?? true,
    sort_order: props.page?.sort_order ?? 0,
    effective_at: props.page?.effective_at?.slice?.(0, 16) ?? '',
    expires_at: props.page?.expires_at?.slice?.(0, 16) ?? '',
    help_key: props.page?.help_key ?? '',
    faq_items: (props.page?.faq_items ?? []).map((item: any, index: number): FaqEditorItem => ({
        id: item.id,
        question: item.question ?? '',
        answer: item.answer ?? '',
        is_active: item.is_active ?? true,
        sort_order: item.sort_order ?? ((index + 1) * 10),
    })),
});

const selectedType = computed(() => props.pageTypes[form.page_type]);

function submit(): void {
    if (props.page) {
        form.put(`/admin/content-pages/${props.page.id}`);
        return;
    }

    form.post('/admin/content-pages');
}
</script>

<template>
    <form class="space-y-6" @submit.prevent="submit">
        <section class="rounded-xl border bg-card p-6">
            <div class="mb-5">
                <h2 id="content-page-template-heading" class="text-base font-semibold">Page template</h2>
                <p class="mt-1 text-sm text-muted-foreground">
                    Choose the content structure that best matches this page.
                </p>
            </div>

            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3" role="radiogroup" aria-labelledby="content-page-template-heading" :aria-describedby="form.errors.page_type ? 'content-page-type-error' : undefined">
                <label
                    v-for="(option, value) in pageTypes"
                    :key="value"
                    class="cursor-pointer rounded-xl border p-4 transition"
                    :class="form.page_type === value
                        ? 'border-[var(--portal-primary)] bg-[color:var(--portal-primary)]/5 ring-1 ring-[var(--portal-primary)]'
                        : 'hover:bg-muted/30'"
                >
                    <input
                        v-model="form.page_type"
                        type="radio"
                        name="page_type"
                        :value="value"
                        class="sr-only"
                    />
                    <span class="block font-semibold">{{ option.label }}</span>
                    <span class="mt-1 block text-sm leading-5 text-muted-foreground">
                        {{ option.description }}
                    </span>
                </label>
            </div>

            <p v-if="form.errors.page_type" id="content-page-type-error" class="mt-2 text-sm text-destructive" role="alert">
                {{ form.errors.page_type }}
            </p>
        </section>

        <div class="grid gap-5 rounded-xl border bg-card p-6 md:grid-cols-2">
            <div class="space-y-2">
                <Label for="content-page-title">Title</Label>
                <Input id="content-page-title" v-model="form.title" required :aria-invalid="Boolean(form.errors.title)" :aria-describedby="form.errors.title ? 'content-page-title-error' : undefined" />
                <p v-if="form.errors.title" id="content-page-title-error" class="text-sm text-destructive">{{ form.errors.title }}</p>
            </div>

            <div class="space-y-2">
                <Label for="content-page-slug">Slug</Label>
                <Input id="content-page-slug" v-model="form.slug" placeholder="Generated from title when blank" :aria-invalid="Boolean(form.errors.slug)" :aria-describedby="form.errors.slug ? 'content-page-slug-error' : undefined" />
                <p v-if="form.errors.slug" id="content-page-slug-error" class="text-sm text-destructive">{{ form.errors.slug }}</p>
            </div>

            <div class="space-y-2">
                <Label for="content-page-navigation-label">Navigation label</Label>
                <Input id="content-page-navigation-label" v-model="form.navigation_label" />
            </div>

            <div class="space-y-2">
                <Label for="content-page-help-key">Help key</Label>
                <Input id="content-page-help-key" v-model="form.help_key" placeholder="content.page-name" />
            </div>

            <div class="space-y-2 md:col-span-2">
                <Label for="content-page-summary">Summary</Label>
                <Textarea id="content-page-summary" v-model="form.summary" rows="3" />
            </div>

            <div class="space-y-2">
                <Label for="content-page-visibility">Visibility</Label>
                <select id="content-page-visibility" v-model="form.visibility" class="h-10 w-full rounded-md border bg-background px-3">
                    <option value="public">Public</option>
                    <option value="portal">Authenticated Portal</option>
                    <option value="both">Both</option>
                </select>
            </div>

            <div class="space-y-2">
                <Label for="content-page-status">Publication status</Label>
                <select id="content-page-status" v-model="form.status" class="h-10 w-full rounded-md border bg-background px-3">
                    <option value="draft">Draft</option>
                    <option value="published">Published</option>
                    <option value="archived">Archived</option>
                </select>
            </div>

            <div class="space-y-2">
                <Label for="content-page-menu-location">Menu location</Label>
                <select id="content-page-menu-location" v-model="form.menu_location" class="h-10 w-full rounded-md border bg-background px-3">
                    <option value="none">None</option>
                    <option value="header">Header</option>
                    <option value="footer">Footer</option>
                    <option value="both">Header and footer</option>
                </select>
            </div>

            <div class="space-y-2">
                <Label for="content-page-sort-order">Sort order</Label>
                <Input id="content-page-sort-order" v-model.number="form.sort_order" type="number" min="0" />
            </div>

            <div class="space-y-2 md:col-span-2">
                <label class="flex items-start gap-3 rounded-lg border bg-muted/20 p-4">
                    <input
                        id="content-page-active"
                        v-model="form.is_active"
                        type="checkbox"
                        aria-describedby="content-page-active-description"
                        class="mt-0.5 h-4 w-4 rounded border-input"
                    />
                    <span>
                        <span class="block font-medium">Active</span>
                        <span id="content-page-active-description" class="mt-1 block text-sm text-muted-foreground">
                            Show this page in its selected header or footer menu location.
                            Inactive pages remain available by direct URL when published.
                        </span>
                    </span>
                </label>
            </div>

            <div class="space-y-2">
                <Label for="content-page-effective-at">Effective date</Label>
                <Input id="content-page-effective-at" v-model="form.effective_at" type="datetime-local" />
            </div>

            <div class="space-y-2">
                <Label for="content-page-expires-at">Expiration date</Label>
                <Input id="content-page-expires-at" v-model="form.expires_at" type="datetime-local" />
            </div>
        </div>

        <section class="space-y-2">
            <div>
                <Label id="content-page-rich-content-label">Page introduction and rich content</Label>
                <p class="mt-1 text-sm text-muted-foreground">
                    {{ selectedType?.description }}
                    <span v-if="form.page_type === 'faq'">
                        Use this area for introductory text; manage individual questions below.
                    </span>
                </p>
            </div>
            <RichContentEditor v-model="form.content_html" aria-labelledby="content-page-rich-content-label" />
        </section>

        <ContentPageFaqEditor
            v-if="form.page_type === 'faq'"
            v-model="form.faq_items"
        />

        <div class="flex justify-end">
            <Button type="submit" :disabled="form.processing">
                <Save class="mr-2 h-4 w-4" />
                {{ form.processing ? 'Saving...' : 'Save Page' }}
            </Button>
        </div>
    </form>
</template>
