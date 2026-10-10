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

export interface DatatableOptions {
  primaryKey: string;
  ajaxPrefix: string;
  columns: Column[];
  buttons: Button[];
  actions: Action[];
  paged?: number;
  filters: Record<string, any>;
  items?: Item[];
}

export type PaginationLink = {
  title: string | null;
  label: string;
  page: number | null;
  active: boolean;
  disabled: boolean;
};

export type PaginatedResponse = {
  success: boolean;
  data: {
    message?: string;
    items?: Item[];
    pagination?: Pagination;
  };
};
export type RequestMethod = "GET" | "POST";

export type RequestOptions = {
  method?: RequestMethod;
  action?: string;
  replaceState?: boolean;
  params?: Record<string, any>;
};

export type Item = Record<string, any>;

export type Pagination = {
  total_items: number;
  total_pages: number;
  current_page: number;
  per_page: number;
};

export interface RequestResponse {
  success: boolean;
  data: {
    message?: string;
    items?: Item[];
    pagination?: Pagination;
    [key: string]: any;
  };
}

export const paginationIcons: Record<string, string> = {
  "pagination.previous": "bi-chevron-left rtl:bi-chevron-right",
  "pagination.next": "bi-chevron-right rtl:bi-chevron-left",
};
