package com.adaptive.infrastructure.config;

import com.google.gson.annotations.SerializedName;

public class FieldDef {
    public String  name;          // JSON key sent to clients (camelCase)
    public String  type;          // int | string | boolean | timestamp
    public String  column;        // SQL column name (snake_case)
    public boolean primary  = false;
    public boolean auto     = false;   // AUTO_INCREMENT
    public boolean required = false;   // NOT NULL
    public boolean autoCreate = false; // DEFAULT CURRENT_TIMESTAMP

    @SerializedName("default")
    public Object defaultValue;   // "default" is a Java keyword, so we alias it
}