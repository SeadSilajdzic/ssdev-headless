<script setup lang="ts">
import { PopoverContent, PopoverRoot, PopoverTrigger } from 'reka-ui';
import { computed } from 'vue';
import type { BrowserSessionItem } from '@headless/components/settings/BrowserSessions.vue';
import { WORLD_PATH, WORLD_VIEWBOX } from '@headless/components/settings/worldMap';
import BaseBadge from '@headless/components/ui/BaseBadge.vue';
import BaseButton from '@headless/components/ui/BaseButton.vue';
import { useTrans } from '@headless/i18n';
import { deviceLabel, groupByPlace, MAP_HEIGHT, MAP_WIDTH, type MapPlace } from '@headless/support/sessionMap';

const props = defineProps<{ sessions: BrowserSessionItem[] }>();

const emit = defineEmits<{ logout: [session: BrowserSessionItem] }>();

const selected = defineModel<string | null>('selected', { default: null });

const t = useTrans();
const places = computed(() => groupByPlace(props.sessions));
const openPlace = computed(() => places.value.find((place) => place.sessions.some((session) => session.id === selected.value))?.key ?? null);

function toggle(place: MapPlace, open: boolean): void {
    selected.value = open ? place.sessions[0].id : null;
}

function position(place: MapPlace): Record<string, string> {
    return { left: `${(place.x / MAP_WIDTH) * 100}%`, top: `${(place.y / MAP_HEIGHT) * 100}%` };
}
</script>

<template>
    <div class="relative overflow-hidden rounded-xl bg-mint-50" role="group" :aria-label="t('settings.sessions.map_label')">
        <svg :viewBox="WORLD_VIEWBOX" class="block h-auto w-full" aria-hidden="true">
            <path :d="WORLD_PATH" class="fill-mint-100 stroke-mint-200" stroke-width="0.3" stroke-linejoin="round" />
        </svg>

        <PopoverRoot v-for="place in places" :key="place.key" :open="openPlace === place.key" @update:open="(open: boolean) => toggle(place, open)">
            <PopoverTrigger as-child>
                <button
                    type="button"
                    data-test="map-point"
                    :data-current="place.hasCurrent ? 'true' : 'false'"
                    :aria-label="
                        t(place.sessions.length === 1 ? 'settings.sessions.place_session' : 'settings.sessions.place_sessions', {
                            place: place.label,
                            count: place.sessions.length,
                        })
                    "
                    class="absolute grid h-6 w-6 -translate-x-1/2 -translate-y-1/2 cursor-pointer place-items-center rounded-full focus-visible:ring-2 focus-visible:ring-mint-500 focus-visible:outline-none"
                    :style="position(place)"
                >
                    <span v-if="place.hasCurrent" class="absolute inset-0.5 animate-ping rounded-full bg-mint-400 opacity-50" aria-hidden="true" />
                    <span
                        class="relative grid min-h-3.5 min-w-3.5 place-items-center rounded-full border-2 border-white px-0.5 text-[9px] leading-none font-bold text-white shadow"
                        :class="[place.hasCurrent ? 'bg-mint-600' : 'bg-ink-700', openPlace === place.key ? 'scale-125' : '']"
                        aria-hidden="true"
                    >
                        <template v-if="place.sessions.length > 1">{{ place.sessions.length }}</template>
                    </span>
                </button>
            </PopoverTrigger>

            <PopoverContent
                side="top"
                :side-offset="8"
                :collision-padding="12"
                class="z-30 w-64 rounded-xl border border-ink-100 bg-white p-3 shadow-pop"
                data-test="map-popup"
            >
                <p class="text-sm font-semibold text-ink-900">{{ place.label }}</p>
                <ul class="mt-1 divide-y divide-ink-100">
                    <li v-for="session in place.sessions" :key="session.id" class="flex items-center gap-3 py-2">
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium text-ink-900">{{ deviceLabel(session) || t('settings.sessions.unknown') }}</p>
                            <p class="text-xs text-ink-500">{{ session.last_active }}</p>
                        </div>
                        <BaseBadge v-if="session.is_current">{{ t('settings.sessions.this_device') }}</BaseBadge>
                        <BaseButton v-else variant="destructive" size="sm" data-test="map-logout" @click="emit('logout', session)">
                            {{ t('settings.sessions.log_out') }}
                        </BaseButton>
                    </li>
                </ul>
            </PopoverContent>
        </PopoverRoot>
    </div>
</template>
