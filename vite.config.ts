import { defineConfig } from "vite";
import { resolve } from "path";
import tailwindcss from "@tailwindcss/vite";

export default defineConfig({
  plugins: [tailwindcss()],

  build: {
    outDir: "assets/dist",
    emptyOutDir: true,

    rollupOptions: {
      // input: "assets/src/js/main.ts",
      input: {
        main: resolve(__dirname, "assets/src/js/main.ts"),
        debug: resolve(__dirname, "assets/src/js/debug.ts"),
      },
      /*output: {
        entryFileNames: "main.js",

        assetFileNames: (assetInfo) => {
          if (assetInfo.name?.endsWith(".css")) {
            return "style.css";
          }

          return "[name][extname]";
        },
      },*/
      output: {
        // Keeps names clean: dist/main.js and dist/debug.js instead of main-[hash].js
        entryFileNames: "[name].js",
        assetFileNames: "[name].[ext]",
        chunkFileNames: "[name].js",
      },
    },
  },
});
