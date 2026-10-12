<script setup lang="ts">
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import ModalMovimientoStock from '@/components/ModalMovimientoStock.vue';
import ModalEditarMovimiento from '@/components/ModalEditarMovimiento.vue';
import ModalNuevoProducto from '@/components/ModalNuevoProducto.vue';
import { InventarioProducto, Producto, TipoProducto } from '@/types/inventory';
import {
    ArrowDownToLine,
    ArrowUpFromLine,
    Search,
    Calendar,
    Pencil,
    Trash2,
    X,
    ArrowLeftRight,
} from 'lucide-vue-next';

const props = defineProps<{
    movimientos: InventarioProducto[];
    productos: Producto[];
    tipos: TipoProducto[];
    filters: {
        tipo_movimiento: string;
        id_producto: string;
        search: string;
        fecha_desde: string;
        fecha_hasta: string;
    };
}>();

// Filter states
const search = ref(props.filters.search || '');
const selectedTipo = ref(props.filters.tipo_movimiento || '');
const selectedProducto = ref(props.filters.id_producto || '');
const fechaDesde = ref(props.filters.fecha_desde || '');
const fechaHasta = ref(props.filters.fecha_hasta || '');

const applyFilters = () => {
    router.get(
        '/movimientos',
        {
            tipo_movimiento: selectedTipo.value || undefined,
            id_producto: selectedProducto.value || undefined,
            search: search.value || undefined,
            fecha_desde: fechaDesde.value || undefined,
            fecha_hasta: fechaHasta.value || undefined,
        },
        { preserveState: true, replace: true }
    );
};

const resetFilters = () => {
    search.value = '';
    selectedTipo.value = '';
    selectedProducto.value = '';
    fechaDesde.value = '';
    fechaHasta.value = '';
    applyFilters();
};

// Modals
const showModalCrear = ref(false);
const defaultTipoCrear = ref<'ENTRADA' | 'SALIDA'>('ENTRADA');

const showModalEditar = ref(false);
const movimientoParaEditar = ref<InventarioProducto | null>(null);

const showModalNuevoProd = ref(false);

const openCrearEntrada = () => {
    defaultTipoCrear.value = 'ENTRADA';
    showModalCrear.value = true;
};

const openCrearSalida = () => {
    defaultTipoCrear.value = 'SALIDA';
    showModalCrear.value = true;
};

const openEditar = (mov: InventarioProducto) => {
    movimientoParaEditar.value = mov;
    showModalEditar.value = true;
};

const eliminarMovimiento = (mov: InventarioProducto) => {
    if (confirm(`¿Estás seguro de eliminar el movimiento #${mov.id} (${mov.tipo_movimiento} de ${mov.cantidad} unidades)? El stock del producto será recalculado.`)) {
        router.delete(`/movimientos/${mov.id}`);
    }
};

const formatFecha = (isoString?: string) => {
    if (!isoString) return '—';
    const d = new Date(isoString);
    if (isNaN(d.getTime())) return isoString;
    return d.toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};
</script>

