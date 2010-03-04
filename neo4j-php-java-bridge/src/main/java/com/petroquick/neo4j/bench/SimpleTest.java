package com.petroquick.neo4j.bench;

import org.neo4j.graphdb.*;
import org.neo4j.index.IndexService;
import org.neo4j.kernel.EmbeddedGraphDatabase;
import org.neo4j.kernel.impl.batchinsert.BatchInserter;
import org.neo4j.kernel.impl.batchinsert.BatchInserterImpl;

import java.io.File;
import java.util.HashMap;
import java.util.Map;

/**
 * @author Philip Rud
 */
public class SimpleTest {
    public static final String NEO4J_DB = "./db-j";

    public static EmbeddedGraphDatabase neo;
    public static IndexService indexer;
    public static BatchInserter inserter;

    public static Long prevTime = null;

    public static void printTime(String message) {
        long currentTime = System.nanoTime();

        if (prevTime == null)
            prevTime = currentTime;
                                        
        System.out.println(message + "\ntime = " + (currentTime - prevTime) / 1000000000.0 + " sec\n");
        prevTime = currentTime;
    }

    public static void clearDir(String path, boolean deleteSelf) {
        File file = new File(path);
        if (!file.exists())
            throw new RuntimeException("path not found");    

        if (file.isDirectory())
            for (String child: file.list())
                clearDir(path + "/" + child, true);

        if (deleteSelf)
            if (!file.delete())
                throw new RuntimeException("couldn't delete some files");
    }

    public static void createChildren(long parent, int childrenPerLevel, int levelsMore) {
        for (int i = 0; i < childrenPerLevel; i++) {
            Map<String, Object> properties = new HashMap<String, Object>();
            properties.put("i", i);
            properties.put("level", levelsMore + 1);
            long node = inserter.createNode(properties);

            inserter.createRelationship(parent, node, DynamicRelationshipType.withName("PARENT"), null);

            if (levelsMore > 0)
                createChildren(node, childrenPerLevel, levelsMore - 1);
        }
    }

    public static void main(String[] args) {
        clearDir(NEO4J_DB, false);
        printTime("initializing...");

        neo = new EmbeddedGraphDatabase(NEO4J_DB);
        neo.shutdown();
        printTime("clean db created");

        inserter = new BatchInserterImpl(NEO4J_DB);
        createChildren(inserter.getReferenceNode(), 100, 1);
        inserter.shutdown();
        printTime("nodes created");

        for (int i = 0; i < 10; i++) {
            neo = new EmbeddedGraphDatabase(NEO4J_DB);

            System.out.println("traversing through all nodes and counting depthSum...");
            Transaction tx = neo.beginTx();
            Traverser traverser = neo.getReferenceNode().traverse(
                    Traverser.Order.BREADTH_FIRST,
                    StopEvaluator.END_OF_GRAPH,
                    ReturnableEvaluator.ALL_BUT_START_NODE,
                    DynamicRelationshipType.withName("PARENT"),
                    Direction.OUTGOING
            );

            int depthSum = 0;
            for (Node node: traverser)
                depthSum += (Integer) node.getProperty("level");

            tx.finish();
            tx.success();
            System.out.println("depthSum = " + depthSum);
            printTime("done traversing");

            neo.shutdown();
        }
        
        System.out.println("neo has been shut down");
    }

}

