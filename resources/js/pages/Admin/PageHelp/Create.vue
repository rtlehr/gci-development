<template>
    <div class="max-w-5xl space-y-6 p-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold">Create Help Page</h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    Add help content for a page.
                </p>
            </div>

            <Button as-child variant="outline"><Link href="/admin/page-help">Back to List</Link></Button>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <Card class="rounded-xl">
                <CardHeader>
                    <CardTitle>Help Page Details</CardTitle>
                    <CardDescription>
                        Enter the help key, title, and HTML content.
                    </CardDescription>
                </CardHeader>

                <CardContent class="space-y-6">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="help_key">Help Key</Label>
                            <Input id="help_key" v-model="form.help_key" />
                            <p class="text-xs text-muted-foreground">
                                Example: people.create
                            </p>
                            <p v-if="form.errors.help_key" class="text-sm text-red-500">
                                {{ form.errors.help_key }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="title">Title</Label>
                            <Input id="title" v-model="form.title" />
                            <p v-if="form.errors.title" class="text-sm text-red-500">
                                {{ form.errors.title }}
                            </p>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label for="content_html">HTML Content</Label>
                        <Textarea
                            id="content_html"
                            v-model="form.content_html"
                            rows="16"
                        />
                        <p class="text-xs text-muted-foreground">
                            You can enter HTML such as headings, paragraphs, lists, and links.
                        </p>
                        <p v-if="form.errors.content_html" class="text-sm text-red-500">
                            {{ form.errors.content_html }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="is_active">Status</Label>
                        <select
                            id="is_active"
                            v-model="form.is_active"
                            class="flex h-10 w-full rounded-md border bg-background px-3 py-2 text-sm"
                        >
                            <option :value="true">Active</option>
                            <option :value="false">Inactive</option>
                        </select>
                        <p v-if="form.errors.is_active" class="text-sm text-red-500">
                            {{ form.errors.is_active }}
                        </p>
                    </div>
                </CardContent>
            </Card>

            <div class="flex gap-3">
                <Button type="submit" :disabled="form.processing">
                    {{ form.processing ? 'Saving...' : 'Create Help Page' }}
                </Button>

                <Button as-child variant="outline"><Link href="/admin/page-help">Cancel</Link></Button>
            </div>
        </form>
    </div>
</template>

<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { usePage } from '@inertiajs/vue3'

// Provides access to current Inertia page props,
// including query data when available from Ziggy.
const page = usePage()

// Pre-fills the help key from the current URL query string.
// First checks Ziggy/Inertia props, then falls back to the browser URL.
const initialHelpKey =
    page.props?.ziggy?.query?.help_key ||
    new URLSearchParams(window.location.search).get('help_key') ||
    ''

// Reactive Inertia form state.
// Tracks field values, validation errors, and processing status.
const form = useForm({
    help_key: initialHelpKey,
    title: '',
    content_html: '',
    is_active: true,
})

/**
 * Submits the new help page record
 * to the backend create endpoint.
 */
function submit() {
    form.post('/admin/page-help')
}
</script>