package com.adaptive.infrastructure.bootstrap;

import com.adaptive.infrastructure.persistence.SchemaGenerator;

import jakarta.servlet.ServletContextEvent;
import jakarta.servlet.ServletContextListener;
import jakarta.servlet.annotation.WebListener;

/** Runs once when the webapp starts. */
@WebListener
public class BootstrapListener implements ServletContextListener {

    @Override
    public void contextInitialized(ServletContextEvent sce) {
        try {
            SchemaGenerator.generateAll();
            System.out.println("[bootstrap] schema ready");
        } catch (RuntimeException e) {
            System.err.println("[bootstrap] FAILED: " + e.getMessage());
            throw e;   // block startup so you notice immediately
        }
    }
}