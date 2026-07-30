<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { History, Search, UserRoundCog } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import PageContainer from '@/components/layout/PageContainer.vue';
import PageHeader from '@/components/layout/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';

type UserOption = {
    id: number;
    name: string;
    email: string | null;
    person_code: string | null;
    roles: string[];
    role_names: string[];
};

const props = defineProps<{
    users: UserOption[];
    logs: any;
    activeSession: boolean;
}>();

const search = ref('');
const selectedUser = ref<UserOption | null>(null);
const reason = ref('');

const filteredUsers = computed(() => {
    const value = search.value.trim().toLowerCase();

    if (!value) return props.users;

    return props.users.filter((user) =>
        [
            user.name,
            user.email ?? '',
            user.person_code ?? '',
            ...user.roles,
        ].join(' ').toLowerCase().includes(value),
    );
});

function begin(): void {
    if (!selectedUser.value) return;

    router.post(
        `/admin/impersonation/${selectedUser.value.id}`,
        { reason: reason.value },
    );
}
</script>

<template>
    <Head title="Impersonation" />

    <PageContainer class="space-y-8">
        <PageHeader
            eyebrow="Security and Access"
            title="Impersonation"
            description="View IRAD as another user for support and troubleshooting. Every session is recorded."
        />

        <section class="rounded-xl border bg-card p-5">
            <div class="flex items-start gap-3">
                <UserRoundCog class="mt-0.5 h-5 w-5 text-primary" />
                <div>
                    <h2 class="font-semibold">Start an impersonation session</h2>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Developers cannot impersonate Owners, Admins, or other Developers.
                        Nested sessions are not allowed.
                    </p>
                </div>
            </div>

            <div class="mt-5 grid gap-5 lg:grid-cols-[1fr_1fr]">
                <div class="space-y-3">
                    <Input v-model="search" placeholder="Search users, person code, email, or role..." />

                    <div class="max-h-96 overflow-y-auto rounded-lg border">
                        <button
                            v-for="user in filteredUsers"
                            :key="user.id"
                            type="button"
                            class="block w-full border-b px-4 py-3 text-left last:border-b-0 hover:bg-muted/40"
                            :class="selectedUser?.id === user.id ? 'bg-muted/60' : ''"
                            @click="selectedUser = user"
                        >
                            <div class="font-medium">{{ user.name }}</div>
                            <div class="mt-1 text-xs text-muted-foreground">
                                {{ user.person_code || 'No person code' }}
                                <span v-if="user.email"> · {{ user.email }}</span>
                            </div>
                            <div class="mt-1 text-xs text-muted-foreground">
                                {{ user.roles.join(', ') || 'No roles' }}
                            </div>
                        </button>
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="rounded-lg border bg-muted/20 p-4">
                        <div class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                            Selected user
                        </div>
                        <div class="mt-2 font-semibold">
                            {{ selectedUser?.name ?? 'Select a user' }}
                        </div>
                        <div v-if="selectedUser" class="mt-1 text-sm text-muted-foreground">
                            {{ selectedUser.roles.join(', ') || 'No roles' }}
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium" for="impersonation-reason">
                            Reason
                        </label>
                        <Textarea
                            id="impersonation-reason"
                            v-model="reason"
                            rows="5"
                            placeholder="Optional support ticket, troubleshooting purpose, or other reason..."
                        />
                    </div>

                    <Button
                        type="button"
                        :disabled="!selectedUser || activeSession"
                        @click="begin"
                    >
                        Begin Impersonation
                    </Button>
                </div>
            </div>
        </section>

        <section class="space-y-4">
            <div class="flex items-center gap-3">
                <History class="h-5 w-5 text-primary" />
                <div>
                    <h2 class="font-semibold">Impersonation log</h2>
                    <p class="text-sm text-muted-foreground">
                        Append-only audit history. Records are not editable or deletable.
                    </p>
                </div>
            </div>

            <div class="overflow-x-auto rounded-xl border bg-card">
                <table class="w-full min-w-[900px] text-sm">
                    <thead class="bg-muted/40 text-left">
                        <tr>
                            <th class="p-3">Started</th>
                            <th class="p-3">Impersonator</th>
                            <th class="p-3">Impersonated User</th>
                            <th class="p-3">Ended</th>
                            <th class="p-3">IP Address</th>
                            <th class="p-3">Reason</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="log in logs.data"
                            :key="log.id"
                            class="border-t align-top"
                        >
                            <td class="p-3">{{ new Date(log.started_at).toLocaleString() }}</td>
                            <td class="p-3">{{ log.impersonator?.name || log.impersonator?.email }}</td>
                            <td class="p-3">{{ log.impersonated_user?.name || log.impersonated_user?.email }}</td>
                            <td class="p-3">
                                {{ log.ended_at ? new Date(log.ended_at).toLocaleString() : 'Active' }}
                            </td>
                            <td class="p-3">{{ log.ip_address || '—' }}</td>
                            <td class="max-w-md whitespace-pre-wrap p-3">{{ log.reason || '—' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </PageContainer>
</template>
