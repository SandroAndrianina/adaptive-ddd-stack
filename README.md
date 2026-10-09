# adaptive-ddd-stack
Config-driven DDD full-stack: PHP (CI4) + Java Servlet + MySQL + Docker. Declare entities in entities.json — CRUD, REST, UI, and file mirror are generated automatically.

- [x] **Step 0 — New repo + copy irm skeleton**, strip domain/controllers/routes to blank shells
- [x] **Step 1 — Shared `entities.json`** (single source of truth for entities, fields, types)
- [x] **Step 2 — Java `EntityConfigLoader` + `SchemaGenerator`** (auto `CREATE TABLE` at startup)
- [x] **Step 3 — Java `GenericJdbcRepository`** (generic CRUD via `Map<String,Object>`)
- [x] **Step 4 — Java `GenericServlet` + `GenericNotifier`** (`/api/{entity}[/{id}]` → CI4)
- [x] **Step 5 — PHP `GenericJavaClient` + `GenericFileRepository`** (mirror to `writable/data/{entity}.json`)
- [x] **Step 6 — PHP `GenericController` + dynamic routes** (`/api/{entity}` no per-entity code)
- [x] **Step 7 — Auto-UI** reading `/api/_schema/{entity}` (table + form generated)
- [ ] **Step 8 — Layout rework**: sidebar navigation + tabs, right panel table
- [ ] **Step 9 — Row edit**: PUT flow, prefill form, edit/delete buttons per row
- [ ] **Step 10 — Entity admin UI**: `/admin` page listing entities with edit/delete
- [ ] **Step 11 — `EntityConfigWriter` in PHP**: create/modify/delete entities in `entities.json` from the UI
- [ ] **Step 12 — Java hot-reload**: `POST /api/_reload` re-runs `SchemaGenerator`, PHP calls it after each save
- [ ] **Step 13 — Drop entity**: `POST /api/_drop/{entity}` with double confirmation (type name to confirm)
- [ ] **Step 14 — Full test from zero** (create a new entity end-to-end from the browser, no code, no restart)
- [ ] **Step 15 — README + tag `v1.0`**

Say **start** and I'll drop **Step 8 — Layout rework (sidebar + tabs)**.

Say **start** when the new repo is created and I drop **Step 0 + Step 1**.