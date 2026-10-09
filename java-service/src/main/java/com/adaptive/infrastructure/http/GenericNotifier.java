package com.adaptive.infrastructure.http;

import com.adaptive.infrastructure.json.JsonMapper;

import java.net.URI;
import java.net.http.HttpClient;
import java.net.http.HttpRequest;
import java.net.http.HttpResponse;
import java.util.Map;

/**
 * Tells CI4 an action happened. Never throws.
 */
public class GenericNotifier {

    private final String baseUrl;
    private final HttpClient http = HttpClient.newHttpClient();

    public GenericNotifier(String baseUrl) { this.baseUrl = baseUrl; }

    public void notify(String entity, String action, Object id, Object payload) {
        try {
            String body = JsonMapper.GSON.toJson(Map.of(
                    "entity",  entity,
                    "action",  action,
                    "id",      id == null ? "" : id,
                    "payload", payload == null ? Map.of() : payload
            ));
            HttpRequest req = HttpRequest.newBuilder()
                    .uri(URI.create(baseUrl + "/api/logs"))
                    .header("Content-Type", "application/json")
                    .POST(HttpRequest.BodyPublishers.ofString(body))
                    .build();
            HttpResponse<String> res = http.send(req, HttpResponse.BodyHandlers.ofString());
            System.out.println("[notifier] CI4 -> " + res.statusCode());
        } catch (Exception e) {
            System.err.println("[notifier] CI4 unreachable: " + e.getMessage());
        }
    }
}