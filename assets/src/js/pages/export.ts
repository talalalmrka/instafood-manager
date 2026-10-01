import * as XLSX from "xlsx";
import { Toast } from "../helpers/toast";

interface MenuVariation {
  price?: number | string;
  variation_name_1?: string;
  variation_name_2?: string;
  ID?: number | string;
  [key: string]: unknown;
}

interface MenuItem {
  name?: string;
  description?: string;
  variations?: MenuVariation[];
  [key: string]: unknown;
}

interface MenuCategory {
  name?: string;
  items?: MenuItem[];
  [key: string]: unknown;
}

export class ExportPage {
  private container: HTMLElement | null;
  private textarea: HTMLTextAreaElement | null;
  private btnCopy: HTMLButtonElement | null;
  private btnCopyIcon: HTMLElement | null;
  private btnJson: HTMLButtonElement | null;
  private btnXls: HTMLButtonElement | null;

  constructor() {
    this.container = document.querySelector(
      "#ifm-export",
    ) as HTMLElement | null;

    this.textarea = this.container?.querySelector(
      "#textarea-json",
    ) as HTMLTextAreaElement | null;

    this.btnCopy = this.container?.querySelector(
      "#btn-copy",
    ) as HTMLButtonElement | null;

    this.btnCopyIcon = this.btnCopy?.querySelector(
      ".icon",
    ) as HTMLElement | null;

    this.btnJson = this.container?.querySelector(
      "#btn-json",
    ) as HTMLButtonElement | null;

    this.btnXls = this.container?.querySelector(
      "#btn-xls",
    ) as HTMLButtonElement | null;

    this.addListeners();
  }

  private addListeners(): void {
    this.btnCopy?.addEventListener("click", () => {
      void this.copy();
    });

    this.btnJson?.addEventListener("click", () => {
      this.downloadJson();
    });

    this.btnXls?.addEventListener("click", () => {
      this.downloadXls();
    });
  }

  static init(): void {
    new ExportPage();
  }

  private get fileName(): string {
    const date = new Date();

    const dateString = [
      date.getFullYear(),
      String(date.getMonth() + 1).padStart(2, "0"),
      String(date.getDate()).padStart(2, "0"),
    ].join("-");

    return `instafood-export-${dateString}`;
  }

  private getFileName(ext: string): string {
    return `${this.fileName}.${ext}`;
  }

  private async copy(): Promise<void> {
    if (!this.textarea) {
      return;
    }

    const text = this.textarea.value;

    try {
      if (navigator.clipboard) {
        await navigator.clipboard.writeText(text);
      } else {
        const textarea = document.createElement("textarea");

        textarea.value = text;
        textarea.setAttribute("readonly", "");
        textarea.style.position = "fixed";
        textarea.style.opacity = "0";
        textarea.style.pointerEvents = "none";

        document.body.appendChild(textarea);

        textarea.focus();
        textarea.select();
        textarea.setSelectionRange(0, textarea.value.length);

        const copied = document.execCommand("copy");

        textarea.remove();

        if (!copied) {
          throw new Error("Copy failed.");
        }
      }

      if (this.btnCopyIcon) {
        const icon = this.btnCopyIcon;

        icon.classList.remove("bi-clipboard");
        icon.classList.add("bi-clipboard-check");

        setTimeout(() => {
          icon.classList.remove("bi-clipboard-check");
          icon.classList.add("bi-clipboard");
        }, 2500);
      }

      Toast.success("JSON copied.");
    } catch {
      Toast.error("Unable to copy JSON.");
    }
  }

  private downloadJson(): void {
    if (!this.textarea) {
      return;
    }

    const blob = new Blob([this.textarea.value], {
      type: "application/json;charset=utf-8",
    });

    this.downloadBlob(blob, this.getFileName("json"));

    Toast.success("JSON file downloaded.");
  }

  private downloadXls(): void {
    if (!this.textarea) {
      return;
    }

    let data: unknown;

    try {
      data = JSON.parse(this.textarea.value);
    } catch {
      Toast.error("Invalid JSON data.");
      return;
    }

    try {
      const workbook = this.createWorkbook(data);

      XLSX.writeFile(workbook, this.getFileName("xlsx"));

      Toast.success("Excel file downloaded.");
    } catch (error) {
      console.error(error);
      Toast.error("Unable to create Excel file.");
    }
  }

