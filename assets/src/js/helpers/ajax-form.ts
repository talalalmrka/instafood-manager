import type { ResultData } from "../types";
import { ajaxUrl } from "./globals";
import { strTitle } from "./base";
export function initAjaxForms(): void {
  document.addEventListener("submit", handleSubmit);
}

function getResultEl(form: HTMLFormElement): HTMLElement {
  let resultElement = form.querySelector<HTMLElement>(".ajax-result");

  if (!resultElement) {
    resultElement = document.createElement("div");
    resultElement.className = "ajax-result alert alert-soft sm mt-3 hidden";
    form.appendChild(resultElement);
  }

  return resultElement;
}

function getSubmitButton(form: HTMLFormElement): HTMLButtonElement | null {
  return form.querySelector<HTMLButtonElement>('[type="submit"]');
}

function getLoadIcon(): HTMLElement {
  const loadEl = document.createElement("i");

  loadEl.className = "icon bi-spinner animate-spin";

  return loadEl;
}

function showResult(element: HTMLElement, data: ResultData): void {
  const { type, message, summary = undefined } = data;
  element.innerHTML = "";
  element.classList.remove("hidden");

  element.classList.remove("alert-success", "alert-error");

  element.classList.add(type === "success" ? "alert-success" : "alert-error");
  if (!summary) {
    element.textContent = message;
    return;
  }
  const p = document.createElement("p");
  p.innerHTML = message;
  element.appendChild(p);
  if (summary) {
    const ul = document.createElement("ul");
    Object.entries(summary).forEach(([key, value]) => {
      const li = document.createElement("li");
      li.innerHTML = `${strTitle(key)}: ${value}`;
      ul.appendChild(li);
    });
    element.appendChild(ul);
  }
}

async function handleSubmit(event: SubmitEvent): Promise<void> {
  const target = event.target;

  if (!(target instanceof HTMLFormElement)) {
    return;
  }

  const form = target;

  if (!form.classList.contains("ajax-form")) {
    return;
  }

  event.preventDefault();

  const resultElement = getResultEl(form);
  const submitButton = getSubmitButton(form);
  const loadIcon = getLoadIcon();

  resultElement.classList.add("hidden");

  if (submitButton) {
    submitButton.appendChild(loadIcon);
    submitButton.disabled = true;
  }

  try {
    const formData = new FormData(form);

    const response = await fetch(ajaxUrl, {
      method: form.method ?? "POST",
      body: formData,
    });

    const result = await response.json();

    if (!response.ok || !result.success) {
      throw new Error(result.data?.message ?? "An unexpected error occurred.");
    }

    console.log(result.data);

    showResult(resultElement, {
      type: "success",
      message: result.data?.message ?? "Done.",
      summary: result.data?.summary,
    });

    form.dispatchEvent(
      new CustomEvent("ajax:success", {
        detail: result,
        bubbles: true,
      }),
    );
  } catch (error) {
    const message =
      error instanceof Error ? error.message : "An unexpected error occurred.";

    showResult(resultElement, {
      type: "error",
      message: message,
    });

    form.dispatchEvent(
      new CustomEvent("ajax:error", {
        detail: {
          message,
          error,
        },
        bubbles: true,
      }),
    );
  } finally {
    loadIcon.remove();

    if (submitButton) {
      submitButton.disabled = false;
    }
  }
}
