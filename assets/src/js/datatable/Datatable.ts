import Alpine from "alpinejs";
import type { DatatableOptions, Item, Column, Action } from "./types";
import { ajaxNonce, ajaxUrl } from "../helpers/globals";
import { jsonPretty } from "../helpers/base";
document.addEventListener("alpine:init", () => {
  Alpine.data("Datatable", (options: DatatableOptions) => ({
    primaryKey: options.primaryKey,
    ajaxAction: options.ajaxAction,
    columns: options.columns,
    buttons: options.buttons,
    actions: options.actions,
    filters: options.filters,
    items: options.items || [],
    pagination: [],
    selected: [],
    selectAll: false,
    loading: false,
    get itemsJson(){
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
    get rows(){
      if(!this.items.length && !this.loading){
        const colspan = this.columns.length + 2;
        return `<tr><td colspan="${colspan}" class="text-center">No items found!</td></tr>`;
      }
      return this.items.map((item: Item) => this.rowContent(item)).join("");
    },
    rowContent(item: Item){
      const itemId = item[this.primaryKey] ?? "";
      return `
      <tr>
      <td>
      <input type="checkbox" x-model="selected" value="${itemId}">
      </td>
      ${this.columns.map((column: Column) => this.rawColumn(item, column)).join("")}
      ${this.actionsColumn(item)}
      </tr>
      `.trim();
    },
    rawColumn(item: Item, column: Column){
      return `
      <td>
      ${item[column.name] ?? ''}
      </td>
      `.trim();
    },
    actionsColumn(item: Item){
      return `
      <td>
      <div class="flex items-center justify-center gap-3 md:gap-4">
      ${this.actions.map((action: Action) => this.actionContent(item, action)).join("")}
      </div>
      </td>
      `.trim();
    },
    actionContent(item: Item, action: Action){
      const itemId = item[this.primaryKey] ?? "";
      return `
      <button type="button" x-on:click="onActionClicked('${action.click}', ${itemId})" title="${action.label}" class="text-xs">
      <i class="icon ${action.icon}"></i>
      </button>
      `.trim();
    },
    async load(): Promise<void> {
      this.loading = true;

      try {
        const body = new URLSearchParams();

        body.set("action", this.ajaxAction);
        body.set("page", String(this.filters.page));
        body.set("per_page", String(this.filters.per_page));
        body.set("search", this.filters.search);

        if (ajaxNonce) {
          body.set("nonce", ajaxNonce);
        }

        if (this.filters.orderby) {
          body.set("orderby", this.filters.orderby);
        }

        if (this.filters.order) {
          body.set("orderby", this.filters.order);
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
        const responseData = result.data;
        console.log(responseData);
        this.items = responseData.items || [];
        this.pagination = responseData.pagination || [];
      } finally {
        this.loading = false;
      }
    },
    init() {
      this.load();
      this.$watch('filters', () => {
        this.load();
      });
      this.$watch('selected', (newVal) => {
        console.log("Selected", newVal);
      });
    },
  }));
});
