import type {
    DataTableAction,
    DataTableButton,
    DataTableRow,
} from './types';

export type DataTableActionHandler<T extends DataTableRow = DataTableRow> = (
    action: string,
    row?: T,
) => void | Promise<void>;

export interface DataTableActionContext<
    T extends DataTableRow = DataTableRow,
> {
    actions: DataTableAction<T>[];
    buttons: DataTableButton[];
    onAction?: DataTableActionHandler<T>;
}

export async function executeAction<
    T extends DataTableRow = DataTableRow,
>(
    context: DataTableActionContext<T>,
    action: string,
    row?: T,
): Promise<void> {
    const definition =
        context.actions.find((item) => item.action === action) ??
        context.buttons.find((item) => item.action === action);

    if (!definition) {
        return;
    }

    if (definition.confirm) {
        const confirmed = window.confirm(
            `Are you sure you want to ${definition.label.toLowerCase()}?`,
        );

        if (!confirmed) {
            return;
        }
    }

    if ('handler' in definition && definition.handler) {
        await definition.handler(row as T);
        return;
    }

    if (context.onAction) {
        await context.onAction(action, row);
    }
}
