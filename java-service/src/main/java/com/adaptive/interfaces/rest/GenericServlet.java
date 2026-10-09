package com.adaptive.interfaces.rest;

import com.adaptive.infrastructure.config.EntityConfigLoader;
import com.adaptive.infrastructure.http.GenericNotifier;
import com.adaptive.infrastructure.json.JsonMapper;
import com.adaptive.infrastructure.persistence.GenericJdbcRepository;

import jakarta.servlet.annotation.WebServlet;
import jakarta.servlet.http.*;
import java.io.IOException;
import java.util.Map;

/**
 * /api/{entity}         GET (list) | POST (create)
 * /api/{entity}/{id}    GET | PUT | DELETE
 */
@WebServlet(urlPatterns = {"/api/*"})
public class GenericServlet extends HttpServlet {

    private GenericNotifier notifier;

    @Override
    public void init() {
        notifier = new GenericNotifier(System.getenv("PHP_BASE_URL"));
    }

    @Override
    protected void doGet(HttpServletRequest req, HttpServletResponse resp) throws IOException {
        withErrors(resp, () -> {
            Path p = parse(req);
            GenericJdbcRepository repo = new GenericJdbcRepository(p.entity);
            if (p.id == null) {
                writeJson(resp, 200, repo.findAll());
            } else {
                Map<String,Object> row = repo.findById(p.id);
                if (row == null) { writeJson(resp, 404, Map.of("error","not found")); return; }
                writeJson(resp, 200, row);
            }
        });
    }

    @Override
    protected void doPost(HttpServletRequest req, HttpServletResponse resp) throws IOException {
        withErrors(resp, () -> {
            Path p = parse(req);
            Map<String,Object> body = JsonMapper.GSON.fromJson(req.getReader(), Map.class);
            GenericJdbcRepository repo = new GenericJdbcRepository(p.entity);
            Map<String,Object> saved = repo.save(body);
            notifier.notify(p.entity, "create", saved.get("id"), saved);
            writeJson(resp, 201, saved);
        });
    }

    @Override
    protected void doPut(HttpServletRequest req, HttpServletResponse resp) throws IOException {
        withErrors(resp, () -> {
            Path p = parse(req);
            if (p.id == null) { writeJson(resp, 400, Map.of("error","id required")); return; }
            Map<String,Object> body = JsonMapper.GSON.fromJson(req.getReader(), Map.class);
            GenericJdbcRepository repo = new GenericJdbcRepository(p.entity);
            Map<String,Object> updated = repo.update(p.id, body);
            if (updated == null) { writeJson(resp, 404, Map.of("error","not found")); return; }
            notifier.notify(p.entity, "update", p.id, updated);
            writeJson(resp, 200, updated);
        });
    }

    @Override
    protected void doDelete(HttpServletRequest req, HttpServletResponse resp) throws IOException {
        withErrors(resp, () -> {
            Path p = parse(req);
            if (p.id == null) { writeJson(resp, 400, Map.of("error","id required")); return; }
            GenericJdbcRepository repo = new GenericJdbcRepository(p.entity);
            boolean ok = repo.delete(p.id);
            notifier.notify(p.entity, "delete", p.id, null);
            writeJson(resp, ok ? 200 : 404, Map.of("deleted", ok));
        });
    }

    // --- helpers -----------------------------------------------------------

    private record Path(String entity, String id) {}

    /** /api/{entity}  or  /api/{entity}/{id} */
    private Path parse(HttpServletRequest req) {
        String path = req.getPathInfo();           // "/scan" or "/scan/5"
        if (path == null || path.equals("/")) {
            throw new RuntimeException("entity name required");
        }
        String[] parts = path.substring(1).split("/");
        String entity  = parts[0];
        String id      = parts.length > 1 ? parts[1] : null;

        // Validate against the config — prevents /api/anything
        EntityConfigLoader.get(entity);
        return new Path(entity, id);
    }

    private interface Action { void run() throws IOException; }

    private void withErrors(HttpServletResponse resp, Action a) throws IOException {
        try { a.run(); }
        catch (RuntimeException e) { writeJson(resp, 500, Map.of("error", e.getMessage())); }
    }

    private void writeJson(HttpServletResponse resp, int status, Object body) throws IOException {
        resp.setStatus(status);
        resp.setContentType("application/json; charset=UTF-8");
        resp.getWriter().write(JsonMapper.GSON.toJson(body));
    }
}