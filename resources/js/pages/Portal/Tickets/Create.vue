<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Upload } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

const props = defineProps<{
    currentUser: { id: number; username: string; email?: string; person_code?: string | null };
    sourceUrl?: string;
    categories: string[];
}>();

const form = useForm({
    title: '',
    request_type: 'bug',
    importance: 'nice_to_have',
    category: 'Other',
    description: '',
    source_url: props.sourceUrl || '',
    screenshot: null as File | null,
});

function selectScreenshot(event: Event): void {
    const input = event.target as HTMLInputElement;
    form.screenshot = input.files?.[0] ?? null;
}

function submit(): void {
    form.post('/portal/tickets', {
        forceFormData: true,
    });
}
</script>

<template>
    <Head title="Submit Support Request" />

    <section class="border-b border-[#e3e3e3] bg-white">
        <div class="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">
            <Link href="/portal/tickets" class="inline-flex items-center gap-2 text-sm font-semibold text-[#005c43] hover:underline">
                <ArrowLeft class="h-4 w-4" />
                Back to my tickets
            </Link>
            <p class="mt-6 text-sm font-bold uppercase tracking-[0.16em] text-[#005c43]">Support</p>
            <h1 class="mt-2 text-3xl font-bold tracking-tight text-[#3a3a3a]">Submit a request</h1>
            <p class="mt-3 text-[#3a3a3a]/70">Report a problem or suggest an improvement. Your identity is provided automatically by IRAD.</p>
        </div>
    </section>

    <div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
        <form class="space-y-6 rounded-xl border border-[#e3e3e3] bg-white p-6 shadow-sm" @submit.prevent="submit">
            <div class="grid gap-5 md:grid-cols-2">
                <div class="space-y-2 md:col-span-2">
                    <Label for="title">Title</Label>
                    <Input id="title" v-model="form.title" placeholder="Briefly describe the request" />
                    <p v-if="form.errors.title" class="text-sm text-destructive">{{ form.errors.title }}</p>
                </div>

                <div class="space-y-2">
                    <Label for="submitted-by">Submitted by</Label>
                    <Input id="submitted-by" :model-value="currentUser.username" disabled />
                </div>

                <div class="space-y-2">
                    <Label for="request-type">Request type</Label>
                    <select id="request-type" v-model="form.request_type" class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm">
                        <option value="bug">Bug</option>
                        <option value="improvement">Improvement</option>
                    </select>
                </div>

                <div class="space-y-2">
                    <Label for="importance">Importance</Label>
                    <select id="importance" v-model="form.importance" class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm">
                        <option value="show_stopper">Show stopper</option>
                        <option value="asap">Needed ASAP</option>
                        <option value="nice_to_have">Nice to have</option>
                    </select>
                </div>

                <div class="space-y-2">
                    <Label for="category">Category</Label>
                    <select id="category" v-model="form.category" class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm">
                        <option v-for="category in categories" :key="category" :value="category">{{ category }}</option>
                    </select>
                </div>

                <div class="space-y-2 md:col-span-2">
                    <Label for="source-url">Source page</Label>
                    <Input id="source-url" v-model="form.source_url" placeholder="Optional page URL related to this request" />
                    <p v-if="form.errors.source_url" class="text-sm text-destructive">{{ form.errors.source_url }}</p>
                </div>

                <div class="space-y-2 md:col-span-2">
                    <Label for="description">Explanation of request</Label>
                    <Textarea id="description" v-model="form.description" rows="8" placeholder="Explain what happened, what you expected, or what should be improved." />
                    <p v-if="form.errors.description" class="text-sm text-destructive">{{ form.errors.description }}</p>
                </div>

                <div class="space-y-2 md:col-span-2">
                    <Label for="screenshot">Screenshot</Label>
                    <div class="rounded-lg border border-dashed border-[#e3e3e3] p-4">
                        <Input id="screenshot" type="file" accept="image/*" @change="selectScreenshot" />
                        <p class="mt-2 flex items-center gap-2 text-xs text-[#3a3a3a]/60"><Upload class="h-3.5 w-3.5" /> Optional image, up to 5 MB.</p>
                    </div>
                    <p v-if="form.errors.screenshot" class="text-sm text-destructive">{{ form.errors.screenshot }}</p>
                </div>
            </div>

            <div class="flex flex-wrap justify-end gap-3 border-t border-[#e3e3e3] pt-5">
                <Button as-child type="button" variant="outline"><Link href="/portal/tickets">Cancel</Link></Button>
                <Button type="submit" class="bg-[#005c43] text-white hover:bg-[#004735]" :disabled="form.processing">
                    {{ form.processing ? 'Submitting…' : 'Submit request' }}
                </Button>
            </div>
        </form>
    </div>
</template>
