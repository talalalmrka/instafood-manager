import Alpine from 'alpinejs';

import {
    DataTable,
    executeAction,
    renderColumn,
    type DataTableActionContext,
    type DataTableActionHandler,
    type DataTableConfig,
    type DataTableRow,
} from './index';

import { createDataTableTemplate } from './template';

export function registerDataTable(): void {
    Alpine.data(
        'ifmDataTable',
        <T extends DataTableRow>(
            config: DataTableConfig<T> & {
                onAction?: DataTableActionHandler<T>;
            },
        ) => {
            const table = new DataTable(config);

            const actionContext: DataTableActionContext<T> = {
                actions: config.actions ?? [],
                buttons: config.buttons ?? [],
                onAction: config.onAction,
            };

            return {
                table,

                get rows() {
                    return table.rows;
                },

                get loading() {
                    return table.loading;
                },

                get selectedIds() {
                    return table.selectedIds;
                },

                get selectedCount() {
                    return table.selectedCount;
                },

                get hasSelection() {
                    return table.hasSelection;
                },

                get total() {
                    return table.total;
                },

                get page() {
                    return table.page;
                },

                get perPage() {
                    return table.perPage;
                },

                get totalPages() {
                    return table.totalPages;
                },

                get search() {
                    return table.search;
                },

                get sortBy() {
                    return table.sortBy;
                },

                get sortDirection() {
                    return table.sortDirection;
                },

                get isFirstPage() {
                    return table.isFirstPage;
                },

                get isLastPage() {
                    return table.isLastPage;
                },

                get allSelected() {
                    return table.allRowsSelected();
                },

                get template() {
                    return createDataTableTemplate(config);
                },

                init() {
                    this.$nextTick(() => {
                        table.load();
                    });
                },

                async refresh() {
                    await table.refresh();
                },

                async searchChanged(value: string) {
                    await table.setSearch(value);
                },

                async perPageChanged(value: string | number) {
                    await table.setPerPage(Number(value));
                },

                async sort(column: string) {
                    await table.setSort(column);
                },

                async nextPage() {
                    await table.nextPage();
                },

                async previousPage() {
                    await table.previousPage();
                },

                async goToPage(page: number) {
                    await table.goToPage(page);
                },

                toggleSelection(id: string | number) {
                    table.toggleSelection(id);
                },

                toggleSelectAll() {
                    table.toggleSelectAll();
                },

                isSelected(id: string | number) {
                    return table.isSelected(id);
                },

                clearSelection() {
                    table.clearSelection();
                },

                getRowId(row: T) {
                    return table.getRowId(row);
                },

                renderColumn(
                    column: DataTableConfig<T>['columns'][number],
                    row: T,
                ) {
                    return renderColumn(column, row);
                },

                async action(
                    action: string,
                    row?: T,
                ) {
                    await executeAction(
                        actionContext,
                        action,
                        row,
                    );
                },
            };
        },
    );
}