export type DataTableRow = Record<string, unknown>;

export type DataTableColumnType =
    | 'text'
    | 'image'
    | 'number'
    | 'currency'
    | 'date'
    | 'badge'
    | 'custom';

export interface DataTableColumn<T extends DataTableRow = DataTableRow> {
    key: string;
    label: string;
    type?: DataTableColumnType;
    sortable?: boolean;
    searchable?: boolean;
    render?: (value: unknown, row: T) => string;
}

export interface DataTableButton {
    action: string;
    label: string;
    icon?: string;
    variant?: 'primary' | 'secondary' | 'danger' | 'warning' | 'ghost';
    requiresSelection?: boolean;
    confirm?: boolean;
}

export interface DataTableAction<T extends DataTableRow = DataTableRow> {
    action: string;
    label: string;
    icon?: string;
    confirm?: boolean;
    handler?: (row: T) => void | Promise<void>;
}

export interface DataTableAjaxConfig {
    action: string;
    nonce?: string;
}

export interface DataTableConfig<T extends DataTableRow = DataTableRow> {
    id: string;

    ajax: DataTableAjaxConfig;

    columns: DataTableColumn<T>[];

    buttons?: DataTableButton[];

    actions?: DataTableAction<T>[];

    selectable?: boolean;

    searchable?: boolean;

    sortable?: boolean;

    pagination?: boolean;

    perPage?: number;
}