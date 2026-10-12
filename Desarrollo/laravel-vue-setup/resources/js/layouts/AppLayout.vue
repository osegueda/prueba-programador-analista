<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import {
    Boxes,
    ArrowDownToLine,
    ArrowUpFromLine,
    PackagePlus,
    History,
    CheckCircle2,
    AlertCircle,
    X,
    LayoutDashboard,
    ArrowLeftRight,
} from 'lucide-vue-next';

defineProps<{
    title?: string;
}>();

const emit = defineEmits<{
    (e: 'open-nuevo-producto'): void;
    (e: 'open-entrada'): void;
    (e: 'open-salida'): void;
}>();

const page = usePage();
const flashSuccess = computed(() => (page.props.flash as any)?.success);
const flashError = computed(() => (page.props.flash as any)?.error);

const showFlashSuccess = ref(false);
const showFlashError = ref(false);

watch(
    flashSuccess,
    (val) => {
        if (val) {
            showFlashSuccess.value = true;
            setTimeout(() => {
                showFlashSuccess.value = false;
            }, 5000);
        }
    },
    { immediate: true }
);

watch(
    flashError,
    (val) => {
        if (val) {
            showFlashError.value = true;
        }
    },
    { immediate: true }
);

const isCurrentUrl = (path: string) => {
    return page.url === path || (path === '/inventario' && page.url === '/');
};
</script>

<template>
    <div class="min-h-screen bg-slate-50 text-slate-900 antialiased flex flex-col selection:bg-indigo-500 selection:text-white">
        <!-- Top Navbar -->
        <header class="sticky top-0 z-40 bg-white/90 backdrop-blur-md border-b border-slate-200/80 shadow-xs">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <!-- Brand / Title (Sin logo) -->
                    <div class="flex items-center space-x-3">
                        <Link href="/inventario" class="flex flex-col group">
                            <span class="text-base font-bold tracking-tight text-slate-900">
                                Sistema de Inventario
                            </span>
                            <span class="text-xs text-slate-500 hidden sm:block">Control de Entradas y Salidas</span>
                        </Link>

                        <!-- Nav tabs -->
                        <nav class="hidden md:flex items-center space-x-1 ml-6 pl-6 border-l border-slate-200">
                            <Link
                                href="/inventario"
                                :class="[
                                    'px-3 py-1.5 rounded-md text-sm font-medium transition-colors',
                                    isCurrentUrl('/inventario')
                                        ? 'bg-slate-100 text-slate-900 font-semibold'
                                        : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50'
                                ]"
                            >
                                Inventario & Existencias
                            </Link>

                            <Link
                                href="/movimientos"
                                :class="[
                                    'px-3 py-1.5 rounded-md text-sm font-medium transition-colors',
                                    isCurrentUrl('/movimientos')
                                        ? 'bg-slate-100 text-slate-900 font-semibold'
                                        : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50'
                                ]"
                            >
                                Movimientos (Entradas / Salidas)
                            </Link>
                        </nav>
                    </div>

                    <!-- Action buttons -->
                    <div class="flex items-center space-x-2">
                        <button
                            type="button"
                            @click="$emit('open-nuevo-producto')"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs sm:text-sm font-medium rounded-lg bg-slate-900 text-white hover:bg-slate-800 active:scale-95 transition-all cursor-pointer"
                        >
                            <PackagePlus class="w-4 h-4" />
                            <span class="hidden sm:inline">Nuevo</span> Producto
                        </button>

                        <button
                            type="button"
                            @click="$emit('open-entrada')"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs sm:text-sm font-medium rounded-lg bg-white text-slate-700 border border-slate-300 hover:bg-slate-50 active:scale-95 transition-all cursor-pointer"
                            title="Registrar Entrada a Stock"
                        >
                            <ArrowDownToLine class="w-4 h-4 text-slate-600" />
                            <span class="hidden md:inline">Entrada</span>
                        </button>

                        <button
                            type="button"
                            @click="$emit('open-salida')"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs sm:text-sm font-medium rounded-lg bg-white text-slate-700 border border-slate-300 hover:bg-slate-50 active:scale-95 transition-all cursor-pointer"
                            title="Registrar Salida de Stock"
                        >
                            <ArrowUpFromLine class="w-4 h-4 text-slate-600" />
                            <span class="hidden md:inline">Salida</span>
                        </button>
                    </div>
                </div>

                <!-- Mobile navigation sub-bar -->
                <div class="flex md:hidden items-center justify-around py-2 border-t border-slate-100 text-xs font-medium">
                    <Link
                        href="/inventario"
                        :class="[
                            'px-3 py-1.5 rounded-md',
                            isCurrentUrl('/inventario') ? 'text-slate-900 bg-slate-100 font-semibold' : 'text-slate-600'
                        ]"
                    >
                        Inventario
                    </Link>
                    <Link
                        href="/movimientos"
                        :class="[
                            'px-3 py-1.5 rounded-md',
                            isCurrentUrl('/movimientos') ? 'text-slate-900 bg-slate-100 font-semibold' : 'text-slate-600'
                        ]"
                    >
                        Movimientos
                    </Link>
                </div>
            </div>
        </header>

        <!-- Flash messages notification center -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full pt-4">
            <!-- Success Flash -->
            <transition
                enter-active-class="transform ease-out duration-300 transition"
                enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
                enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
                leave-active-class="transition ease-in duration-100"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="showFlashSuccess && flashSuccess"
                    class="mb-4 rounded-xl bg-emerald-50 border border-emerald-200 p-4 shadow-sm flex items-start justify-between"
                >
                    <div class="flex items-center space-x-3">
                        <CheckCircle2 class="w-5 h-5 text-emerald-600 shrink-0" />
                        <p class="text-sm font-medium text-emerald-800">{{ flashSuccess }}</p>
                    </div>
                    <button
                        @click="showFlashSuccess = false"
                        class="text-emerald-500 hover:text-emerald-700 cursor-pointer p-0.5 rounded-md hover:bg-emerald-100 transition-colors"
                    >
                        <X class="w-4 h-4" />
                    </button>
                </div>
            </transition>

            <!-- Error Flash -->
            <transition
                enter-active-class="transform ease-out duration-300 transition"
                enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
                enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
                leave-active-class="transition ease-in duration-100"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="showFlashError && flashError"
                    class="mb-4 rounded-xl bg-rose-50 border border-rose-200 p-4 shadow-sm flex items-start justify-between"
                >
                    <div class="flex items-center space-x-3">
                        <AlertCircle class="w-5 h-5 text-rose-600 shrink-0" />
                        <p class="text-sm font-medium text-rose-800">{{ flashError }}</p>
                    </div>
                    <button
                        @click="showFlashError = false"
                        class="text-rose-500 hover:text-rose-700 cursor-pointer p-0.5 rounded-md hover:bg-rose-100 transition-colors"
                    >
                        <X class="w-4 h-4" />
                    </button>
                </div>
            </transition>
        </div>

        <!-- Main Content Area -->
        <main class="flex-1 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full py-6">
            <slot />
        </main>

        <!-- Footer -->
        <footer class="bg-white border-t border-slate-200 mt-auto py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between text-xs text-slate-500 gap-2">
                <div class="flex items-center space-x-2">
                    <span class="font-semibold text-slate-700">Sistema de Inventario MVP</span>
                    <span>•</span>
                    <span>Laravel + Vue 3 + Inertia + MySQL</span>
                </div>
                <div>
                    <span>Prueba Programador Analista - Moisés Flores Osegueda</span>
                </div>
            </div>
        </footer>
    </div>
</template>
