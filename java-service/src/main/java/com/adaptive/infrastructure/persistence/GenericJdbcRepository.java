package com.adaptive.infrastructure.persistence;

import com.adaptive.infrastructure.config.EntityConfigLoader;
import com.adaptive.infrastructure.config.EntityDef;
import com.adaptive.infrastructure.config.FieldDef;

import java.sql.*;
import java.util.*;

/**
 * One repository for all entities. Driven entirely by EntityDef.
 */
public class GenericJdbcRepository {

    private final String  entityName;
    private final EntityDef def;

    public GenericJdbcRepository(String entityName) {
        this.entityName = entityName;
        this.def        = EntityConfigLoader.get(entityName);
    }

    public List<Map<String,Object>> findAll() {
        String sql = "SELECT * FROM " + def.table + " ORDER BY " + pk().column;
        List<Map<String,Object>> out = new ArrayList<>();
        try (Connection c = DatabaseConnection.getConnection();
             PreparedStatement ps = c.prepareStatement(sql);
             ResultSet rs = ps.executeQuery()) {
            while (rs.next()) out.add(row(rs));
        } catch (SQLException e) {
            throw new RuntimeException("findAll(" + entityName + "): " + e.getMessage(), e);
        }
        return out;
    }

    public Map<String,Object> findById(Object id) {
        String sql = "SELECT * FROM " + def.table + " WHERE " + pk().column + " = ?";
        try (Connection c = DatabaseConnection.getConnection();
             PreparedStatement ps = c.prepareStatement(sql)) {
            ps.setObject(1, id);
            try (ResultSet rs = ps.executeQuery()) {
                return rs.next() ? row(rs) : null;
            }
        } catch (SQLException e) {
            throw new RuntimeException("findById(" + entityName + "): " + e.getMessage(), e);
        }
    }

    public Map<String,Object> save(Map<String,Object> payload) {
        List<FieldDef> writable = writableFields();
        String cols  = writable.stream().map(f -> f.column).reduce((a,b) -> a+","+b).orElse("");
        String marks = String.join(",", Collections.nCopies(writable.size(), "?"));

        String sql = "INSERT INTO " + def.table + " (" + cols + ") VALUES (" + marks + ")";
        try (Connection c = DatabaseConnection.getConnection();
             PreparedStatement ps = c.prepareStatement(sql, Statement.RETURN_GENERATED_KEYS)) {

            int i = 1;
            for (FieldDef f : writable) ps.setObject(i++, coerce(payload.get(f.name), f));

            ps.executeUpdate();
            try (ResultSet keys = ps.getGeneratedKeys()) {
                if (keys.next()) return findById(keys.getObject(1));
            }
        } catch (SQLException e) {
            throw new RuntimeException("save(" + entityName + "): " + e.getMessage(), e);
        }
        return null;
    }

    public Map<String,Object> update(Object id, Map<String,Object> payload) {
        List<FieldDef> writable = writableFields();
        String sets = writable.stream()
                .map(f -> f.column + " = ?")
                .reduce((a,b) -> a+", "+b).orElse("");

        String sql = "UPDATE " + def.table + " SET " + sets + " WHERE " + pk().column + " = ?";
        try (Connection c = DatabaseConnection.getConnection();
             PreparedStatement ps = c.prepareStatement(sql)) {

            int i = 1;
            for (FieldDef f : writable) ps.setObject(i++, coerce(payload.get(f.name), f));
            ps.setObject(i, id);

            int n = ps.executeUpdate();
            return n > 0 ? findById(id) : null;
        } catch (SQLException e) {
            throw new RuntimeException("update(" + entityName + "): " + e.getMessage(), e);
        }
    }

    public boolean delete(Object id) {
        String sql = "DELETE FROM " + def.table + " WHERE " + pk().column + " = ?";
        try (Connection c = DatabaseConnection.getConnection();
             PreparedStatement ps = c.prepareStatement(sql)) {
            ps.setObject(1, id);
            return ps.executeUpdate() > 0;
        } catch (SQLException e) {
            throw new RuntimeException("delete(" + entityName + "): " + e.getMessage(), e);
        }
    }

    // --- helpers ------------------------------------------------------------

    private FieldDef pk() {
        return def.fields.stream().filter(f -> f.primary).findFirst()
                .orElseThrow(() -> new RuntimeException("No primary key on " + entityName));
    }

    /** Insertable/updatable fields: not primary, not auto-create. */
    private List<FieldDef> writableFields() {
        List<FieldDef> out = new ArrayList<>();
        for (FieldDef f : def.fields) {
            if (f.primary || f.autoCreate) continue;
            out.add(f);
        }
        return out;
    }

    /** JSON -> DB: booleans become 0/1, missing = null. */
    private Object coerce(Object v, FieldDef f) {
        if (v == null && f.defaultValue != null) v = f.defaultValue;
        if (v == null) return null;
        if ("boolean".equals(f.type)) {
            if (v instanceof Boolean b) return b ? 1 : 0;
            if (v instanceof Number  n) return n.intValue() != 0 ? 1 : 0;
        }
        if ("int".equals(f.type) && v instanceof Number n) return n.intValue();
        return v;
    }

    /** One row -> Map keyed by JSON name (camelCase). */
    private Map<String,Object> row(ResultSet rs) throws SQLException {
        Map<String,Object> m = new LinkedHashMap<>();
        for (FieldDef f : def.fields) {
            Object v = rs.getObject(f.column);
            if (v instanceof java.sql.Timestamp ts) v = ts.toString(); // "2026-10-09 08:23:50.0"
                if ("boolean".equals(f.type) && v != null) {
                    if (v instanceof Boolean b)      v = b;
                    else if (v instanceof Number n)  v = n.intValue() != 0;
                }
            m.put(f.name, v);
        }
        return m;
    }
}