import "../css/style.css";
import Alpine from "alpinejs";

// window.Alpine = Alpine;

Alpine.start();
import { initAjaxForms } from "./helpers/ajax-form";
initAjaxForms();
import "./pages/import";
import "./pages/export";
