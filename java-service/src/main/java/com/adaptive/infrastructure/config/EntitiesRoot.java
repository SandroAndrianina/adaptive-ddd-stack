package com.adaptive.infrastructure.config;

import java.util.Map;

/** Root of entities.json: { "entities": { "scan": {...}, "patient": {...} } } */
public class EntitiesRoot {
    public Map<String, EntityDef> entities;
}