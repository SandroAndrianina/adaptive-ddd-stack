package com.adaptive.infrastructure.persistence;

import com.adaptive.infrastructure.config.EntityConfigLoader;
import com.adaptive.infrastructure.config.EntityDef;
import com.adaptive.infrastructure.config.FieldDef;

import java.sql.Connection;
import java.sql.SQLException;
import java.sql.Statement;
import java.util.Map;

/**
 * Turns EntityDefs into CREATE TABLE IF NOT EXISTS and runs them.
 * Called once at Tomcat startup.
 */
public class SchemaGenerator {

    public static void generateAll() {
        Map<String, EntityDef> entities = EntityConfigLoader.load();
        for (Map.Entry<String, EntityDef> e : entities.entrySet()) {
            createTable(e.getKey(), e.getValue());
        }
    }

    private static void createTable(String logicalName, EntityDef def) {
        StringBuilder sb = new StringBuilder("CREATE TABLE IF NOT EXISTS ")
                .append(def.table).append(" (");

        boolean first = true;
        for (FieldDef f : def.fields) {
            if (!first) sb.append(", ");
            first = false;

            sb.append(f.column).append(" ").append(sqlType(f));
            if (f.required) sb.append(" NOT NULL");
            if (f.auto)     sb.append(" AUTO_INCREMENT");
            if (f.primary)  sb.append(" PRIMARY KEY");
            if (f.autoCreate) sb.append(" DEFAULT CURRENT_TIMESTAMP");
            else if (f.defaultValue != null) sb.append(" DEFAULT ").append(sqlLiteral(f.defaultValue));
        }
        sb.append(")");

        String sql = sb.toString();
        System.out.println("[schema] " + logicalName + " -> " + sql);

        try (Connection c = DatabaseConnection.getConnection();
             Statement st = c.createStatement()) {
            st.execute(sql);
        } catch (SQLException e) {
            throw new RuntimeException("Schema gen failed for " + def.table + ": " + e.getMessage(), e);
        }
    }

    private static String sqlType(FieldDef f) {
        return switch (f.type) {
            case "int"       -> "INT";
            case "string"    -> "VARCHAR(255)";
            case "boolean"   -> "TINYINT(1)";
            case "timestamp" -> "TIMESTAMP";
            default -> throw new RuntimeException("Unknown type: " + f.type);
        };
    }

    private static String sqlLiteral(Object v) {
        if (v instanceof Number) {
            double d = ((Number) v).doubleValue();
            if (d == Math.floor(d)) return String.valueOf((long) d);   // 0.0 -> 0
            return String.valueOf(d);
        }
        if (v instanceof Boolean) return ((Boolean) v) ? "1" : "0";
        return "'" + v.toString().replace("'", "''") + "'";
    }
}