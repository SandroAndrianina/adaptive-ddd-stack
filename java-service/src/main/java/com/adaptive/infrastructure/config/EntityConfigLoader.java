package com.adaptive.infrastructure.config;

import com.adaptive.infrastructure.json.JsonMapper;

import java.io.FileReader;
import java.util.Map;

public final class EntityConfigLoader {

    private static final String PATH = "/etc/adaptive/entities.json";
    private static Map<String, EntityDef> cache;

    public static synchronized Map<String, EntityDef> load() {
        if (cache != null) return cache;
        try (FileReader r = new FileReader(PATH)) {
            EntitiesRoot root = JsonMapper.GSON.fromJson(r, EntitiesRoot.class);
            cache = root.entities;
            return cache;
        } catch (Exception e) {
            throw new RuntimeException("Cannot read " + PATH + ": " + e.getMessage(), e);
        }
    }

    public static EntityDef get(String name) {
        EntityDef d = load().get(name);
        if (d == null) throw new RuntimeException("Unknown entity: " + name);
        return d;
    }

    private EntityConfigLoader() {}
}