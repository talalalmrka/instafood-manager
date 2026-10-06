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
  filters: Record<string, any>;
  items?: Item[];
}
