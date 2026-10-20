import { Building2, Circle, Key, LayoutDashboard, Shield, ShieldCheck, User, UserCog, Users } from 'lucide-vue-next';
import type { Component } from 'vue';

const icons: Record<string, Component> = {
    'layout-dashboard': LayoutDashboard,
    user: User,
    shield: Shield,
    key: Key,
    users: Users,
    'shield-check': ShieldCheck,
    'building-2': Building2,
    'user-cog': UserCog,
};

export function registerNavigationIcon(name: string, icon: Component): void {
    icons[name] = icon;
}

export function resolveNavigationIcon(name: string): Component {
    return icons[name] ?? Circle;
}
