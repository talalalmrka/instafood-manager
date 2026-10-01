/*interface InstaFoodVariation {
  [key: string]: unknown;
}*/

/*interface InstaFoodItem {
  name: string;
  variations: InstaFoodVariation[];
  [key: string]: unknown;
}*/

/*interface InstaFoodCategory {
  name: string;
  items: InstaFoodItem[];
  [key: string]: unknown;
}*/

/*interface InstaFoodData {
  categories: InstaFoodCategory[];
  [key: string]: unknown;
}*/

const validateButton = document.getElementById(
  "instafood_validate",
) as HTMLButtonElement | null;

const jsonTextarea = document.getElementById(
  "instafood_json",
) as HTMLTextAreaElement | null;

const validationResult = document.getElementById(
  "instafood_validation_result",
) as HTMLElement | null;

validateButton?.addEventListener("click", () => {
  if (!jsonTextarea || !validationResult) {
    return;
  }

  try {
    const data: unknown = JSON.parse(jsonTextarea.value);

    if (!isRecord(data) || Array.isArray(data)) {
      throw new Error("The root JSON value must be an object.");
    }

    if (!Array.isArray(data.categories)) {
      throw new Error('The "categories" property must be an array.');
    }

    let itemCount = 0;
    let variationCount = 0;

    for (const category of data.categories) {
      if (!isRecord(category) || Array.isArray(category)) {
        throw new Error("Every category must be an object.");
      }

      if (typeof category.name !== "string" || !category.name.trim()) {
        throw new Error('Every category must have a non-empty "name".');
      }

      if (!Array.isArray(category.items)) {
        throw new Error('Every category must have an "items" array.');
      }

      for (const item of category.items) {
        if (!isRecord(item) || Array.isArray(item)) {
          throw new Error("Every item must be an object.");
        }

        if (typeof item.name !== "string" || !item.name.trim()) {
          throw new Error('Every item must have a non-empty "name".');
        }

        if (!Array.isArray(item.variations)) {
          throw new Error('Every item must have a "variations" array.');
        }

        itemCount += 1;
        variationCount += item.variations.length;
      }
    }

    validationResult.innerHTML = `
            <div class="alert alert-success alert-soft sm mt-2">
                Valid JSON. Categories: ${data.categories.length},
                Items: ${itemCount},
                Variations: ${variationCount}.
            </div>
        `;
  } catch (error: unknown) {
    const message =
      error instanceof Error ? error.message : "An unknown error occurred.";
    validationResult.innerHTML = `
            <div class="alert alert-error alert-soft sm mt-2">
                Invalid JSON: ${escapeHtml(message)}
            </div>
        `;
  }
});

function isRecord(value: unknown): value is Record<string, unknown> {
  return typeof value === "object" && value !== null;
}

function escapeHtml(value: string): string {
  const div = document.createElement("div");

  div.textContent = value;

  return div.innerHTML;
}

const importForm = document.querySelector<HTMLFormElement>(
  "#instafood-import-form",
);

importForm?.addEventListener("submit", (event) => {
  const checkbox = document.getElementById(
    "delete_previous",
  ) as HTMLInputElement | null;

  if (!checkbox?.checked) {
    return;
  }

  const confirmed = window.confirm(
    "WARNING: This will permanently delete all existing InstaFood items and categories before importing. Continue?",
  );

  if (!confirmed) {
    event.preventDefault();
  }
});

const fileInput = document.getElementById(
  "instafood_json_file",
) as HTMLInputElement | null;

const fileStatus = document.getElementById(
  "instafood_file_status",
) as HTMLElement | null;

fileInput?.addEventListener("change", () => {
  const file = fileInput.files?.[0];

  if (!file || !jsonTextarea || !fileStatus) {
    return;
  }

  const isJson =
    file.type === "application/json" ||
    file.name.toLowerCase().endsWith(".json");

  if (!isJson) {
    fileInput.value = "";
    fileStatus.textContent = "Please select a JSON file.";
    fileStatus.style.color = "#d63638";

    return;
  }

  const reader = new FileReader();

  reader.onload = (event) => {
    const content = event.target?.result;

    if (typeof content !== "string") {
      fileStatus.textContent = "Unable to read the file.";
      fileStatus.style.color = "#d63638";

      return;
    }

    jsonTextarea.value = content;

    fileStatus.textContent = "JSON file loaded successfully.";
    fileStatus.style.color = "#008a20";
  };

  reader.onerror = () => {
    fileStatus.textContent = "Unable to read the file.";
    fileStatus.style.color = "#d63638";
  };

  reader.readAsText(file);
});
