<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Bell, Settings, Shield, UserRound } from 'lucide-vue-next';
import { useAuth } from '@/composables/useAuth';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

const { user, username, can } = useAuth();
</script>

<template>
    <DropdownMenu v-if="user">
        <DropdownMenuTrigger as-child>
            <Button
                variant="outline"
                size="icon"
                class="border-[#005c43]/30"
                :aria-label="`Open account menu for ${username}`"
                :title="username"
            >
                <UserRound class="h-4 w-4" />
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" class="w-56">
            <DropdownMenuLabel>
                <div class="truncate">{{ username }}</div>
                <div class="truncate text-xs font-normal text-muted-foreground">{{ user.email }}</div>
            </DropdownMenuLabel>
            <DropdownMenuSeparator />
            <DropdownMenuItem as-child>
                <Link href="/portal/alerts">
                    <Bell class="mr-2 h-4 w-4" />
                    Alerts
                </Link>
            </DropdownMenuItem>
            <DropdownMenuItem as-child>
                <Link href="/settings/profile">
                    <Settings class="mr-2 h-4 w-4" />
                    Account settings
                </Link>
            </DropdownMenuItem>
            <DropdownMenuItem v-if="can('view_admin')" as-child>
                <Link href="/admin">
                    <Shield class="mr-2 h-4 w-4" />
                    Administration
                </Link>
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
