<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Palette, RotateCcw, Save, Settings2 } from 'lucide-vue-next';
import { computed } from 'vue';
import PageContainer from '@/components/layout/PageContainer.vue';
import PageHeader from '@/components/layout/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

type Setting = {
    id: number;
    key: string;
    group: string;
    label: string;
    description: string | null;
    type: 'text' | 'textarea' | 'color';
    value: string | null;
    sort_order: number;
};

type SettingGroup = {
    name: string;
    settings: Setting[];
};

const props = defineProps<{
    groups: SettingGroup[];
}>();

const initialValues = Object.fromEntries(
    props.groups.flatMap((group) =>
        group.settings.map((setting) => [setting.id, setting.value ?? '']),
    ),
);

const form = useForm({
    settings: { ...initialValues } as Record<number, string>,
});

const hasChanges = computed(() =>
    props.groups.some((group) =>
        group.settings.some(
            (setting) => form.settings[setting.id] !== (setting.value ?? ''),
        ),
    ),
);

function submit(): void {
    form.put('/admin/site-settings', {
        preserveScroll: true,
    });
}

function resetUnsaved(): void {
    form.settings = { ...initialValues };
    form.clearErrors();
}
</script>

<template>
    <Head title="Site Settings" />

    <PageContainer class="space-y-6">
        <PageHeader
            eyebrow="Configuration"
            title="Site Settings"
            description="Manage the branding, program details, homepage content, and footer text used by the Public and Portal templates."
        >
            <template #actions>
                <Button variant="outline" as-child>
                    <Link href="/admin">Back to Admin Portal</Link>
                </Button>
            </template>
        </PageHeader>

        <div class="rounded-xl border bg-muted/30 px-4 py-3 text-sm text-muted-foreground">
            Changes apply to the shared Public and Portal templates after saving. The database seeder preserves the original IRAD defaults for new installations and database rebuilds.
        </div>

        <form class="space-y-6" @submit.prevent="submit">
            <Card v-for="group in groups" :key="group.name">
                <CardHeader>
                    <div class="flex items-start gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border bg-muted/40 text-primary">
                            <Palette v-if="group.name === 'Branding'" class="h-5 w-5" />
                            <Settings2 v-else class="h-5 w-5" />
                        </div>
                        <div>
                            <CardTitle>{{ group.name }}</CardTitle>
                            <CardDescription>
                                <template v-if="group.name === 'Branding'">Names and colors shared by the header, page shell, buttons, links, cards, and footer.</template>
                                <template v-else-if="group.name === 'Program'">Program identity and contract details shown on the homepage.</template>
                                <template v-else-if="group.name === 'Homepage'">Labels and descriptions used by the public landing page.</template>
                                <template v-else>Text displayed in the shared public and portal footer.</template>
                            </CardDescription>
                        </div>
                    </div>
                </CardHeader>

                <CardContent class="grid gap-5 md:grid-cols-2">
                    <div
                        v-for="setting in group.settings"
                        :key="setting.id"
                        class="space-y-2"
                        :class="setting.type === 'textarea' ? 'md:col-span-2' : ''"
                    >
                        <Label :for="`setting-${setting.id}`">{{ setting.label }}</Label>

                        <div v-if="setting.type === 'color'" class="flex items-center gap-3">
                            <input
                                :id="`setting-color-${setting.id}`"
                                v-model="form.settings[setting.id]"
                                type="color"
                                class="h-10 w-14 cursor-pointer rounded-md border bg-background p-1"
                                :aria-label="`${setting.label} color picker`"
                            />
                            <Input
                                :id="`setting-${setting.id}`"
                                v-model="form.settings[setting.id]"
                                maxlength="7"
                                class="font-mono"
                            />
                        </div>

                        <Textarea
                            v-else-if="setting.type === 'textarea'"
                            :id="`setting-${setting.id}`"
                            v-model="form.settings[setting.id]"
                            rows="3"
                        />

                        <Input
                            v-else
                            :id="`setting-${setting.id}`"
                            v-model="form.settings[setting.id]"
                        />

                        <p v-if="setting.description" class="text-xs leading-5 text-muted-foreground">
                            {{ setting.description }}
                        </p>

                        <p v-if="form.errors[`settings.${setting.id}`]" class="text-sm text-destructive">
                            {{ form.errors[`settings.${setting.id}`] }}
                        </p>
                    </div>
                </CardContent>
            </Card>

            <div class="sticky bottom-4 flex flex-wrap items-center justify-end gap-3 rounded-xl border bg-background/95 p-4 shadow-lg backdrop-blur">
                <Button
                    type="button"
                    variant="outline"
                    :disabled="form.processing || !hasChanges"
                    @click="resetUnsaved"
                >
                    <RotateCcw class="mr-2 h-4 w-4" />
                    Reset unsaved changes
                </Button>

                <Button type="submit" :disabled="form.processing || !hasChanges">
                    <Save class="mr-2 h-4 w-4" />
                    {{ form.processing ? 'Saving...' : 'Save Site Settings' }}
                </Button>
            </div>
        </form>
    </PageContainer>
</template>
