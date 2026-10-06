import Alpine from "alpinejs";
import type { DatatableOptions } from "./types";
import { ajaxNonce, ajaxUrl } from "../helpers/globals";
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
    onButtonClicked(action: string) {
      console.log("Button clicked", action);
    },
    onActionClicked(action: string, id: any) {
      console.log("Action clicked", {
        action: action,
        id: id,
      });
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
    },
  }));
});
