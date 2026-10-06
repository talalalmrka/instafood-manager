import "../css/style.css";
import Alpine from "alpinejs";
import { initAjaxForms } from "./helpers/ajax-form";
import "./datatable/Datatable";
// import { registerDataTable } from "./components/datatable/alpine";
// import { registerCategoriesPage } from "./pages/categories";

// registerDataTable();
// registerCategoriesPage();

// window.Alpine = Alpine;

Alpine.start();
initAjaxForms();
import "./pages/import";
import "./pages/export";