<template>
    <AppLayout
        @open-nuevo-producto="showModalNuevoProd = true"
        @open-entrada="openCrearEntrada()"
        @open-salida="openCrearSalida()"
    >
        <Head title="Movimientos de Inventario" />

        <div class="space-y-5">
            <!-- Encabezado simple -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between pb-4 border-b border-slate-200 gap-3">
                <div>
                    <h1 class="text-xl font-bold text-slate-900 tracking-tight">
                        Movimientos de Inventario
                    </h1>
                    <p class="text-xs text-slate-500">
                        Registro y trazabilidad de entradas y salidas de bodega
                    </p>
                </div>

                <!-- Botones de Acción -->
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        @click="openCrearEntrada()"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-medium rounded-lg bg-slate-900 hover:bg-slate-800 text-white transition-colors cursor-pointer"
                    >
                        <ArrowDownToLine class="w-4 h-4" />
                        Registrar Entrada (+)
                    </button>
                    <button
                        type="button"
                        @click="openCrearSalida()"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-medium rounded-lg bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 transition-colors cursor-pointer"
                    >
                        <ArrowUpFromLine class="w-4 h-4" />
                        Registrar Salida (-)
                    </button>
                </div>
            </div>

            <!-- Toolbar con Filtros -->
            <div class="bg-white p-3.5 rounded-xl border border-slate-200 space-y-3">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-2.5">
                    <!-- Búsqueda -->
                    <div class="lg:col-span-2 relative">
                        <Search class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
                        <input
                            v-model="search"
                            @keyup.enter="applyFilters"
                            type="text"
                            placeholder="Buscar por motivo, referencia o producto..."
                            class="w-full pl-9 pr-3 py-1.5 text-xs rounded-lg border border-slate-300 bg-white text-slate-800 focus:border-slate-500 focus:outline-hidden"
                        />
                    </div>

                    <!-- Tipo -->
                    <div>
                        <select
                            v-model="selectedTipo"
                            @change="applyFilters"
                            class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-300 bg-white text-slate-700 cursor-pointer focus:outline-hidden"
                        >
                            <option value="">Todos los Tipos</option>
                            <option value="ENTRADA">Solo Entradas</option>
                            <option value="SALIDA">Solo Salidas</option>
                        </select>
                    </div>

                    <!-- Producto -->
                    <div>
                        <select
                            v-model="selectedProducto"
                            @change="applyFilters"
                            class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-300 bg-white text-slate-700 cursor-pointer focus:outline-hidden"
                        >
                            <option value="">Todos los Productos</option>
                            <option v-for="prod in productos" :key="prod.id" :value="prod.id">
                                [{{ prod.codigo }}] {{ prod.descripcion }}
                            </option>
                        </select>
                    </div>

                    <!-- Botón Aplicar -->
                    <div>
                        <button
                            type="button"
                            @click="applyFilters"
                            class="w-full py-1.5 px-3 text-xs font-medium text-white bg-slate-800 hover:bg-slate-900 rounded-lg transition-colors cursor-pointer"
                        >
                            Filtrar
                        </button>
                    </div>
                </div>

                <!-- Rango de Fechas -->
                <div class="pt-2.5 border-t border-slate-100 flex flex-wrap items-center justify-between gap-2 text-xs text-slate-600">
                    <div class="flex flex-wrap items-center gap-3">
                        <span class="text-slate-500 font-medium flex items-center gap-1">
                            <Calendar class="w-3.5 h-3.5" />
                            Fechas:
                        </span>
                        <div class="flex items-center gap-1.5">
                            <label class="text-slate-400">Desde:</label>
                            <input
                                v-model="fechaDesde"
                                @change="applyFilters"
                                type="date"
                                class="px-2 py-1 rounded border border-slate-200 text-xs text-slate-700 focus:outline-hidden"
                            />
                        </div>
                        <div class="flex items-center gap-1.5">
                            <label class="text-slate-400">Hasta:</label>
                            <input
                                v-model="fechaHasta"
                                @change="applyFilters"
                                type="date"
                                class="px-2 py-1 rounded border border-slate-200 text-xs text-slate-700 focus:outline-hidden"
                            />
                        </div>
                    </div>

                    <button
                        v-if="selectedTipo || selectedProducto || search || fechaDesde || fechaHasta"
                        type="button"
                        @click="resetFilters"
                        class="text-xs text-slate-600 hover:text-slate-900 font-medium cursor-pointer flex items-center gap-1"
                    >
                        <X class="w-3.5 h-3.5" />
                        Limpiar filtros
                    </button>
                </div>
            </div>

            <!-- Tabla de Movimientos -->
            <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-left text-xs">
                        <thead class="bg-slate-50 text-slate-700 font-semibold">
                            <tr>
                                <th scope="col" class="py-3 px-4">ID</th>
                                <th scope="col" class="py-3 px-4">Fecha & Hora</th>
                                <th scope="col" class="py-3 px-4">Producto</th>
                                <th scope="col" class="py-3 px-4 text-center">Tipo</th>
                                <th scope="col" class="py-3 px-4 text-center">Cantidad</th>
                                <th scope="col" class="py-3 px-4">Motivo / Observación</th>
                                <th scope="col" class="py-3 px-4">Referencia</th>
                                <th scope="col" class="py-3 px-4 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            <tr
                                v-for="mov in movimientos"
                                :key="mov.id"
                                class="hover:bg-slate-50 transition-colors"
                            >
                                <!-- ID -->
                                <td class="py-3 px-4 font-mono text-slate-500 whitespace-nowrap">
                                    #{{ mov.id }}
                                </td>

                                <!-- Fecha -->
                                <td class="py-3 px-4 text-slate-700 whitespace-nowrap font-mono text-[11px]">
                                    {{ formatFecha(mov.fecha_ingreso) }}
                                </td>

                                <!-- Producto -->
                                <td class="py-3 px-4">
                                    <div class="font-medium text-slate-900">
                                        <span class="font-mono text-slate-600 font-semibold mr-1">[{{ mov.producto?.codigo }}]</span>
                                        {{ mov.producto?.descripcion }}
                                    </div>
                                    <div class="text-[11px] text-slate-400">
                                        {{ mov.producto?.tipo?.nombre }} (Stock: {{ mov.producto?.stock_actual }})
                                    </div>
                                </td>

                                <!-- Tipo -->
                                <td class="py-3 px-4 text-center whitespace-nowrap">
                                    <span
                                        :class="[
                                            'px-2 py-0.5 rounded text-[11px] font-medium border',
                                            mov.tipo_movimiento === 'ENTRADA'
                                                ? 'bg-slate-50 text-slate-800 border-slate-300'
                                                : 'bg-slate-100 text-slate-800 border-slate-300'
                                        ]"
                                    >
                                        {{ mov.tipo_movimiento }}
                                    </span>
                                </td>

                                <!-- Cantidad -->
                                <td class="py-3 px-4 text-center font-mono font-bold whitespace-nowrap">
                                    <span :class="mov.tipo_movimiento === 'ENTRADA' ? 'text-slate-900' : 'text-slate-700'">
                                        {{ mov.tipo_movimiento === 'ENTRADA' ? '+' : '-' }}{{ mov.cantidad }}
                                    </span>
                                </td>

                                <!-- Motivo -->
                                <td class="py-3 px-4 text-slate-700">
                                    {{ mov.motivo }}
                                </td>

                                <!-- Referencia -->
                                <td class="py-3 px-4 font-mono text-[11px] text-slate-500 whitespace-nowrap">
                                    {{ mov.referencia || '—' }}
                                </td>

                                <!-- Acciones -->
                                <td class="py-3 px-4 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center space-x-1">
                                        <button
                                            type="button"
                                            @click="openEditar(mov)"
                                            class="p-1.5 text-slate-500 hover:text-slate-900 hover:bg-slate-100 rounded-md transition-colors cursor-pointer"
                                            title="Editar Movimiento"
                                        >
                                            <Pencil class="w-4 h-4" />
                                        </button>

                                        <button
                                            type="button"
                                            @click="eliminarMovimiento(mov)"
                                            class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-md transition-colors cursor-pointer"
                                            title="Eliminar Movimiento"
                                        >
                                            <Trash2 class="w-4 h-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="movimientos.length === 0">
                                <td colspan="8" class="py-10 text-center text-slate-400">
                                    <ArrowLeftRight class="w-8 h-8 text-slate-300 mx-auto mb-2" />
                                    <p class="text-xs">No se encontraron movimientos registrados.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modales -->
        <ModalMovimientoStock
            :show="showModalCrear"
            :productos="productos"
            :default-tipo="defaultTipoCrear"
            @close="showModalCrear = false"
        />

        <ModalEditarMovimiento
            :show="showModalEditar"
            :movimiento="movimientoParaEditar"
            :productos="productos"
            @close="showModalEditar = false"
        />

        <ModalNuevoProducto
            :show="showModalNuevoProd"
            :tipos="tipos"
            @close="showModalNuevoProd = false"
        />
    </AppLayout>
</template>
