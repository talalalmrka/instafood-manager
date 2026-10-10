import Alpine from "alpinejs";
import {
  type DatatableOptions,
  type Item,
  type Column,
  type Action,
  type PaginationLink,
  type RequestOptions,
  type RequestResponse,
} from "./types";
// import { startLoading, finishLoading } from "../helpers/loading";
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
    showDebug: localStorage.getItem("datatableDebug") === "true",
    selectAll: false,
    loading: false,
    loadingAction: null,
    loadingArgs: null,
    get debugJson() {
      return jsonPretty({
        primaryKey: this.primaryKey,
        ajaxPrefix: this.ajaxPrefix,
        paged: this.paged,
        filters: this.filters,
        items: this.items,
        pagination: this.pagination,
        selected: this.selected,
      });
    },
    toggleDebug() {
      this.showDebug = !this.showDebug;
      if (this.showDebug) {
        localStorage.setItem("datatableDebug", "true");
      } else {
        localStorage.removeItem("datatableDebug");
      }
    },
    itemIds() {
      return this.items.map((item: Item) => dataGet(item, this.primaryKey));
    },
    isLoading(action: string = "", args: any = null) {
      return (
        this.loading &&
        this.loadingAction === action &&
        this.loadingArgs === args
      );
    },
    setLoading(
      loading: boolean = true,
      action: string | null = null,
      args: any = null,
    ) {
      this.loading = loading;
      this.loadingAction = action;
      this.loadingArgs = args;
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
      this.filters = {
        search: "",
        per_page: 15,
        orderby: "",
        order: "",
      };
    },
    get rows() {
      if (!this.items.length && !this.loading) {
        const colspan = this.columns.length + 2;
        return `<tr><td colspan="${colspan}" class="text-center">No items found!</td></tr>`;
      }
      return this.items.map((item: Item) => this.rowContent(item)).join("");
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
      const itemId = dataGet(item, this.primaryKey);
      // const onClickAttr = `onActionClicked('${action.click}', ${itemId})`;
      const onClickAttr = `deleteItem(${itemId})`;
      return `
      <button type="button" x-on:click="${onClickAttr}" title="${action.label}" class="text-xs">
      <i class="icon ${action.icon}" :class="{'fg-loader-dots-move': isLoading('${action.click}')}"></i>
      </button>
      `.trim();
    },
    hasPagination() {
      return this.pagination?.total_pages && this.pagination.total_pages > 1;
    },
    get paginationLinks(): PaginationLink[] {
      if (!this.hasPagination()) {
        return [];
      }

      const currentPage = Number(
        this.pagination.current_page ?? this.paged ?? 1,
      );
      const totalPages = Number(
        this.pagination.total_pages ?? this.pagination.last_page ?? 1,
      );

      const links: PaginationLink[] = [];
      const addLink = (
        page: number | null,
        label: string,
        title: string,
        disabled = false,
        active = false,
      ) => {
        links.push({
          page,
          label,
          title,
          disabled,
          active,
        });
      };

      addLink(
        1,
        '<i class="icon bi-chevron-double-left rtl:bi-chevron-double-right"></i>',
        "First page",
        currentPage === 1,
      );

      addLink(
        currentPage > 1 ? currentPage - 1 : null,
        '<i class="icon bi-chevron-left rtl:bi-chevron-right"></i>',
        "Previous page",
        currentPage === 1,
      );

      const startPage = Math.max(1, Math.min(currentPage - 1, totalPages - 2));
      const endPage = Math.min(totalPages, startPage + 2);

      if (startPage > 1) {
        addLink(1, "1", "Page 1", false, currentPage === 1);

        if (startPage > 2) {
          addLink(null, "...", "More pages", true);
        }
      }

      for (let page = startPage; page <= endPage; page++) {
        addLink(
          page,
          String(page),
          `Page ${page}`,
          false,
          page === currentPage,
        );
      }

      if (endPage < totalPages) {
        if (endPage < totalPages - 1) {
          addLink(null, "...", "More pages", true);
        }

        addLink(
          totalPages,
          String(totalPages),
          `Page ${totalPages}`,
          false,
          currentPage === totalPages,
        );
      }

      addLink(
        currentPage < totalPages ? currentPage + 1 : null,
        '<i class="icon bi-chevron-right rtl:bi-chevron-left"></i>',
        "Next page",
        currentPage === totalPages,
      );

      addLink(
        totalPages,
        '<i class="icon bi-chevron-double-right rtl:bi-chevron-double-left"></i>',
        "Last page",
        currentPage === totalPages,
      );

      return links;
    },
    get paginationHtml() {
      if (!this.hasPagination()) {
        return "";
      }
      return `
      <div class="pagination-summary">Page ${this.pagination.current_page} of ${
        this.pagination.total_pages
      }</div>
      <nav class="pagination" aria-label="Pagination" role="pagination">
      ${this.paginationLinks
        .map((item: PaginationLink) => this.paginationLinkHtml(item))
        .join("")}
      </nav>
      `.trim();
    },
    paginationLinkHtml(item: PaginationLink) {
      const clickAttr = item.page ? ` x-on:click="goToPage(${item.page})"` : "";
      return `<button${clickAttr} title="${item.title}" class="${cssClasses(
        "pagination-item",
        {
          active: item.active,
        },
      )}"${item.disabled ? " disabled" : ""}>${item.label}</button$>`;
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
    async deleteItem(id: any): Promise<void> {
      console.log(`Delete: ${id}`);
      try {
        const result = await this.request({
          method: "POST",
          action: "delete",
          params: {
            id: id,
          },
        });

        const responseData = result.data;
        const responseId = responseData.id;
        if (responseId) {
          this.items = this.items.filter(
            (item: Item) => dataGet(item, this.primaryKey) !== responseId,
          );
        }
      } catch (error) {
        Toast.error(error);
      }
    },
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
