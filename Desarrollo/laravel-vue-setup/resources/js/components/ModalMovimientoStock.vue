<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Producto } from '@/types/inventory';
import {
    X,
    ArrowDownToLine,
    ArrowUpFromLine,
    AlertCircle,
    AlertTriangle,
    Layers,
    Calendar,
    FileText,
} from 'lucide-vue-next';

const props = defineProps<{
    show: boolean;
    productos: Producto[];
    defaultTipo?: 'ENTRADA' | 'SALIDA';
    selectedProductoId?: number | null;
}>();

const emit = defineEmits<{
    (e: 'close'): void;
}>();

const nowFormatted = () => {
    const d = new Date();
    d.setMinutes(d.getMinutes() - d.getTimezoneOffset());
    return d.toISOString().slice(0, 16);
};

const form = useForm({
    id_producto: props.selectedProductoId ? String(props.selectedProductoId) : '',
    tipo_movimiento: (props.defaultTipo || 'ENTRADA') as 'ENTRADA' | 'SALIDA',
    cantidad: 1,
    fecha_ingreso: nowFormatted(),
    motivo: '',
    referencia: '',
});

watch(
    () => props.show,
    (isOpen) => {
        if (isOpen) {
            form.clearErrors();
            form.tipo_movimiento = props.defaultTipo || 'ENTRADA';
            if (props.selectedProductoId) {
                form.id_producto = String(props.selectedProductoId);
            }
            form.fecha_ingreso = nowFormatted();
            form.cantidad = 1;
            form.motivo = '';
            form.referencia = '';
        }
    }
);

watch(
    () => props.selectedProductoId,
    (val) => {
        if (val) {
            form.id_producto = String(val);
        }
    }
);

watch(
    () => props.defaultTipo,
    (val) => {
        if (val) {
            form.tipo_movimiento = val;
        }
    }
);

const productoSeleccionado = computed(() => {
    return props.productos.find((p) => p.id === Number(form.id_producto)) || null;
});

const isSalidaInsuficiente = computed(() => {
    if (form.tipo_movimiento !== 'SALIDA' || !productoSeleccionado.value) return false;
    return form.cantidad > productoSeleccionado.value.stock_actual;
});

const quickMotivosEntrada = [
    'Compra de reposición',
    'Devolución de departamento',
    'Ajuste por inventario físico',
    'Ingreso extraordinario',
];

const quickMotivosSalida = [
    'Entrega para uso en oficina',
    'Suministro a personal de limpieza',
    'Consumo interno programado',
    'Avería o merma de material',
];

const setMotivo = (m: string) => {
    form.motivo = m;
};

