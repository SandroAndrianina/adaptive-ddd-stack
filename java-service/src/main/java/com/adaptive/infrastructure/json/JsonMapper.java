package com.adaptive.infrastructure.json;

import com.google.gson.Gson;
import com.google.gson.GsonBuilder;

/**
 * One Gson instance. Pretty printing off (smaller payload).
 */
public final class JsonMapper {

    public static final Gson GSON = new GsonBuilder().create();

    private JsonMapper() {}
}