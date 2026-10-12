<script setup lang="ts">
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import ModalNuevoProducto from '@/components/ModalNuevoProducto.vue';
import ModalMovimientoStock from '@/components/ModalMovimientoStock.vue';
import ModalHistorialProducto from '@/components/ModalHistorialProducto.vue';
import ModalEditarProducto from '@/components/ModalEditarProducto.vue';
import { Producto, TipoProducto } from '@/types/inventory';
import {
    Search,
    Plus,
    ArrowDownToLine,
    ArrowUpFromLine,
    History,
    Pencil,
    Trash2,
    X,
    Package,
} from 'lucide-vue-next';

const props = defineProps<{
    productos: Producto[];
    tipos: TipoProducto[];
    filters: {
        search: string;
        id_tipo_producto: string;
        estado: string;
    };
}>();

// Filter states
const search = ref(props.filters.search || '');
const selectedTipo = ref(props.filters.id_tipo_producto || '');
const selectedEstado = ref(props.filters.estado || 'todos');

const applyFilters = () => {
    router.get(
        '/inventario',
        {
            search: search.value || undefined,
            id_tipo_producto: selectedTipo.value || undefined,
            estado: selectedEstado.value !== 'todos' ? selectedEstado.value : undefined,
        },
        { preserveState: true, replace: true }
    );
};

const resetFilters = () => {
    search.value = '';
    selectedTipo.value = '';
    selectedEstado.value = 'todos';
    applyFilters();
};

// Modal controls
const showModalNuevo = ref(false);
const showModalMovimiento = ref(false);
const defaultTipoMovimiento = ref<'ENTRADA' | 'SALIDA'>('ENTRADA');
const selectedProdIdForMovimiento = ref<number | null>(null);

const showModalHistorial = ref(false);
const productoParaHistorial = ref<Producto | null>(null);

const showModalEditar = ref(false);
const productoParaEditar = ref<Producto | null>(null);

// Quick action triggers
const openNuevaEntrada = (prodId?: number) => {
    defaultTipoMovimiento.value = 'ENTRADA';
    selectedProdIdForMovimiento.value = prodId || null;
    showModalMovimiento.value = true;
};

const openNuevaSalida = (prodId?: number) => {
    defaultTipoMovimiento.value = 'SALIDA';
    selectedProdIdForMovimiento.value = prodId || null;
    showModalMovimiento.value = true;
};

const openHistorial = (prod: Producto) => {
    productoParaHistorial.value = prod;
    showModalHistorial.value = true;
};

const openEditar = (prod: Producto) => {
    productoParaEditar.value = prod;
    showModalEditar.value = true;
};

const eliminarProducto = (prod: Producto) => {
    if (confirm(`¿Estás seguro de eliminar el producto "${prod.descripcion}"? Se eliminarán también sus registros de movimiento asociados.`)) {
        router.delete(`/productos/${prod.id}`);
    }
};
</script>

