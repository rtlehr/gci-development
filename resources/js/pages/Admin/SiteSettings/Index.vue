<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    Building2,
    ChevronRight,
    Home,
    PanelBottom,
    Palette,
    RotateCcw,
    Save,
    Settings2,
    SlidersHorizontal,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import PageContainer from '@/components/layout/PageContainer.vue';
import PageHeader from '@/components/layout/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

type Setting = {
    id: number;
    key: string;
    group: string;
    label: string;
    description: string | null;
    type: 'text' | 'textarea' | 'color' | 'boolean';
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

const normalizedValue = (setting: Setting): string | boolean =>
    setting.type === 'boolean'
        ? ['1', 'true', 'yes', 'on'].includes(String(setting.value ?? '').toLowerCase())
        : (setting.value ?? '');

const initialValues = Object.fromEntries(
    props.groups.flatMap((group) =>
        group.settings.map((setting) => [setting.id, normalizedValue(setting)]),
    ),
);

const form = useForm({
    settings: { ...initialValues } as Record<number, any>,
});

const activeSection = ref(props.groups[0]?.name ?? '');

const activeGroup = computed(() =>
    props.groups.find((group) => group.name === activeSection.value) ?? props.groups[0],
);

const hasChanges = computed(() =>
    props.groups.some((group) =>
        group.settings.some(
            (setting) => form.settings[setting.id] !== normalizedValue(setting),
        ),
    ),
);

function groupDescription(name: string): string {
    if (name === 'Branding') return 'Site name, logos, and shared colors.';
    if (name === 'Program') return 'Program identity and contract details.';
    if (name === 'Homepage') return 'Public landing page content.';
    if (name === 'Portal Features') return 'Optional Public and Portal capabilities.';
    if (name === 'Footer') return 'Shared footer content.';
    return 'Site configuration options.';
}

function groupLongDescription(name: string): string {
    if (name === 'Branding') return 'Names and colors shared by the header, page shell, buttons, links, cards, and footer.';
    if (name === 'Program') return 'Program identity and contract details shown on the homepage.';
    if (name === 'Homepage') return 'Labels and descriptions used by the public landing page.';
    if (name === 'Portal Features') return 'Choose which optional capabilities are available in the Public and Portal experience. Administrative management remains available.';
    if (name === 'Footer') return 'Text displayed in the shared public and portal footer.';
    return 'Manage settings for this section.';
}

function groupIcon(name: string) {
    if (name === 'Branding') return Palette;
    if (name === 'Program') return Building2;
    if (name === 'Homepage') return Home;
    if (name === 'Portal Features') return SlidersHorizontal;
    if (name === 'Footer') return PanelBottom;
    return Settings2;
}

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
            description="Manage branding, program details, optional Portal features, homepage content, and footer text."
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

        <form @submit.prevent="submit">
            <div class="grid gap-6 lg:grid-cols-[270px_minmax(0,1fr)]">
                <aside class="self-start rounded-xl border bg-background p-3 shadow-sm lg:sticky lg:top-6">
                    <div class="border-b px-2 pb-3 pt-1">
                        <p class="text-sm font-semibold">Site Setting sections</p>
                    </div>

                    <nav class="mt-2 space-y-1" aria-label="Site Setting sections">
                        <button
                            v-for="group in groups"
                            :key="group.name"
                            type="button"
                            class="flex w-full items-start gap-3 rounded-lg px-3 py-3 text-left transition"
                            :class="activeSection === group.name ? 'bg-foreground text-background' : 'hover:bg-muted'"
                            @click="activeSection = group.name"
                        >
                            <component :is="groupIcon(group.name)" class="mt-0.5 h-4 w-4 shrink-0" />

                            <span class="min-w-0 flex-1">
                                <span class="block text-sm font-medium">{{ group.name }}</span>
                                <span
                                    class="mt-0.5 block text-xs"
                                    :class="activeSection === group.name ? 'text-background/75' : 'text-muted-foreground'"
                                >
                                    {{ groupDescription(group.name) }}
                                </span>
                            </span>

                            <ChevronRight class="mt-1 h-4 w-4 shrink-0 opacity-70" />
                        </button>
                    </nav>
                </aside>

                <div class="min-w-0 space-y-6">
                    <Card v-if="activeGroup">
                        <CardHeader>
                            <div class="flex items-start gap-3">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border bg-muted/40 text-primary">
                                    <component :is="groupIcon(activeGroup.name)" class="h-5 w-5" />
                                </div>
                                <div>
                                    <CardTitle>{{ activeGroup.name }}</CardTitle>
                                    <CardDescription>{{ groupLongDescription(activeGroup.name) }}</CardDescription>
                                </div>
                            </div>
                        </CardHeader>

                        <CardContent class="grid gap-5 md:grid-cols-2">
                            <div
                                v-for="setting in activeGroup.settings"
                                :key="setting.id"
                                class="space-y-2"
                                :class="setting.type === 'textarea' ? 'md:col-span-2' : ''"
                            >
                                <Label v-if="setting.type !== 'boolean'" :for="`setting-${setting.id}`">
                                    {{ setting.label }}
                                </Label>

                                <div
                                    v-if="setting.type === 'boolean'"
                                    class="rounded-lg border bg-muted/20 p-4"
                                >
                                    <div class="flex items-start gap-3">
                                        <Checkbox
                                            :id="`setting-${setting.id}`"
                                            v-model="form.settings[setting.id]"
                                        />
                                        <div class="min-w-0 space-y-1">
                                            <Label :for="`setting-${setting.id}`" class="cursor-pointer text-sm font-semibold">
                                                {{ setting.label }}
                                            </Label>
                                            <p class="text-xs font-medium" :class="form.settings[setting.id] ? 'text-emerald-700' : 'text-muted-foreground'">
                                                {{ form.settings[setting.id] ? 'On' : 'Off' }}
                                            </p>
                                            <p class="text-xs leading-5 text-muted-foreground">
                                                {{ form.settings[setting.id] ? 'Available to Public and Portal users.' : 'Hidden from Public and Portal users.' }}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div v-else-if="setting.type === 'color'" class="flex items-center gap-3">
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
                </div>
            </div>
        </form>
    </PageContainer>
</template>