  private createWorkbook(data: unknown): XLSX.WorkBook {
    const workbook = XLSX.utils.book_new();

    const categories = this.getCategories(data);

    const categoryRows: Record<string, unknown>[] = [];
    const itemRows: Record<string, unknown>[] = [];
    const variationRows: Record<string, unknown>[] = [];

    categories.forEach((category, categoryIndex) => {
      const categoryName = this.getValue(
        category,
        "name",
        `Category ${categoryIndex + 1}`,
      );

      categoryRows.push({
        Order: categoryIndex + 1,
        Category: categoryName,
      });

      const items = this.getItems(category);

      items.forEach((item, itemIndex) => {
        const itemName = this.getValue(item, "name", `Item ${itemIndex + 1}`);

        const description = this.getValue(item, "description", "");

        itemRows.push({
          Category: categoryName,
          Order: itemIndex + 1,
          Item: itemName,
          Description: description,
        });

        const variations = this.getVariations(item);

        variations.forEach((variation, variationIndex) => {
          variationRows.push({
            Category: categoryName,
            Item: itemName,
            Order: variationIndex + 1,
            Price: variation.price ?? "",
            VariationName1: variation.variation_name_1 ?? "",
            VariationName2: variation.variation_name_2 ?? "",
            ID: variation.ID ?? "",
          });
        });
      });
    });

    const categoriesSheet = XLSX.utils.json_to_sheet(categoryRows);
    const itemsSheet = XLSX.utils.json_to_sheet(itemRows);
    const variationsSheet = XLSX.utils.json_to_sheet(variationRows);

    this.autoSizeColumns(categoriesSheet, categoryRows);
    this.autoSizeColumns(itemsSheet, itemRows);
    this.autoSizeColumns(variationsSheet, variationRows);

    XLSX.utils.book_append_sheet(workbook, categoriesSheet, "Categories");

    XLSX.utils.book_append_sheet(workbook, itemsSheet, "Items");

    XLSX.utils.book_append_sheet(workbook, variationsSheet, "Variations");

    return workbook;
  }

  private getCategories(data: unknown): MenuCategory[] {
    if (Array.isArray(data)) {
      return data as MenuCategory[];
    }

    if (!this.isObject(data)) {
      return [];
    }

    const possibleKeys = ["categories", "category", "menu", "data"];

    for (const key of possibleKeys) {
      const value = data[key];

      if (Array.isArray(value)) {
        return value as MenuCategory[];
      }
    }

    return [];
  }

  private getItems(category: MenuCategory): MenuItem[] {
    if (!this.isObject(category)) {
      return [];
    }

    const items = category.items;

    return Array.isArray(items) ? (items as MenuItem[]) : [];
  }

  private getVariations(item: MenuItem): MenuVariation[] {
    if (!this.isObject(item)) {
      return [];
    }

    const variations = item.variations;

    return Array.isArray(variations) ? (variations as MenuVariation[]) : [];
  }

  private getValue(
    object: Record<string, unknown>,
    key: string,
    fallback: string,
  ): string {
    const value = object[key];

    if (value === null || value === undefined) {
      return fallback;
    }

    return String(value);
  }

  private isObject(value: unknown): value is Record<string, unknown> {
    return typeof value === "object" && value !== null;
  }

  private autoSizeColumns(
    worksheet: XLSX.WorkSheet,
    rows: Record<string, unknown>[],
  ): void {
    if (rows.length === 0) {
      return;
    }

    const headers = Object.keys(rows[0]);

    worksheet["!cols"] = headers.map((header) => {
      let maxLength = header.length;

      rows.forEach((row) => {
        const value = row[header];

        if (value !== null && value !== undefined) {
          maxLength = Math.max(maxLength, String(value).length);
        }
      });

      return {
        wch: Math.min(Math.max(maxLength + 2, 10), 60),
      };
    });
  }

  private downloadBlob(blob: Blob, filename: string): void {
    const url = URL.createObjectURL(blob);
    const link = document.createElement("a");

    link.href = url;
    link.download = filename;

    document.body.appendChild(link);
    link.click();
    link.remove();

    URL.revokeObjectURL(url);
  }
}

document.addEventListener("DOMContentLoaded", () => {
  ExportPage.init();
});
