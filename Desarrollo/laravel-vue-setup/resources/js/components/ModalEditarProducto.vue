<script setup lang="ts">
import { watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Producto, TipoProducto } from '@/types/inventory';
import { X, Pencil, AlertCircle } from 'lucide-vue-next';

const props = defineProps<{
    show: boolean;
    producto: Producto | null;
    tipos: TipoProducto[];
}>();

const emit = defineEmits<{
    (e: 'close'): void;
}>();

const form = useForm({
    id_tipo_producto: '',
    descripcion: '',
    codigo: '',
    stock_minimo: 5,
});

watch(
    () => props.producto,
    (prod) => {
        if (prod) {
            form.id_tipo_producto = String(prod.id_tipo_producto);
            form.descripcion = prod.descripcion;
            form.codigo = prod.codigo;
            form.stock_minimo = prod.stock_minimo;
            form.clearErrors();
        }
    },
    { immediate: true }
);

const submit = () => {
    if (!props.producto) return;
    form.put(`/productos/${props.producto.id}`, {
        onSuccess: () => {
            emit('close');
        },
    });
};
</script>

<template>
    <div v-if="show && producto" class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6 overflow-y-auto">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs transition-opacity" @click="emit('close')"></div>

        <!-- Modal Dialog -->
        <div class="relative w-full max-w-lg bg-white rounded-xl shadow-xl border border-slate-200 overflow-hidden transform transition-all z-10">
            <!-- Header Neutro -->
            <div class="px-6 py-4 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Editar Producto</h3>
                    <p class="text-xs text-slate-500">Modifica los detalles del producto seleccionado</p>
                </div>
                <button
                    type="button"
                    @click="emit('close')"
                    class="rounded-lg p-1 text-slate-400 hover:text-slate-700 hover:bg-slate-200 transition-colors cursor-pointer"
                >
                    <X class="w-4 h-4" />
                </button>
            </div>

            <!-- Form -->
            <form @submit.prevent="submit" class="p-6 space-y-4">
                <!-- Tipo de Producto (Categoría) -->
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1.5">
                        Categoría / Tipo de Producto <span class="text-rose-500">*</span>
                    </label>
                    <select
                        v-model="form.id_tipo_producto"
                        required
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 bg-white text-slate-800 text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition-all outline-hidden cursor-pointer"
                    >
                        <option v-for="tipo in tipos" :key="tipo.id" :value="tipo.id">
                            {{ tipo.nombre }}
                        </option>
                    </select>
                    <p v-if="form.errors.id_tipo_producto" class="mt-1 text-xs text-rose-600 flex items-center gap-1">
                        <AlertCircle class="w-3.5 h-3.5" /> {{ form.errors.id_tipo_producto }}
                    </p>
                </div>

                <!-- Descripción -->
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1.5">
                        Descripción <span class="text-rose-500">*</span>
                    </label>
                    <input
                        v-model="form.descripcion"
                        type="text"
                        required
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 bg-white text-slate-800 text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition-all outline-hidden"
                    />
                    <p v-if="form.errors.descripcion" class="mt-1 text-xs text-rose-600 flex items-center gap-1">
                        <AlertCircle class="w-3.5 h-3.5" /> {{ form.errors.descripcion }}
                    </p>
                </div>

                <!-- Código y Stock Mínimo -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1.5">
                            Código SKU
                        </label>
                        <input
                            v-model="form.codigo"
                            type="text"
                            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 bg-white text-slate-800 text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition-all outline-hidden"
                        />
                        <p v-if="form.errors.codigo" class="mt-1 text-xs text-rose-600 flex items-center gap-1">
                            <AlertCircle class="w-3.5 h-3.5" /> {{ form.errors.codigo }}
                        </p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1.5">
                            Stock Mínimo (Alerta)
                        </label>
                        <input
                            v-model.number="form.stock_minimo"
                            type="number"
                            min="0"
                            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 bg-white text-slate-800 text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition-all outline-hidden"
                        />
                        <p v-if="form.errors.stock_minimo" class="mt-1 text-xs text-rose-600 flex items-center gap-1">
                            <AlertCircle class="w-3.5 h-3.5" /> {{ form.errors.stock_minimo }}
                        </p>
                    </div>
                </div>

                <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-600">
                    <span>Stock actual en bodega: <strong>{{ producto.stock_actual }} unidades</strong>. El stock actual se gestiona exclusivamente a través de los movimientos de Entrada y Salida.</span>
                </div>

                <!-- Footer Actions -->
                <div class="pt-3 border-t border-slate-100 flex items-center justify-end space-x-2">
                    <button
                        type="button"
                        @click="emit('close')"
                        class="px-3.5 py-2 text-xs font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors cursor-pointer"
                    >
                        Cancelar
                    </button>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex items-center gap-2 px-4 py-2 text-xs font-medium text-white bg-slate-900 rounded-lg hover:bg-slate-800 disabled:opacity-50 transition-colors cursor-pointer"
                    >
                        <span v-if="form.processing">Guardando...</span>
                        <span v-else>Guardar Cambios</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
