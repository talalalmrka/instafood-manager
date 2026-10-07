import Alpine from 'alpinejs';
import {
    DataTable,
    executeAction,
    renderColumn,
    type DataTableActionContext,
    type DataTableConfig,
    type DataTableRow,
} from '../components/datatable';

import { createDataTableTemplate } from '../components/datatable/template';

interface Category extends DataTableRow {
    id: number;
    name: string;
    slug: string;
    count: number;
}

export function registerCategoriesPage(): void {
    Alpine.data(
        'ifmCategories',
        () => {
            const config: DataTableConfig<Category> = {
                id: 'categories-table',

                ajax: {
                    action: 'ifm_categories_datatable',
                    nonce: window.ifm.nonce,
                },

                columns: [
                    {
                        key: 'name',
                        label: 'Name',
                        sortable: true,
                        searchable: true,
                    },
                    {
                        key: 'slug',
                        label: 'Slug',
                        sortable: true,
                    },
                    {
                        key: 'count',
                        label: 'Products',
                        type: 'number',
                        sortable: true,
                    },
                ],

                buttons: [
                    {
                        action: 'create',
                        label: 'Create',
                        icon: 'bi-plus-lg',
                        variant: 'primary',
                    },
                    {
                        action: 'deleteSelected',
                        label: 'Delete selected',
                        icon: 'bi-trash',
                        variant: 'danger',
                        requiresSelection: true,
                        confirm: true,
                    },
                ],

                actions: [
                    {
                        action: 'show',
                        label: 'Show',
                        icon: 'bi-eye',
                    },
                    {
                        action: 'edit',
                        label: 'Edit',
                        icon: 'bi-pencil',
                    },
                    {
                        action: 'delete',
                        label: 'Delete',
                        icon: 'bi-trash',
                        confirm: true,
                    },
                ],

                selectable: true,
                searchable: true,
                sortable: true,
                pagination: true,
                perPage: 20,
            };

            const table = new DataTable(config);

            const actionContext: DataTableActionContext<Category> = {
                actions: config.actions ?? [],
                buttons: config.buttons ?? [],
            };

            return {
                table,

                config,

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

                init() {
                    this.$nextTick(() => {
                        table.load();
                    });
                },

                template: createDataTableTemplate(config),

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

                getRowId(row: Category) {
                    return table.getRowId(row);
                },

                renderColumn(
                    column: DataTableConfig<Category>['columns'][number],
                    row: Category,
                ) {
                    return renderColumn(column, row);
                },

                async action(
                    action: string,
                    row?: Category,
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