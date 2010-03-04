package com.petroquick.neo4j;

import org.neo4j.graphdb.*;
import org.neo4j.index.IndexService;
import org.neo4j.index.Isolation;
import org.neo4j.index.lucene.LuceneFulltextIndexService;
import org.neo4j.index.lucene.LuceneFulltextQueryIndexService;
import org.neo4j.index.lucene.LuceneIndexService;
import org.neo4j.kernel.EmbeddedGraphDatabase;

/**
 * @author Philip Rud
 */
public class PHPGateway {
    private static String NEO4J_DB_PATH = "NEO4J_DB_PATH";

    private static PHPGateway instance;

    private EmbeddedGraphDatabase graphDb;
    private IndexService index;
    private IndexService fulltextIndex;

    private PHPGateway() {
        String databaseDir = System.getenv(NEO4J_DB_PATH);
        if (databaseDir == null) {
            System.out.println("Neo4J PHP Gateway :: " + NEO4J_DB_PATH + " environment variable is not set, assuming target/db");
            databaseDir = "target/db";
        }
        graphDb = new EmbeddedGraphDatabase(databaseDir);
        graphDb.enableRemoteShell();

        index = new LuceneIndexService(graphDb);
        index.setIsolation(Isolation.SAME_TX);
        
        fulltextIndex = new LuceneFulltextQueryIndexService(graphDb);
        fulltextIndex.setIsolation(Isolation.SAME_TX);

        System.out.println("Neo4J PHP Gateway :: initialized");
    }

    /**
     * The gateway instance should be obtained from the servlet context on the PHP side.
     *
     * @return gateway instance
     */
    static PHPGateway get() {
        if (instance == null)
            instance = new PHPGateway();

        return instance;
    }

    /*----- The only methods intended to be called over the PHP/Java bridge -----*/

    public EmbeddedGraphDatabase getGraphDb() {
        return graphDb;
    }

    public RelationshipType factoryRelationshipType(String name) {
        return RelationshipTypeWrapper.get(name);
    }

    public Transaction factoryTransaction() {
        return graphDb.beginTx();
    }

    public IndexService factoryIndex() {
        return index;
    }

    public IndexService factoryFulltextIndex() {
        return fulltextIndex;
    }

}
