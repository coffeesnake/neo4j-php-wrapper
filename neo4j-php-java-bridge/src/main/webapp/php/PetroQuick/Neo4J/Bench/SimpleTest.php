<?php

require_once(PetroQuick_Neo4JGateway_Config::GATEWAY_URL);

class PetroQuick_Neo4J_Bench_SimpleTest {
    public static $NEO4J_DB;

    public static $neo;
    public static $indexer;
    public static $inserter;

    public static $prevTime = null;

    public static function printTime($message) {
        microtime(true);

        $currentTime = microtime(true);

        if (!isset(self::$prevTime))
            self::$prevTime = $currentTime;

        echo "\n$message" . "\ntime = " . sprintf("%F", ($currentTime - self::$prevTime)) . " sec\n";
        self::$prevTime = $currentTime;
    }

    public static function clearDir($path, $deleteSelf) {
        if (is_dir($path)) {
            $children = glob(rtrim($path, '/') . '/*');
            foreach ($children as $index => $child)
                self::clearDir($child, true);
        }

        if ($deleteSelf) {
            if (is_dir($path))
                rmdir($path);
            else
                unlink($path);
        }
    }

    public static function createChildren($parent, $childrenPerLevel, $levelsMore) {
        for ($i = 0; $i < $childrenPerLevel; $i++) {
            $properties = new java("java.util.HashMap");
            $properties->put("i", $i);
            $properties->put("level", $levelsMore + 1);
            $node = self::$inserter->createNode($properties);

            self::$inserter->createRelationship($parent, $node, java("org.neo4j.graphdb.DynamicRelationshipType")->withName("PARENT"), null);

            if ($levelsMore > 0)
                self::createChildren($node, $childrenPerLevel, $levelsMore - 1);
        }
    }

    public static function main() {
        echo "<pre>";

        self::printTime("initializing...");

        self::clearDir(self::$NEO4J_DB, false);

        self::$neo = new java("org.neo4j.kernel.EmbeddedGraphDatabase", self::$NEO4J_DB);
        self::$neo->shutdown();
        self::printTime("clean db created");

        self::$inserter = new java("org.neo4j.kernel.impl.batchinsert.BatchInserterImpl", self::$NEO4J_DB);
        self::createChildren(self::$inserter->getReferenceNode(), 10, 1);
        self::$inserter->shutdown();
        self::printTime("nodes created");

        self::$neo = new java("org.neo4j.kernel.EmbeddedGraphDatabase", self::$NEO4J_DB);

        for ($i = 0; $i < 10; $i++) {
            echo "\ntraversing through all nodes and counting depthSum...";
            $tx = self::$neo->beginTx();
            $traverser = self::$neo->getReferenceNode()->traverse(
                java('org.neo4j.graphdb.Traverser$Order')->BREADTH_FIRST,
                java('org.neo4j.graphdb.StopEvaluator')->END_OF_GRAPH,
                java('org.neo4j.graphdb.ReturnableEvaluator')->ALL_BUT_START_NODE,
                java('org.neo4j.graphdb.DynamicRelationshipType')->withName('PARENT'),
                java('org.neo4j.graphdb.Direction')->OUTGOING
            );

            $depthSum = 0;
            $nodes = $traverser->iterator();
            while (java_values($nodes->hasNext())) {
                $node = $nodes->next();
                $depthSum += intval(java_values($node->getProperty("level")));
            }

            $tx->finish();
            $tx->success();
            echo "\ndepthSum = $depthSum";
            self::printTime("done traversing");
        }

        self::$neo->shutdown();
                              
        echo "\nneo has been shut down";
        echo "</pre>";
    }


}