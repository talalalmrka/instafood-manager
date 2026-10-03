import type {
    DataTableColumn,
    DataTableRow,
} from './types';

function escapeHtml(value: unknown): string {
    const element = document.createElement('div');

    element.textContent = String(value ?? '');

    return element.innerHTML;
}

export function renderColumn<
    T extends DataTableRow = DataTableRow,
>(
    column: DataTableColumn<T>,
    row: T,
): string {
    const value = row[column.key];

    if (column.render) {
        return column.render(value, row);
    }

    switch (column.type ?? 'text') {
        case 'image':
            return renderImage(value);

        case 'number':
            return renderNumber(value);

        case 'currency':
            return renderCurrency(value);

        case 'date':
            return renderDate(value);

        case 'badge':
            return renderBadge(value);

        case 'custom':
            return String(value ?? '');

        case 'text':
        default:
            return escapeHtml(value);
    }
}

function renderImage(value: unknown): string {
    if (!value) {
        return '';
    }

    const src = escapeHtml(value);

    return `
        <img
            src="${src}"
            alt=""
            class="size-10 rounded-lg object-cover"
            loading="lazy"
        >
    `;
}

function renderNumber(value: unknown): string {
    const number = Number(value);

    if (Number.isNaN(number)) {
        return '';
    }

    return new Intl.NumberFormat().format(number);
}

function renderCurrency(value: unknown): string {
    const number = Number(value);

    if (Number.isNaN(number)) {
        return '';
    }

    return new Intl.NumberFormat(undefined, {
        style: 'currency',
        currency: 'USD',
    }).format(number);
}

function renderDate(value: unknown): string {
    if (!value) {
        return '';
    }

    const date = new Date(String(value));

    if (Number.isNaN(date.getTime())) {
        return escapeHtml(value);
    }

    return new Intl.DateTimeFormat(undefined, {
        dateStyle: 'medium',
    }).format(date);
}

function renderBadge(value: unknown): string {
    const text = escapeHtml(value);

    return `
        <span class="fgx:badge">
            ${text}
        </span>
    `;
}
