package com.petroquick.neo4j;

import javax.servlet.ServletContextEvent;
import javax.servlet.ServletContextListener;

/**
 * @author Philip Rud
 */
public class Neo4JGatewayListener implements ServletContextListener {

    public static final String NEO4J_INSTANCE = "NEO4J_PHPGATEWAY_INSTANCE";

    public void contextInitialized(ServletContextEvent servletContextEvent) {
        servletContextEvent.getServletContext().setAttribute(NEO4J_INSTANCE, com.petroquick.neo4j.PHPGateway.get());
    }

    public void contextDestroyed(ServletContextEvent servletContextEvent) {
        System.out.println("Neo4J PHP Gateway :: shutting down");
        PHPGateway gateway = (PHPGateway) servletContextEvent.getServletContext().getAttribute(NEO4J_INSTANCE);
        gateway.factoryIndex().shutdown();
        gateway.factoryFulltextIndex().shutdown();        
        gateway.getGraphDb().shutdown();
    }
}
