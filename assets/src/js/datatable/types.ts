export interface Column {
  name: string;
  label?: string;
  sortable?: boolean;
  searchable?: boolean;
  headClass?: string;
  class?: string;
  noWrap?: boolean;
}

export interface Button {
  click: string;
  label: string;
  icon?: string;
  class?: string;
  requiresSelection?: boolean;
}

export interface Action {
  click: string;
  label: string;
  icon?: string;
  class?: string;
}

export type Item = Record<string, any>;

export interface DatatableOptions {
  primaryKey: string;
  ajaxAction: string;
  columns: Column[];
  buttons: Button[];
  actions: Action[];
  paged?: number;
  filters: Record<string, any>;
  items?: Item[];
}

export type PaginationLink = {
  url: string | null;
  label: string;
  page: number | null;
  active: boolean;
};

export type Pagination = {
  current_page: number;
  first_page_url: string;
  from: number | null;
  last_page: number;
  last_page_url: string;
  links: PaginationLink[];
  next_page_url: string | null;
  path: string;
  per_page: number;
  prev_page_url: string | null;
  to: number | null;
  total: number;
};

export type PaginatedResponse<T> = {
  success: boolean;
  data: {
    items: T[];
    pagination: Pagination;
  };
};

export const paginationIcons: Record<string, string> = {
  "pagination.previous": "bi-chevron-left rtl:bi-chevron-right",
  "pagination.next": "bi-chevron-right rtl:bi-chevron-left",
};
