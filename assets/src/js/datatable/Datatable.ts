import Alpine from "alpinejs";
import {
  type DatatableOptions,
  type Item,
  type Column,
  type Action,
  type PaginationLink,
  paginationIcons,
  type RequestOptions,
  type RequestResponse,
} from "./types";
import { startLoading, finishLoading } from "../helpers/loading";
import { ajaxNonce, ajaxUrl } from "../helpers/globals";
import { cssClasses, dataGet, jsonPretty } from "../helpers/base";
import { Toast } from "../helpers/toast";
document.addEventListener("alpine:init", () => {
  Alpine.data("Datatable", (options: DatatableOptions) => ({
    primaryKey: options.primaryKey,
    ajaxPrefix: options.ajaxPrefix,
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
    loadingAction: null,
    itemIds() {
      return this.items.map((item: Item) => dataGet(item, this.primaryKey));
    },
    isLoading(action: string = "") {
      return this.loading && this.loadingAction === action;
    },
    setLoading(loading: boolean = true, action: string | null = null) {
      this.loading = loading;
      this.loadingAction = action;
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
      <button type="button" x-on:click="${action.click}(${itemId})" title="${action.label}" class="text-xs">
      <i class="icon ${action.icon}" :class="{'fg-loader-dots-move': isLoading('${action.click}')}"></i>
      </button>
      `.trim();
    },
    sortClass(columnName: string, order: "asc" | "desc") {
      return columnName === this.filters.orderby && order === this.filters.order
        ? "text-gray-900 dark:text-gray-100"
        : "text-gray-400 dark:text-gray-500";
    },
    async request(options: RequestOptions = {}): Promise<RequestResponse> {
      // const action = options.action ?? "";
      this.setLoading(true, options.action);
      // startLoading(action);
      try {
        const method = options.method ?? "GET";
        const params = new URLSearchParams();
        const values = options.params ?? {};
        if (options.action) {
          params.set("action", `${this.ajaxPrefix}_${options.action}`);
        }

        if (ajaxNonce) {
          params.set("nonce", ajaxNonce);
        }

        Object.entries(values).forEach(([key, value]) => {
          if (value !== undefined && value !== null && value !== "") {
            params.set(key, String(value));
          }
        });

        let url = ajaxUrl;
        const fetchOptions: RequestInit = {
          method,
          headers: {},
        };

        if (method === "GET") {
          const query = params.toString();
          url += `${url.includes("?") ? "&" : "?"}${query}`;

          if (options.replaceState) {
            const currentUrl = new URL(window.location.href);

            Object.entries(values).forEach(([key, value]) => {
              if (value !== undefined && value !== null && value !== "") {
                currentUrl.searchParams.set(key, String(value));
              } else {
                currentUrl.searchParams.delete(key);
              }
            });

            window.history.replaceState(
              window.history.state,
              "",
              currentUrl.toString(),
            );
          }
        } else {
          fetchOptions.headers = {
            "Content-Type": "application/x-www-form-urlencoded;charset=UTF-8",
          };
          fetchOptions.body = params.toString();
        }

        const response = await fetch(url, fetchOptions);

        if (!response.ok) {
          this.setLoading(false, null);
          throw new Error(`Request failed: ${response.status}`);
        }

        const result: RequestResponse = await response.json();

        if (!result.success) {
          this.setLoading(false, null);
          throw new Error(result.data?.message ?? "Request failed.");
        }
        this.setLoading(false, null);
        if (result.data?.message) {
          Toast.success(result.data?.message);
        }
        return result;
      } finally {
        this.setLoading(false, null);
        // finishLoading(action);
      }
    },
    async loadItems(): Promise<void> {
      try {
        const result = await this.request({
          method: "GET",
          action: "items",
          replaceState: true,
          params: {
            ...this.filters,
            ...{
              paged: this.paged,
            },
          },
        });
        const responseData = result.data;
        this.items = responseData.items || [];
        this.pagination = responseData.pagination || [];
      } catch (error) {
        Toast.error(error);
      }
    },
    async delete(id: string | number): Promise<void> {
      console.log(`Delete: ${id}`);
      try {
        const result = await this.request({
          method: "POST",
          action: "delete",
          params: {
            id: id,
          },
        });
        console.log("Result", result);
        const responseData = result.data;

        // this.items = responseData.items || [];
        // this.pagination = responseData.pagination || [];
      } catch (error) {
        Toast.error(error);
      }
    },

    /* async load(): Promise<void> {
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
    }, */

    init() {
      this.loadItems();

      this.$watch("filters", () => {
        this.paged = undefined;
        this.loadItems();
      });

      this.$watch("paged", () => {
        this.loadItems();
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
