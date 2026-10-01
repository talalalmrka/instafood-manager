import type { Content, CssClassValue, HtmlAttrs } from "../types";

export function cssClasses(...classes: CssClassValue[]): string {
  const result: string[] = [];

  for (const value of classes) {
    if (!value) {
      continue;
    }

    if (typeof value === "string") {
      result.push(value);
      continue;
    }

    if (Array.isArray(value)) {
      const nested = cssClasses(...value);

      if (nested) {
        result.push(nested);
      }

      continue;
    }

    for (const [className, condition] of Object.entries(value)) {
      if (condition) {
        result.push(className);
      }
    }
  }

  return result.join(" ");
}

export function attrs(attributes?: HtmlAttrs): string {
  if (!attributes) {
    return "";
  }
  return Object.entries(attributes)
    .filter(
      ([, value]) => value !== false && value !== null && value !== undefined,
    )
    .map(([name, value]) => {
      if (value === true) {
        return name;
      }

      return `${name}="${escapeHtml(String(value))}"`;
    })
    .join(" ");
}

export function escapeHtml(value: string): string {
  return value
    .replace(/&/g, "&amp;")
    .replace(/"/g, "&quot;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;");
}

export function match<T, R>(
  value: T,
  cases: Record<string, R>,
  defaultValue: R,
): R {
  return cases[String(value)] ?? defaultValue;
}

export function jsonPretty(value: any): string {
  return JSON.stringify(value, null, 2);
}

export async function flat(contents: (Content | Content[])[]) {
  const flatten = (items: (Content | Content[])[]): Content[] =>
    items.flatMap((item) => (Array.isArray(item) ? flatten(item) : item));

  return await Promise.all(flatten(contents));
}

export function ucfirst(value: string): string {
  return value.length ? value.charAt(0).toUpperCase() + value.slice(1) : "";
}

export function strTitle(str: string): string {
  return ucfirst(str).replace(/[-_]/g, " ");
}

export function strSlug(title: string, separator: string = "-"): string {
  return title
    .normalize("NFKD")
    .replace(/[\u0300-\u036f]/g, "")
    .replace(/['’]/g, "")
    .replace(/[^a-zA-Z0-9]+/g, separator)
    .replace(new RegExp(`${escapeRegExp(separator)}+`, "g"), separator)
    .replace(
      new RegExp(
        `^${escapeRegExp(separator)}|${escapeRegExp(separator)}$`,
        "g",
      ),
      "",
    )
    .toLowerCase();
}

export function escapeRegExp(value: string): string {
  return value.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
}
