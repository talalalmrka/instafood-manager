import { defineConfig } from "vite";
import tailwindcss from "@tailwindcss/vite";

export default defineConfig({
  plugins: [tailwindcss()],

  build: {
    outDir: "assets/dist",
    emptyOutDir: true,

    rollupOptions: {
      input: "assets/src/js/main.ts",

      output: {
        entryFileNames: "main.js",

        assetFileNames: (assetInfo) => {
          if (assetInfo.name?.endsWith(".css")) {
            return "style.css";
          }

          return "[name][extname]";
        },
      },
    },
  },
});
