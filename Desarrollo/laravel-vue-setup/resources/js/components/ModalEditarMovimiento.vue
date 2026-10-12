<script setup lang="ts">
import { watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { InventarioProducto, Producto } from '@/types/inventory';
import { X, Pencil, ArrowDownToLine, ArrowUpFromLine, AlertCircle } from 'lucide-vue-next';

const props = defineProps<{
    show: boolean;
    movimiento: InventarioProducto | null;
    productos: Producto[];
}>();

const emit = defineEmits<{
    (e: 'close'): void;
}>();

const form = useForm({
    id_producto: '',
    tipo_movimiento: 'ENTRADA' as 'ENTRADA' | 'SALIDA',
    cantidad: 1,
    fecha_ingreso: '',
    motivo: '',
    referencia: '',
});

watch(
    () => props.movimiento,
    (mov) => {
        if (mov) {
            form.id_producto = String(mov.id_producto);
            form.tipo_movimiento = mov.tipo_movimiento;
            form.cantidad = mov.cantidad;
            // Format fecha_ingreso to datetime-local format
            const dt = new Date(mov.fecha_ingreso);
            if (!isNaN(dt.getTime())) {
                dt.setMinutes(dt.getMinutes() - dt.getTimezoneOffset());
                form.fecha_ingreso = dt.toISOString().slice(0, 16);
            } else {
                form.fecha_ingreso = mov.fecha_ingreso.slice(0, 16);
            }
            form.motivo = mov.motivo;
            form.referencia = mov.referencia || '';
            form.clearErrors();
        }
    },
    { immediate: true }
);

const submit = () => {
    if (!props.movimiento) return;
    form.put(`/movimientos/${props.movimiento.id}`, {
        onSuccess: () => {
            emit('close');
        },
    });
};
</script>

<template>
    <div v-if="show && movimiento" class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6 overflow-y-auto">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs transition-opacity" @click="emit('close')"></div>

        <!-- Modal Dialog -->
        <div class="relative w-full max-w-lg bg-white rounded-xl shadow-xl border border-slate-200 overflow-hidden transform transition-all z-10">
            <!-- Header Neutro -->
            <div class="px-6 py-4 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Editar Movimiento #{{ movimiento.id }}</h3>
                    <p class="text-xs text-slate-500">Modifica los detalles del registro de inventario</p>
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
                <!-- Tipo de Movimiento -->
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1.5">
                        Tipo de Operación <span class="text-rose-500">*</span>
                    </label>
                    <div class="grid grid-cols-2 gap-2 p-1 bg-slate-100 rounded-lg">
                        <button
                            type="button"
                            @click="form.tipo_movimiento = 'ENTRADA'"
                            :class="[
                                'py-2 px-3 rounded-md text-xs font-medium transition-all flex items-center justify-center gap-1.5 cursor-pointer',
                                form.tipo_movimiento === 'ENTRADA'
                                    ? 'bg-slate-900 text-white'
                                    : 'text-slate-600 hover:text-slate-900'
                            ]"
                        >
                            <ArrowDownToLine class="w-3.5 h-3.5" />
                            Entrada (+ Stock)
                        </button>
                        <button
                            type="button"
                            @click="form.tipo_movimiento = 'SALIDA'"
                            :class="[
                                'py-2 px-3 rounded-md text-xs font-medium transition-all flex items-center justify-center gap-1.5 cursor-pointer',
                                form.tipo_movimiento === 'SALIDA'
                                    ? 'bg-slate-900 text-white'
                                    : 'text-slate-600 hover:text-slate-900'
                            ]"
                        >
                            <ArrowUpFromLine class="w-3.5 h-3.5" />
                            Salida (- Stock)
                        </button>
                    </div>
                </div>

                <!-- Producto -->
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1.5">
                        Producto <span class="text-rose-500">*</span>
                    </label>
                    <select
                        v-model="form.id_producto"
                        required
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 bg-white text-slate-800 text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition-all outline-hidden cursor-pointer"
                    >
                        <option v-for="prod in productos" :key="prod.id" :value="prod.id">
                            [{{ prod.codigo }}] {{ prod.descripcion }} (Actual: {{ prod.stock_actual }})
                        </option>
                    </select>
                    <p v-if="form.errors.id_producto" class="mt-1 text-xs text-rose-600 flex items-center gap-1">
                        <AlertCircle class="w-3.5 h-3.5" /> {{ form.errors.id_producto }}
                    </p>
                </div>

                <!-- Grid Cantidad y Fecha -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1.5">
                            Cantidad <span class="text-rose-500">*</span>
                        </label>
                        <input
                            v-model.number="form.cantidad"
                            type="number"
                            min="1"
                            required
                            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 bg-white text-slate-800 text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition-all outline-hidden"
                        />
                        <p v-if="form.errors.cantidad" class="mt-1 text-xs text-rose-600 flex items-center gap-1">
                            <AlertCircle class="w-3.5 h-3.5" /> {{ form.errors.cantidad }}
                        </p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1.5">
                            Fecha del Movimiento <span class="text-rose-500">*</span>
                        </label>
                        <input
                            v-model="form.fecha_ingreso"
                            type="datetime-local"
                            required
                            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 bg-white text-slate-800 text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition-all outline-hidden cursor-pointer"
                        />
                        <p v-if="form.errors.fecha_ingreso" class="mt-1 text-xs text-rose-600 flex items-center gap-1">
                            <AlertCircle class="w-3.5 h-3.5" /> {{ form.errors.fecha_ingreso }}
                        </p>
                    </div>
                </div>

                <!-- Motivo -->
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1.5">
                        Motivo / Justificación <span class="text-rose-500">*</span>
                    </label>
                    <input
                        v-model="form.motivo"
                        type="text"
                        required
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 bg-white text-slate-800 text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition-all outline-hidden"
                    />
                    <p v-if="form.errors.motivo" class="mt-1 text-xs text-rose-600 flex items-center gap-1">
                        <AlertCircle class="w-3.5 h-3.5" /> {{ form.errors.motivo }}
                    </p>
                </div>

                <!-- Referencia -->
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1.5">
                        Referencia o Documento
                    </label>
                    <input
                        v-model="form.referencia"
                        type="text"
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 bg-white text-slate-800 text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition-all outline-hidden"
                    />
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
