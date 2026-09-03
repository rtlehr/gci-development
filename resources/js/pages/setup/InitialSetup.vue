<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ShieldCheck } from 'lucide-vue-next';
import { ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import PageContainer from '@/components/layout/PageContainer.vue';
import PageHeader from '@/components/layout/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';

const props = defineProps<{ ownerPersonCode: string }>();
const processing = ref(false);
const completeDialogOpen = ref(false);

function completeSetup(): void {
    processing.value = true;
    router.post('/setup/complete', {}, {
        onFinish: () => {
            processing.value = false;
            completeDialogOpen.value = false;
        },
    });
}
</script>

<template>
    <AppLayout>
        <Head title="Initial Setup" />
        <PageContainer>
            <PageHeader
                title="Initial Setup"
                description="Bootstrap Owner access is active only until you complete installation."
            />

            <div class="grid gap-6 lg:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2"><ShieldCheck class="h-5 w-5" /> Bootstrap Owner</CardTitle>
                        <CardDescription>Current designated Owner person code: {{ props.ownerPersonCode }}</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4 text-sm">
                        <p>Use normal Owner access to configure Site Settings, users, roles, permissions, workflows, and other installation-specific options.</p>
                        <div class="flex flex-wrap gap-3">
                            <Button as-child><Link href="/admin">Open Administration</Link></Button>
                            <Button variant="outline" as-child><Link href="/admin/site-settings">Site Settings</Link></Button>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Complete installation</CardTitle>
                        <CardDescription>Do this only after enterprise identity configuration is ready.</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4 text-sm">
                        <p>Completing setup permanently disables bootstrap password authentication. Future protected requests require the configured identity provider.</p>
                        <Button variant="destructive" :disabled="processing" @click="completeDialogOpen = true">
                            Complete Initial Setup
                        </Button>
                    </CardContent>
                </Card>
            </div>
            <AlertDialog :open="completeDialogOpen" @update:open="completeDialogOpen = $event">
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Complete Initial Setup?</AlertDialogTitle>
                        <AlertDialogDescription>
                            Bootstrap password authentication will be permanently disabled. Future protected access will require the configured enterprise identity provider.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel :disabled="processing">Cancel</AlertDialogCancel>
                        <AlertDialogAction
                            class="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                            :disabled="processing"
                            @click="completeSetup"
                        >
                            {{ processing ? 'Completing…' : 'Complete Setup' }}
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </PageContainer>
    </AppLayout>
</template>