const submit = () => {
    form.post('/movimientos', {
        onSuccess: () => {
            emit('close');
        },
    });
};
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6 overflow-y-auto">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs transition-opacity" @click="emit('close')"></div>

        <!-- Modal Dialog -->
        <div class="relative w-full max-w-lg bg-white rounded-2xl shadow-2xl border border-slate-100 overflow-hidden transform transition-all z-10">
            <!-- Header Neutro -->
            <div class="px-6 py-4 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-slate-900">
                        {{ form.tipo_movimiento === 'ENTRADA' ? 'Registrar Entrada de Stock' : 'Registrar Salida de Stock' }}
                    </h3>
                    <p class="text-xs text-slate-500">
                        {{ form.tipo_movimiento === 'ENTRADA' ? 'Aumenta las existencias en bodega' : 'Disminuye las existencias en bodega' }}
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

            <!-- Form -->
            <form @submit.prevent="submit" class="p-6 space-y-4">
                <!-- Selector de Tipo de Movimiento (Tabs Neutras) -->
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

                <!-- Selección de Producto -->
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1.5">
                        Producto Seleccionado <span class="text-rose-500">*</span>
                    </label>
                    <select
                        v-model="form.id_producto"
                        required
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 bg-white text-slate-800 text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition-all outline-hidden cursor-pointer"
                    >
                        <option value="" disabled>Selecciona el producto...</option>
                        <option v-for="prod in productos" :key="prod.id" :value="prod.id">
                            [{{ prod.codigo }}] {{ prod.descripcion }} (Disponibles: {{ prod.stock_actual }})
                        </option>
                    </select>
                    <p v-if="form.errors.id_producto" class="mt-1 text-xs text-rose-600 flex items-center gap-1">
                        <AlertCircle class="w-3.5 h-3.5" /> {{ form.errors.id_producto }}
                    </p>

                    <!-- Tarjeta informativa del producto seleccionado -->
                    <div
                        v-if="productoSeleccionado"
                        class="mt-2 p-3 rounded-xl bg-slate-50 border border-slate-200/80 flex items-center justify-between text-xs"
                    >
                        <div class="flex items-center space-x-2">
                            <Layers class="w-4 h-4 text-slate-400" />
                            <span class="text-slate-600 font-medium">Categoría:</span>
                            <span class="text-slate-800 font-semibold">{{ productoSeleccionado.tipo?.nombre }}</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <span class="text-slate-600">Stock Actual:</span>
                            <span
                                :class="[
                                    'px-2 py-0.5 rounded-md font-bold',
                                    productoSeleccionado.stock_actual === 0
                                        ? 'bg-rose-100 text-rose-700'
                                        : productoSeleccionado.stock_actual <= productoSeleccionado.stock_minimo
                                        ? 'bg-amber-100 text-amber-700'
                                        : 'bg-emerald-100 text-emerald-700'
                                ]"
                            >
                                {{ productoSeleccionado.stock_actual }} unidades
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Grid de Cantidad y Fecha -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Cantidad -->
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1.5">
                            Cantidad a {{ form.tipo_movimiento === 'ENTRADA' ? 'Ingresar' : 'Despachar' }} <span class="text-rose-500">*</span>
                        </label>
                        <input
                            v-model.number="form.cantidad"
                            type="number"
                            min="1"
                            required
                            :class="[
                                'w-full px-3.5 py-2.5 rounded-xl border text-slate-800 text-sm outline-hidden transition-all',
                                isSalidaInsuficiente
                                    ? 'border-rose-400 bg-rose-50/50 focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500'
                                    : 'border-slate-300 bg-white focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600'
                            ]"
                        />
                        <p v-if="form.errors.cantidad" class="mt-1 text-xs text-rose-600 flex items-center gap-1">
                            <AlertCircle class="w-3.5 h-3.5" /> {{ form.errors.cantidad }}
                        </p>
                    </div>

                    <!-- Fecha y hora de ingreso -->
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

                <!-- Alerta de Stock Insuficiente si es Salida -->
                <div
                    v-if="isSalidaInsuficiente"
                    class="rounded-xl bg-rose-50 border border-rose-200 p-3.5 flex items-start gap-2.5 text-xs text-rose-800 animate-pulse"
                >
                    <AlertTriangle class="w-4 h-4 text-rose-600 shrink-0 mt-0.5" />
                    <div>
                        <strong class="font-semibold">¡Existencia insuficiente!</strong>
                        <p class="mt-0.5">
                            Estás intentando retirar {{ form.cantidad }} unidades, pero solo hay {{ productoSeleccionado?.stock_actual }} disponibles en bodega. Por favor ajusta la cantidad.
                        </p>
                    </div>
                </div>

                <!-- Motivo / Observación con chips rápidos -->
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1.5">
                        Motivo / Justificación <span class="text-rose-500">*</span>
                    </label>
                    <input
                        v-model="form.motivo"
                        type="text"
                        required
                        placeholder="Ej: Reposición de suministros, Despacho mensual..."
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 bg-white text-slate-800 text-sm placeholder:text-slate-400 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition-all outline-hidden"
                    />

                    <!-- Sugerencias de motivos rápidos -->
                    <div class="mt-2 flex flex-wrap gap-1.5">
                        <span class="text-[11px] text-slate-400 self-center mr-1">Rápidos:</span>
                        <button
                            v-for="sug in (form.tipo_movimiento === 'ENTRADA' ? quickMotivosEntrada : quickMotivosSalida)"
                            :key="sug"
                            type="button"
                            @click="setMotivo(sug)"
                            class="text-[11px] px-2 py-0.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-md transition-colors cursor-pointer"
                        >
                            {{ sug }}
                        </button>
                    </div>

                    <p v-if="form.errors.motivo" class="mt-1 text-xs text-rose-600 flex items-center gap-1">
                        <AlertCircle class="w-3.5 h-3.5" /> {{ form.errors.motivo }}
                    </p>
                </div>

                <!-- Referencia Documental -->
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1.5">
                        Referencia o Documento (Opcional)
                    </label>
                    <input
                        v-model="form.referencia"
                        type="text"
                        placeholder="Ej: Factura F-4890, Vale de salida V-102"
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 bg-white text-slate-800 text-sm placeholder:text-slate-400 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition-all outline-hidden"
                    />
                    <p v-if="form.errors.referencia" class="mt-1 text-xs text-rose-600 flex items-center gap-1">
                        <AlertCircle class="w-3.5 h-3.5" /> {{ form.errors.referencia }}
                    </p>
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
                        :disabled="form.processing || isSalidaInsuficiente"
                        class="inline-flex items-center gap-2 px-4 py-2 text-xs font-medium text-white bg-slate-900 rounded-lg hover:bg-slate-800 disabled:opacity-50 disabled:cursor-not-allowed transition-colors cursor-pointer"
                    >
                        <span v-if="form.processing">Procesando...</span>
                        <span v-else>
                            {{ form.tipo_movimiento === 'ENTRADA' ? 'Guardar Entrada' : 'Guardar Salida' }}
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
