export interface TipoProducto {
    id: number;
    nombre: string;
    descripcion?: string | null;
    created_at?: string;
    updated_at?: string;
}

export interface Producto {
    id: number;
    id_tipo_producto: number;
    codigo: string;
    descripcion: string;
    stock_actual: number;
    stock_minimo: number;
    tipo?: TipoProducto;
    movimientos_count?: number;
    created_at?: string;
    updated_at?: string;
}

export interface InventarioProducto {
    id: number;
    id_producto: number;
    tipo_movimiento: 'ENTRADA' | 'SALIDA';
    cantidad: number;
    fecha_ingreso: string;
    motivo: string;
    referencia?: string | null;
    producto?: Producto;
    created_at?: string;
    updated_at?: string;
}

export interface InventoryMetrics {
    total_productos: number;
    total_unidades: number;
    stock_bajo: number;
    agotados: number;
    total_entradas: number;
    total_salidas: number;
}

export interface HistorialItem {
    id: number;
    tipo_movimiento: 'ENTRADA' | 'SALIDA';
    cantidad: number;
    fecha_ingreso: string;
    motivo: string;
    referencia?: string | null;
    saldo_resultante: number;
    created_at?: string;
}

export interface ProductHistoryResponse {
    producto: Producto;
    historial: HistorialItem[];
    resumen: {
        total_entradas: number;
        total_salidas: number;
        stock_actual: number;
        stock_minimo: number;
        total_movimientos: number;
    };
}
