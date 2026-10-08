import Alpine from "alpinejs";
import {
  type DatatableOptions,
  type Item,
  type Column,
  type Action,
  type PaginationLink,
  paginationIcons,
} from "./types";
import { ajaxNonce, ajaxUrl } from "../helpers/globals";
import { cssClasses, dataGet, jsonPretty } from "../helpers/base";
document.addEventListener("alpine:init", () => {
  Alpine.data("Datatable", (options: DatatableOptions) => ({
    primaryKey: options.primaryKey,
    ajaxAction: options.ajaxAction,
    columns: options.columns,
    buttons: options.buttons,
    actions: options.actions,
    paged: options.paged,
    filters: options.filters,
    items: options.items || [],
    pagination: {},
    selected: [],
    selectAll: false,
    loading: false,
    itemIds() {
      return this.items.map((item: Item) => dataGet(item, this.primaryKey));
    },
    get itemsJson() {
      return jsonPretty(this.items);
    },
    onButtonClicked(action: string) {
      console.log("Button clicked", action);
    },
    onActionClicked(action: string, id: any) {
      console.log("Action clicked", {
        action: action,
        id: id,
      });
    },
    resetFilters() {
      this.filters = {};
    },
    get rows() {
      if (!this.items.length && !this.loading) {
        const colspan = this.columns.length + 2;
        return `<tr><td colspan="${colspan}" class="text-center">No items found!</td></tr>`;
      }
      return this.items.map((item: Item) => this.rowContent(item)).join("");
    },
    hasPagination(): boolean {
      return this.pagination.last_page > 1;
    },
    get paginationHtml() {
      if (!this.hasPagination()) {
        return "";
      }

      return `
      <div class="pagination-summary">Page ${this.pagination.current_page} of ${
        this.pagination.last_page
      }</div>
      <nav class="pagination" aria-label="Pagination" role="pagination">
      <button x-on:click="goToPage(1)" class="pagination-item" title="First Page"${
        this.paged === 1 ? " disabled" : ""
      }>
        <i class="icon bi-chevron-double-left rtl:bi-chevron-double-right"></i>
      </button>
      ${this.pagination.links
        .map((item: PaginationLink) => this.paginationLinkHtml(item))
        .join("")}
      <button x-on:click="goToPage(${
        this.pagination.last_page
      })" class="pagination-item" title="Last Page" ${
        this.paged === this.pagination.last_page ? " disabled" : ""
      }>
        <i class="icon bi-chevron-double-right rtl:bi-chevron-double-left"></i>
      </button>
      </nav>
      `.trim();
    },
    paginationLinkHtml(item: PaginationLink) {
      const icon = paginationIcons[item.label];
      const label = icon ? `<i class="icon ${icon}"></i>` : item.label;
      const clickAttr = item.page ? ` x-on:click="goToPage(${item.page})"` : "";
      return `<button${clickAttr} title="Page ${item.page}" class="${cssClasses(
        "pagination-item",
        {
          active: item.active,
        },
      )}"${item.page ? "" : " disabled"}>${label}</button$>`;
    },
    goToPage(paged: number) {
      if (this.paged !== paged) {
        this.paged = paged;
      }
    },
    sort(columnName: string) {
      const orderby = columnName;
      const order =
        this.filters.orderby === columnName && this.filters.order === "asc"
          ? "desc"
          : "asc";
      const currentFilters = this.filters;
      currentFilters.orderby = orderby;
      currentFilters.order = order;
      this.filters = currentFilters;
    },
    rowContent(item: Item) {
      const itemId = item[this.primaryKey] ?? "";
      return `
      <tr>
      <td class="text-center">
      <input type="checkbox" x-model="selected" value="${itemId}">
      </td>
      ${this.columns
        .map((column: Column) => this.rawColumn(item, column))
        .join("")}
      ${this.actionsColumn(item)}
      </tr>
      `.trim();
    },
    rawColumn(item: Item, column: Column) {
      return `
      <td>
      ${item[column.name] ?? ""}
      </td>
      `.trim();
    },
    actionsColumn(item: Item) {
      return this.actions.length
        ? `
      <td>
      <div class="flex items-center justify-center gap-3 md:gap-4">
      ${this.actions
        .map((action: Action) => this.actionContent(item, action))
        .join("")}
      </div>
      </td>
      `.trim()
        : "";
    },
    actionContent(item: Item, action: Action) {
      const itemId = item[this.primaryKey] ?? "";
      return `
      <button type="button" x-on:click="onActionClicked('${action.click}', ${itemId})" title="${action.label}" class="text-xs">
      <i class="icon ${action.icon}"></i>
      </button>
      `.trim();
    },
    sortClass(columnName: string, order: "asc" | "desc") {
      return columnName === this.filters.orderby && order === this.filters.order
        ? "text-gray-900 dark:text-gray-100"
        : "text-gray-400 dark:text-gray-500";
    },
    async load(): Promise<void> {
      this.loading = true;

      try {
        const params = new URLSearchParams();

        params.set("action", this.ajaxAction);
        if (this.paged) {
          params.set("paged", String(this.paged));
        }

        if (this.filters.per_page) {
          params.set("per_page", String(this.filters.per_page));
        }

        if (this.filters.search) {
          params.set("search", this.filters.search);
        }

        if (this.filters.orderby) {
          params.set("orderby", this.filters.orderby);
        }

        if (this.filters.order) {
          params.set("order", this.filters.order);
        }

        if (ajaxNonce) {
          params.set("nonce", ajaxNonce);
        }

        const url = `${ajaxUrl}?${params.toString()}`;

        const currentUrl = new URL(window.location.href);

        Object.entries(this.filters).forEach(([key, value]) => {
          if (value !== undefined && value !== null && value !== "") {
            currentUrl.searchParams.set(key, String(value));
          } else {
            currentUrl.searchParams.delete(key);
          }
        });

        if (
          this.paged !== undefined &&
          this.paged !== null &&
          this.paged !== "" &&
          this.paged !== 1
        ) {
          currentUrl.searchParams.set("paged", String(this.paged));
        } else {
          currentUrl.searchParams.delete("paged");
        }
        history.replaceState({}, "", currentUrl.toString());

        const response = await fetch(url, {
          method: "GET",
          headers: {
            Accept: "application/json",
          },
        });

        if (!response.ok) {
          throw new Error(`DataTable request failed: ${response.status}`);
        }

        const result = await response.json();

        if (!result.success) {
          throw new Error(result.data?.message ?? "DataTable request failed.");
        }

        const responseData = result.data;

        this.items = responseData.items || [];
        this.pagination = responseData.pagination || [];
      } finally {
        this.loading = false;
      }
    },

    init() {
      this.load();

      this.$watch("filters", () => {
        this.paged = undefined;
        this.load();
      });

      this.$watch("paged", () => {
        this.load();
      });

      this.$watch("selected", (newVal) => {
        console.log("Selected", newVal);
      });

      this.$watch("selectAll", (newVal) => {
        if (newVal) {
          this.selected = this.itemIds();
        } else {
          this.selected = [];
        }
      });
    },
  }));
});
