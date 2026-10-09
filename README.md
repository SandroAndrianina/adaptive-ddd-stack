# adaptive-ddd-stack
Config-driven DDD full-stack: PHP (CI4) + Java Servlet + MySQL + Docker. Declare entities in entities.json — CRUD, REST, UI, and file mirror are generated automatically.

- [x] **Step 0 — New repo + copy irm skeleton**, strip domain/controllers/routes to blank shells
- [x] **Step 1 — Shared `entities.json`** (single source of truth for entities, fields, types)
- [x] **Step 2 — Java `EntityConfigLoader` + `SchemaGenerator`** (auto `CREATE TABLE` at startup)
- [ ] **Step 3 — Java `GenericJdbcRepository`** (generic CRUD via `Map<String,Object>`)
- [ ] **Step 4 — Java `GenericServlet` + `GenericNotifier`** (`/api/{entity}[/{id}]` → CI4)
- [ ] **Step 5 — PHP `GenericJavaClient` + `GenericFileRepository`** (mirror to `writable/data/{entity}.json`)
- [ ] **Step 6 — PHP `GenericController` + dynamic routes** (`/api/{entity}` no per-entity code)
- [ ] **Step 7 — Auto-UI** reading `/api/_schema/{entity}` (table + form generated)
- [ ] **Step 9 — Full test from zero** (2 entities declared only in `entities.json`)
- [ ] **Step 10 — README + tag `v1.0`**

Say **start** when the new repo is created and I drop **Step 0 + Step 1**.