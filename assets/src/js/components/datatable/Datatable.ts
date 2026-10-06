import type { DataTableConfig, DataTableRow } from "./types";
import { ajaxUrl, ajaxNonce } from "../../helpers/globals";
export class DataTable<T extends DataTableRow = DataTableRow> {
  public readonly config: DataTableConfig<T>;

  public rows: T[] = [];
  public selected: Set<string | number> = new Set();

  public loading = false;
  public search = "";
  public page = 1;
  public perPage: number;

  public total = 0;

  public sortBy: string | null = null;
  public sortDirection: "asc" | "desc" = "asc";

  constructor(config: DataTableConfig<T>) {
    this.config = config;
    this.perPage = config.perPage ?? 20;
  }

  get selectedIds(): Array<string | number> {
    return Array.from(this.selected);
  }

  get selectedCount(): number {
    return this.selected.size;
  }

  get hasSelection(): boolean {
    return this.selected.size > 0;
  }

  get totalPages(): number {
    if (!this.total || !this.perPage) {
      return 1;
    }

    return Math.ceil(this.total / this.perPage);
  }

  get isFirstPage(): boolean {
    return this.page <= 1;
  }

  get isLastPage(): boolean {
    return this.page >= this.totalPages;
  }

  async load(): Promise<void> {
    this.loading = true;

    try {
      const response = await this.request();

      this.rows = response.rows;
      this.total = response.total;

      this.clearInvalidSelection();
    } finally {
      this.loading = false;
    }
  }

  async refresh(): Promise<void> {
    await this.load();
  }

  async goToPage(page: number): Promise<void> {
    if (page < 1 || page > this.totalPages || page === this.page) {
      return;
    }

    this.page = page;

    await this.load();
  }

  async nextPage(): Promise<void> {
    if (this.isLastPage) {
      return;
    }

    await this.goToPage(this.page + 1);
  }

  async previousPage(): Promise<void> {
    if (this.isFirstPage) {
      return;
    }

    await this.goToPage(this.page - 1);
  }

  async setSearch(value: string): Promise<void> {
    this.search = value;
    this.page = 1;

    await this.load();
  }

  async setPerPage(value: number): Promise<void> {
    this.perPage = value;
    this.page = 1;

    await this.load();
  }

  async setSort(column: string): Promise<void> {
    if (this.sortBy === column) {
      this.sortDirection = this.sortDirection === "asc" ? "desc" : "asc";
    } else {
      this.sortBy = column;
      this.sortDirection = "asc";
    }

    this.page = 1;

    await this.load();
  }

  toggleSelection(id: string | number): void {
    if (this.selected.has(id)) {
      this.selected.delete(id);
    } else {
      this.selected.add(id);
    }
  }

  isSelected(id: string | number): boolean {
    return this.selected.has(id);
  }

  selectAll(): void {
    for (const row of this.rows) {
      const id = this.getRowId(row);

      this.selected.add(id);
    }
  }

  clearSelection(): void {
    this.selected.clear();
  }

  toggleSelectAll(): void {
    if (this.allRowsSelected()) {
      this.clearSelection();
      return;
    }

    this.selectAll();
  }

  allRowsSelected(): boolean {
    if (!this.rows.length) {
      return false;
    }

    return this.rows.every((row) => {
      return this.selected.has(this.getRowId(row));
    });
  }

  getRowId(row: T): string | number {
    const id = row.id;

    if (typeof id !== "string" && typeof id !== "number") {
      throw new Error("DataTable rows must contain an id.");
    }

    return id;
  }

  protected async request(): Promise<{
    rows: T[];
    total: number;
  }> {
    const body = new URLSearchParams();

    body.set("action", this.config.ajax.action);
    body.set("page", String(this.page));
    body.set("per_page", String(this.perPage));
    body.set("search", this.search);

    if (ajaxNonce) {
      body.set("nonce", ajaxNonce);
    }

    if (this.sortBy) {
      body.set("sort_by", this.sortBy);
      body.set("sort_direction", this.sortDirection);
    }

    const response = await fetch(ajaxUrl, {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
      },
      body,
    });

    if (!response.ok) {
      throw new Error(`DataTable request failed: ${response.status}`);
    }

    const result = await response.json();

    if (!result.success) {
      throw new Error(result.data?.message ?? "DataTable request failed.");
    }

    return {
      rows: result.data.rows ?? [],
      total: Number(result.data.total ?? 0),
    };
  }

  private clearInvalidSelection(): void {
    const ids = new Set(this.rows.map((row) => this.getRowId(row)));

    for (const id of this.selected) {
      if (!ids.has(id)) {
        this.selected.delete(id);
      }
    }
  }
}
