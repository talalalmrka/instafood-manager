document.addEventListener("DOMContentLoaded", () => {
  const textarea = document.getElementById(
    "instafood_export_json",
  ) as HTMLTextAreaElement | null;

  const copyButton = document.getElementById(
    "instafood_copy_json",
  ) as HTMLButtonElement | null;

  const downloadButton = document.getElementById(
    "instafood_download_json",
  ) as HTMLButtonElement | null;

  const status = document.getElementById(
    "instafood_export_status",
  ) as HTMLElement | null;

  if (!textarea || !copyButton || !downloadButton || !status) {
    return;
  }

  const date = new Date();

  const dateString = [
    date.getFullYear(),
    String(date.getMonth() + 1).padStart(2, "0"),
    String(date.getDate()).padStart(2, "0"),
  ].join("-");

  const filename = `instafood-export-${dateString}.json`;

  const showStatus = (message: string, success = true): void => {
    status.textContent = message;
    status.style.color = success ? "#008a20" : "#d63638";

    window.setTimeout(() => {
      status.textContent = "";
    }, 2500);
  };

  copyButton.addEventListener("click", async () => {
    try {
      await navigator.clipboard.writeText(textarea.value);

      showStatus("JSON copied to clipboard.");
    } catch {
      textarea.focus();
      textarea.select();

      try {
        document.execCommand("copy");

        showStatus("JSON copied to clipboard.");
      } catch {
        showStatus("Unable to copy JSON.", false);
      }
    }
  });

  downloadButton.addEventListener("click", () => {
    const blob = new Blob([textarea.value], {
      type: "application/json;charset=utf-8",
    });

    const url = URL.createObjectURL(blob);
    const link = document.createElement("a");

    link.href = url;
    link.download = filename;

    document.body.appendChild(link);
    link.click();
    link.remove();

    URL.revokeObjectURL(url);

    showStatus("JSON file downloaded.");
  });
});