<template>
    <AppLayout
        @open-nuevo-producto="showModalNuevo = true"
        @open-entrada="openNuevaEntrada()"
        @open-salida="openNuevaSalida()"
    >
        <Head title="Inventario de Productos" />

        <div class="space-y-5">
            <!-- Encabezado simple -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between pb-4 border-b border-slate-200 gap-3">
                <div>
                    <h1 class="text-xl font-bold text-slate-900 tracking-tight">
                        Inventario de Productos
                    </h1>
                    <p class="text-xs text-slate-500">
                        Consulta de existencias actuales y gestión de catálogo
                    </p>
                </div>

                <!-- Botones de Acción -->
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        @click="showModalNuevo = true"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-medium rounded-lg bg-slate-900 hover:bg-slate-800 text-white transition-colors cursor-pointer"
                    >
                        <Plus class="w-4 h-4" />
                        Nuevo Producto
                    </button>
                    <button
                        type="button"
                        @click="openNuevaEntrada()"
                        class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-medium rounded-lg bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 transition-colors cursor-pointer"
                    >
                        <ArrowDownToLine class="w-4 h-4 text-slate-600" />
                        Entrada
                    </button>
                    <button
                        type="button"
                        @click="openNuevaSalida()"
                        class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-medium rounded-lg bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 transition-colors cursor-pointer"
                    >
                        <ArrowUpFromLine class="w-4 h-4 text-slate-600" />
                        Salida
                    </button>
                </div>
            </div>

            <!-- Toolbar de Filtros -->
            <div class="bg-white p-3.5 rounded-xl border border-slate-200 space-y-2 sm:space-y-0 sm:flex sm:items-center sm:justify-between sm:gap-3">
                <!-- Búsqueda -->
                <div class="relative flex-1">
                    <Search class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
                    <input
                        v-model="search"
                        @keyup.enter="applyFilters"
                        type="text"
                        placeholder="Buscar por código o descripción..."
                        class="w-full pl-9 pr-3 py-1.5 text-xs rounded-lg border border-slate-300 bg-white text-slate-800 focus:border-slate-500 focus:outline-hidden"
                    />
                </div>

                <!-- Filtros -->
                <div class="flex flex-wrap items-center gap-2">
                    <select
                        v-model="selectedTipo"
                        @change="applyFilters"
                        class="px-2.5 py-1.5 text-xs rounded-lg border border-slate-300 bg-white text-slate-700 cursor-pointer focus:outline-hidden"
                    >
                        <option value="">Todas las Categorías</option>
                        <option v-for="tipo in tipos" :key="tipo.id" :value="tipo.id">
                            {{ tipo.nombre }}
                        </option>
                    </select>

                    <select
                        v-model="selectedEstado"
                        @change="applyFilters"
                        class="px-2.5 py-1.5 text-xs rounded-lg border border-slate-300 bg-white text-slate-700 cursor-pointer focus:outline-hidden"
                    >
                        <option value="todos">Todos los Estados</option>
                        <option value="disponible">En Existencia</option>
                        <option value="bajo">Stock Bajo</option>
                        <option value="agotado">Agotado</option>
                    </select>

                    <button
                        type="button"
                        @click="applyFilters"
                        class="px-3 py-1.5 text-xs font-medium text-white bg-slate-800 hover:bg-slate-900 rounded-lg transition-colors cursor-pointer"
                    >
                        Filtrar
                    </button>

                    <button
                        v-if="search || selectedTipo || selectedEstado !== 'todos'"
                        type="button"
                        @click="resetFilters"
                        class="p-1.5 text-xs text-slate-500 hover:text-slate-800 rounded-lg transition-colors cursor-pointer"
                        title="Limpiar filtros"
                    >
                        <X class="w-4 h-4" />
                    </button>
                </div>
            </div>

            <!-- Tabla de Existencias -->
            <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-left text-xs">
                        <thead class="bg-slate-50 text-slate-700 font-semibold">
                            <tr>
                                <th scope="col" class="py-3 px-4">Código</th>
                                <th scope="col" class="py-3 px-4">Descripción del Producto</th>
                                <th scope="col" class="py-3 px-4">Categoría</th>
                                <th scope="col" class="py-3 px-4 text-center">Stock Mínimo</th>
                                <th scope="col" class="py-3 px-4 text-center">Existencias</th>
                                <th scope="col" class="py-3 px-4 text-center">Estado</th>
                                <th scope="col" class="py-3 px-4 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            <tr
                                v-for="prod in productos"
                                :key="prod.id"
                                class="hover:bg-slate-50 transition-colors"
                            >
                                <!-- Código -->
                                <td class="py-3 px-4 font-mono font-medium text-slate-700 whitespace-nowrap">
                                    {{ prod.codigo }}
                                </td>

                                <!-- Descripción -->
                                <td class="py-3 px-4 font-medium text-slate-900">
                                    {{ prod.descripcion }}
                                </td>

                                <!-- Categoría -->
                                <td class="py-3 px-4 whitespace-nowrap">
                                    <span class="px-2 py-0.5 rounded-md text-[11px] font-medium bg-slate-100 text-slate-700 border border-slate-200">
                                        {{ prod.tipo?.nombre }}
                                    </span>
                                </td>

                                <!-- Stock Mínimo -->
                                <td class="py-3 px-4 text-center font-mono text-slate-600 whitespace-nowrap">
                                    {{ prod.stock_minimo }} uds
                                </td>

                                <!-- Existencias Actuales -->
                                <td class="py-3 px-4 text-center font-mono font-bold text-slate-900 whitespace-nowrap">
                                    {{ prod.stock_actual }}
                                </td>

                                <!-- Estado -->
                                <td class="py-3 px-4 text-center whitespace-nowrap">
                                    <span
                                        v-if="prod.stock_actual === 0"
                                        class="px-2 py-0.5 rounded text-[11px] font-medium text-rose-700 bg-rose-50 border border-rose-200"
                                    >
                                        Agotado
                                    </span>
                                    <span
                                        v-else-if="prod.stock_actual <= prod.stock_minimo"
                                        class="px-2 py-0.5 rounded text-[11px] font-medium text-amber-700 bg-amber-50 border border-amber-200"
                                    >
                                        Stock Bajo
                                    </span>
                                    <span
                                        v-else
                                        class="px-2 py-0.5 rounded text-[11px] font-medium text-slate-700 bg-slate-100 border border-slate-200"
                                    >
                                        Disponible
                                    </span>
                                </td>

                                <!-- Acciones -->
                                <td class="py-3 px-4 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center space-x-1">
                                        <button
                                            type="button"
                                            @click="openHistorial(prod)"
                                            class="p-1.5 text-slate-600 hover:text-slate-900 hover:bg-slate-100 rounded-md transition-colors cursor-pointer"
                                            title="Historial de Movimientos"
                                        >
                                            <History class="w-4 h-4" />
                                        </button>

                                        <button
                                            type="button"
                                            @click="openNuevaEntrada(prod.id)"
                                            class="p-1.5 text-slate-600 hover:text-slate-900 hover:bg-slate-100 rounded-md transition-colors cursor-pointer"
                                            title="Entrada a Stock (+)"
                                        >
                                            <ArrowDownToLine class="w-4 h-4" />
                                        </button>

                                        <button
                                            type="button"
                                            @click="openNuevaSalida(prod.id)"
                                            class="p-1.5 text-slate-600 hover:text-slate-900 hover:bg-slate-100 rounded-md transition-colors cursor-pointer"
                                            title="Salida de Stock (-)"
                                        >
                                            <ArrowUpFromLine class="w-4 h-4" />
                                        </button>

                                        <button
                                            type="button"
                                            @click="openEditar(prod)"
                                            class="p-1.5 text-slate-500 hover:text-slate-900 hover:bg-slate-100 rounded-md transition-colors cursor-pointer"
                                            title="Editar Producto"
                                        >
                                            <Pencil class="w-4 h-4" />
                                        </button>

                                        <button
                                            type="button"
                                            @click="eliminarProducto(prod)"
                                            class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-md transition-colors cursor-pointer"
                                            title="Eliminar Producto"
                                        >
                                            <Trash2 class="w-4 h-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="productos.length === 0">
                                <td colspan="7" class="py-10 text-center text-slate-400">
                                    <Package class="w-8 h-8 text-slate-300 mx-auto mb-2" />
                                    <p class="text-xs">No se encontraron productos coincidentes.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modales -->
        <ModalNuevoProducto
            :show="showModalNuevo"
            :tipos="tipos"
            @close="showModalNuevo = false"
        />

        <ModalMovimientoStock
            :show="showModalMovimiento"
            :productos="productos"
            :default-tipo="defaultTipoMovimiento"
            :selected-producto-id="selectedProdIdForMovimiento"
            @close="showModalMovimiento = false"
        />

        <ModalHistorialProducto
            :show="showModalHistorial"
            :producto="productoParaHistorial"
            @close="showModalHistorial = false"
            @open-entrada="(id) => { showModalHistorial = false; openNuevaEntrada(id); }"
            @open-salida="(id) => { showModalHistorial = false; openNuevaSalida(id); }"
        />

        <ModalEditarProducto
            :show="showModalEditar"
            :producto="productoParaEditar"
            :tipos="tipos"
            @close="showModalEditar = false"
        />
    </AppLayout>
</template>
