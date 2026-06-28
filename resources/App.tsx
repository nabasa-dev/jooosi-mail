import './wp-admin';
import { createRoot } from "react-dom/client"

import AdminApp from "@/admin/app"

import "@/styles/app.css"

const container = document.getElementById("jooosi-mail-app")

if (container !== null) {
  createRoot(container).render(<AdminApp />)
}
