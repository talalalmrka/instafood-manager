import Alpine from "alpinejs";

type LoadingStore = {
  total: number;
  actions: Record<string, number>;
  start(action?: string): void;
  finish(action?: string): void;
  isLoading(target?: string): boolean;
};

export function registerLoading(): void {
  const store: LoadingStore = {
    total: 0,
    actions: {},

    start(action = "") {
      this.total++;

      if (action) {
        this.actions[action] = (this.actions[action] ?? 0) + 1;
      }
    },

    finish(action = "") {
      this.total = Math.max(0, this.total - 1);

      if (action && this.actions[action]) {
        this.actions[action]--;

        if (this.actions[action] <= 0) {
          delete this.actions[action];
        }
      }
    },

    isLoading(target = "") {
      if (!target) return this.total > 0;

      return target
        .split(",")
        .map((value) => value.trim())
        .filter(Boolean)
        .some((value) => (this.actions[value] ?? 0) > 0);
    },
  };

  Alpine.store("requestLoading", store);

  Alpine.directive("loading", (el, { modifiers, expression }, { cleanup }) => {
    const target = el.getAttribute("x-loading.target") ?? "";
    const classMode = modifiers.includes("class");
    const classes = classMode
      ? expression.trim().split(/\s+/).filter(Boolean)
      : [];

    const originalDisplay = el.style.display;

    const update = () => {
      const loading = store.isLoading(target);

      if (classMode) {
        for (const className of classes) {
          el.classList.toggle(className, loading);
        }
      } else {
        el.style.display = loading ? originalDisplay : "none";
      }
    };

    const stop = Alpine.effect(update);

    cleanup(() => {
      stop();

      for (const className of classes) {
        el.classList.remove(className);
      }

      el.style.display = originalDisplay;
    });
  });
}

export function startLoading(action?: string): void {
  (Alpine.store("requestLoading") as LoadingStore).start(action);
}

export function finishLoading(action?: string): void {
  (Alpine.store("requestLoading") as LoadingStore).finish(action);
}
