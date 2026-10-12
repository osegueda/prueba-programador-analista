<script setup lang="ts">
import { ref, watch } from 'vue';
import { Producto, ProductHistoryResponse, HistorialItem } from '@/types/inventory';
import {
    X,
    History,
    ArrowDownToLine,
    ArrowUpFromLine,
    Package,
    Calendar,
    Layers,
    FileSpreadsheet,
    Loader2,
} from 'lucide-vue-next';

const props = defineProps<{
    show: boolean;
    producto: Producto | null;
}>();

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'open-entrada', productoId: number): void;
    (e: 'open-salida', productoId: number): void;
}>();

const loading = ref(false);
const data = ref<ProductHistoryResponse | null>(null);

const fetchHistorial = async (id: number) => {
    loading.value = true;
    try {
        const response = await fetch(`/productos/${id}/historial`);
        if (response.ok) {
            data.value = await response.json();
        }
    } catch (err) {
        console.error('Error al cargar historial del producto:', err);
    } finally {
        loading.value = false;
    }
};

watch(
    () => props.producto,
    (prod) => {
        if (prod && props.show) {
            fetchHistorial(prod.id);
        } else {
            data.value = null;
        }
    }
);

watch(
    () => props.show,
    (isOpen) => {
        if (isOpen && props.producto) {
            fetchHistorial(props.producto.id);
        }
    }
);
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6 overflow-y-auto">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs transition-opacity" @click="emit('close')"></div>

        <!-- Modal Dialog -->
        <div class="relative w-full max-w-4xl bg-white rounded-2xl shadow-2xl border border-slate-100 overflow-hidden transform transition-all z-10 max-h-[90vh] flex flex-col">
            <!-- Header Neutro -->
            <div class="px-6 py-4 border-b border-slate-200 bg-slate-50 flex items-center justify-between shrink-0">
                <div>
                    <div class="flex items-center gap-2">
                        <h3 class="text-sm font-bold text-slate-900">Historial de Movimientos (Kardex)</h3>
                        <span v-if="producto" class="px-2 py-0.5 rounded text-xs font-mono font-bold bg-slate-200 text-slate-800">
                            {{ producto.codigo }}
                        </span>
                    </div>
                    <p class="text-xs text-slate-500">
                        {{ producto?.descripcion }}
                    </p>
                </div>
                <button
                    type="button"
                    @click="emit('close')"
                    class="rounded-lg p-1 text-slate-400 hover:text-slate-700 hover:bg-slate-200 transition-colors cursor-pointer"
                >
                    <X class="w-4 h-4" />
                </button>
            </div>

            <!-- Body -->
            <div class="p-6 overflow-y-auto flex-1 space-y-4">
                <!-- Loading State -->
                <div v-if="loading" class="py-12 flex flex-col items-center justify-center text-slate-400 space-y-2">
                    <Loader2 class="w-6 h-6 animate-spin text-slate-600" />
                    <p class="text-xs">Consultando movimientos...</p>
                </div>

                <template v-else-if="data">
                    <!-- Resumen Simple en una sola línea -->
                    <div class="p-3 bg-slate-50 border border-slate-200 rounded-lg flex flex-wrap items-center justify-between gap-3 text-xs">
                        <div class="flex items-center gap-4 text-slate-700 font-mono">
                            <span>Stock Actual: <strong class="text-slate-900">{{ data.resumen.stock_actual }} uds</strong></span>
                            <span>Total Entradas: <strong class="text-slate-900">+{{ data.resumen.total_entradas }}</strong></span>
                            <span>Total Salidas: <strong class="text-slate-900">-{{ data.resumen.total_salidas }}</strong></span>
                        </div>
                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                @click="emit('open-entrada', producto!.id)"
                                class="px-2.5 py-1 text-xs font-medium rounded-md bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 transition-colors cursor-pointer"
                            >
                                + Entrada
                            </button>
                            <button
                                type="button"
                                @click="emit('open-salida', producto!.id)"
                                class="px-2.5 py-1 text-xs font-medium rounded-md bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 transition-colors cursor-pointer"
                            >
                                - Salida
                            </button>
                        </div>
                    </div>

                    <!-- Tabla de Movimientos Cronológicos -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700">
                                Registro Cronológico de Movimientos
                            </h4>
                            <span class="text-[11px] text-slate-400">Ordenado por fecha más reciente primero</span>
                        </div>

                        <div class="overflow-x-auto border border-slate-200 rounded-xl shadow-xs">
                            <table class="min-w-full divide-y divide-slate-200 text-left text-xs">
                                <thead class="bg-slate-50 text-slate-600 font-semibold">
                                    <tr>
                                        <th scope="col" class="py-3 px-3.5">Fecha & Hora</th>
                                        <th scope="col" class="py-3 px-3.5">Tipo</th>
                                        <th scope="col" class="py-3 px-3.5 text-right">Cantidad</th>
                                        <th scope="col" class="py-3 px-3.5 text-right">Saldo Tras Movimiento</th>
                                        <th scope="col" class="py-3 px-3.5">Motivo / Justificación</th>
                                        <th scope="col" class="py-3 px-3.5">Referencia</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    <tr
                                        v-for="item in data.historial"
                                        :key="item.id"
                                        class="hover:bg-slate-50/80 transition-colors"
                                    >
                                        <td class="py-2.5 px-3.5 text-slate-700 whitespace-nowrap font-mono text-[11px]">
                                            {{ item.fecha_ingreso }}
                                        </td>
                                        <td class="py-2.5 px-3.5 whitespace-nowrap">
                                            <span
                                                :class="[
                                                    'inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold',
                                                    item.tipo_movimiento === 'ENTRADA'
                                                        ? 'bg-emerald-50 text-emerald-700 border border-emerald-200'
                                                        : 'bg-rose-50 text-rose-700 border border-rose-200'
                                                ]"
                                            >
                                                <ArrowDownToLine v-if="item.tipo_movimiento === 'ENTRADA'" class="w-3 h-3 text-emerald-600" />
                                                <ArrowUpFromLine v-else class="w-3 h-3 text-rose-600" />
                                                {{ item.tipo_movimiento }}
                                            </span>
                                        </td>
                                        <td class="py-2.5 px-3.5 text-right font-mono font-bold whitespace-nowrap">
                                            <span :class="item.tipo_movimiento === 'ENTRADA' ? 'text-emerald-600' : 'text-rose-600'">
                                                {{ item.tipo_movimiento === 'ENTRADA' ? '+' : '-' }}{{ item.cantidad }}
                                            </span>
                                        </td>
                                        <td class="py-2.5 px-3.5 text-right font-mono font-bold text-slate-800 whitespace-nowrap">
                                            {{ item.saldo_resultante }} uds
                                        </td>
                                        <td class="py-2.5 px-3.5 text-slate-700 max-w-xs truncate" :title="item.motivo">
                                            {{ item.motivo }}
                                        </td>
                                        <td class="py-2.5 px-3.5 text-slate-500 font-mono text-[11px] whitespace-nowrap">
                                            {{ item.referencia || '—' }}
                                        </td>
                                    </tr>

                                    <tr v-if="data.historial.length === 0">
                                        <td colspan="6" class="py-8 text-center text-slate-400">
                                            No se han registrado movimientos para este producto aún.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Footer -->
            <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex items-center justify-end shrink-0">
                <button
                    type="button"
                    @click="emit('close')"
                    class="px-5 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-100 transition-colors cursor-pointer"
                >
                    Cerrar Historial
                </button>
            </div>
        </div>
    </div>
</template>
