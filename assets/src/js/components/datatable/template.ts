import type {
    DataTableColumn,
    DataTableConfig,
    DataTableRow,
} from './types';

export function createDataTableTemplate<
    T extends DataTableRow = DataTableRow,
>(
    config: DataTableConfig<T>,
): string {
    return `
        <div class="ifm-datatable">
            ${renderToolbar(config)}

            ${renderTable(config)}

            ${renderPagination()}
        </div>
    `;
}

function renderToolbar<
    T extends DataTableRow,
>(
    config: DataTableConfig<T>,
): string {
    return `
        <div class="flex items-center justify-between gap-4 mb-4">
            <div class="flex items-center gap-2">
                ${renderButtons(config)}
            </div>

            ${
                config.searchable !== false
                    ? `
                        <div class="relative">
                            <input
                                type="search"
                                x-model="search"
                                @input.debounce.400ms="
                                    searchChanged($event.target.value)
                                "
                                placeholder="Search..."
                                class="form-input"
                            >
                        </div>
                    `
                    : ''
            }
        </div>
    `;
}

function renderButtons<
    T extends DataTableRow,
>(
    config: DataTableConfig<T>,
): string {
    if (!config.buttons?.length) {
        return '';
    }

    return config.buttons
        .map((button) => {
            const disabled = button.requiresSelection
                ? ':disabled="!hasSelection"'
                : '';

            const classes = getButtonClasses(
                button.variant ?? 'secondary',
            );

            return `
                <button
                    type="button"
                    class="${classes}"
                    ${disabled}
                    @click="action('${button.action}')"
                >
                    ${
                        button.icon
                            ? `<i class="${button.icon}"></i>`
                            : ''
                    }

                    <span>${button.label}</span>
                </button>
            `;
        })
        .join('');
}

function renderTable<
    T extends DataTableRow,
>(
    config: DataTableConfig<T>,
): string {
    return `
        <div class="relative overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr>
                        ${renderHeader(config)}
                    </tr>
                </thead>

                <tbody>
                    <template
                        x-for="row in rows"
                        :key="getRowId(row)"
                    >
                        <tr>
                            ${renderRow(config)}
                        </tr>
                    </template>
                </tbody>
            </table>

            <template x-if="loading">
                <div class="absolute inset-0 flex items-center justify-center">
                    <div class="animate-spin">
                        <i class="bi-arrow-repeat"></i>
                    </div>
                </div>
            </template>

            <template x-if="!loading && rows.length === 0">
                <div class="py-12 text-center">
                    <span>No results found.</span>
                </div>
            </template>
        </div>
    `;
}

function renderHeader<
    T extends DataTableRow,
>(
    config: DataTableConfig<T>,
): string {
    const selection = config.selectable
        ? `
            <th class="w-10">
                <input
                    type="checkbox"
                    :checked="allSelected"
                    @change="toggleSelectAll()"
                >
            </th>
        `
        : '';

    const columns = config.columns
        .map((column) => renderHeaderColumn(column))
        .join('');

    const actions = config.actions?.length
        ? '<th class="w-1 whitespace-nowrap">Actions</th>'
        : '';

    return `${selection}${columns}${actions}`;
}

function renderHeaderColumn<
    T extends DataTableRow,
>(
    column: DataTableColumn<T>,
): string {
    if (!column.sortable) {
        return `<th>${column.label}</th>`;
    }

    return `
        <th>
            <button
                type="button"
                @click="sort('${column.key}')"
                class="inline-flex items-center gap-1"
            >
                <span>${column.label}</span>

                <template x-if="sortBy === '${column.key}'">
                    <i
                        :class="
                            sortDirection === 'asc'
                                ? 'bi-arrow-up'
                                : 'bi-arrow-down'
                        "
                    ></i>
                </template>
            </button>
        </th>
    `;
}

function renderRow<
    T extends DataTableRow,
>(
    config: DataTableConfig<T>,
): string {
    const selection = config.selectable
        ? `
            <td>
                <input
                    type="checkbox"
                    :checked="isSelected(getRowId(row))"
                    @change="toggleSelection(getRowId(row))"
                >
            </td>
        `
        : '';

    const columns = config.columns
        .map((column) => renderCell(column))
        .join('');

    const actions = config.actions?.length
        ? renderActions(config)
        : '';

    return `${selection}${columns}${actions}`;
}

function renderCell<
    T extends DataTableRow,
>(
    column: DataTableColumn<T>,
): string {
    const columnConfig = JSON.stringify({
        key: column.key,
        label: column.label,
        type: column.type,
        sortable: column.sortable,
        searchable: column.searchable,
    });

    return `
        <td>
            <span
                x-html="renderColumn(${escapeAttribute(columnConfig)}, row)"
            ></span>
        </td>
    `;
}

function renderActions<
    T extends DataTableRow,
>(
    config: DataTableConfig<T>,
): string {
    return `
        <td>
            <div class="flex items-center gap-1">
                ${
                    config.actions
                        ?.map((item) => {
                            return `
                                <button
                                    type="button"
                                    title="${item.label}"
                                    @click="
                                        action(
                                            '${item.action}',
                                            row
                                        )
                                    "
                                >
                                    ${
                                        item.icon
                                            ? `<i class="${item.icon}"></i>`
                                            : item.label
                                    }
                                </button>
                            `;
                        })
                        .join('')
                }
            </div>
        </td>
    `;
}

function renderPagination(): string {
    return `
        <div class="flex items-center justify-between gap-4 mt-4">
            <div>
                <span>
                    Page
                    <span x-text="page"></span>
                    of
                    <span x-text="totalPages"></span>
                </span>
            </div>

            <div class="flex items-center gap-2">
                <button
                    type="button"
                    :disabled="isFirstPage"
                    @click="previousPage()"
                >
                    <i class="bi-chevron-left"></i>
                </button>

                <button
                    type="button"
                    :disabled="isLastPage"
                    @click="nextPage()"
                >
                    <i class="bi-chevron-right"></i>
                </button>
            </div>
        </div>
    `;
}

function getButtonClasses(
    variant: string,
): string {
    const variants: Record<string, string> = {
        primary: 'btn btn-primary',
        secondary: 'btn btn-secondary',
        danger: 'btn btn-danger',
        warning: 'btn btn-warning',
        ghost: 'btn btn-ghost',
    };

    return variants[variant] ?? variants.secondary;
}

function escapeAttribute(value: string): string {
    return value
        .replace(/\\/g, '\\\\')
        .replace(/'/g, "\\'");
}