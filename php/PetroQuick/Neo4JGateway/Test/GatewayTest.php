<?php

/* ----- bootstrap config ----- */
set_include_path(get_include_path() . PATH_SEPARATOR . realpath(dirname(__FILE__) . '/../../../'));
require_once 'Zend/Loader/Autoloader.php';
Zend_Loader_Autoloader::getInstance()->registerNamespace('PetroQuick_');
/* ---------------------------- */

require_once(PetroQuick_Neo4JGateway_Config::GATEWAY_URL_TEST);

class PetroQuick_Neo4JGateway_Test_GatewayTest extends PHPUnit_Framework_TestCase {
    private $_neoGateway;

    private function _cleanupDb() {
        $tx = $this->_neoGateway->factoryTransaction();

        try {
            $nodes = $this->_neoGateway->getGraphDb()->getAllNodes()->iterator();
            while (java_values($nodes->hasNext())) {
                $node = $nodes->next();

                $nodeRels = $node->getRelationships();
                while (java_values($nodeRels->hasNext()))
                    $nodeRels->next()->delete();

                if (!java_values($node->equals($this->_neoGateway->getGraphDb()->getReferenceNode())))
                    $node->delete();
            }

            $tx->success();
        } catch (Exception $e) {
            java_last_exception_get()->printStackTrace();
            $tx->failure();
        }
        $tx->finish();
    }

    protected function setUp() {
        $this->_neoGateway = java_context()->getServletContext()->getAttribute(
            PetroQuick_Neo4JGateway_Config::GATEWAY_CONTEXT_PARAM
        );
        $this->_cleanupDb();
    }

    /**
     * Tests obtaining of the gateway instance.
     */
    public function testObtainGateway() {
        $this->assertContains('PHPGateway', java_inspect($this->_neoGateway), 'PHPGateway object not returned.');
    }

    /**
     * Tests getting the graphDb instance.
     */
    public function testGetGraphDb() {
        $this->assertNotNull($this->_neoGateway->getGraphDb(), 'getGraphDb() returned null.');
    }

    /**
     * Tests whether the database is really empty after the _cleanupDb method.
     */
    public function testCleanupDb() {
        $tx = $this->_neoGateway->factoryTransaction();

        $nodes = $this->_neoGateway->getGraphDb()->getAllNodes()->iterator();

        $nodesCount = 0;
        while (java_values($nodes->hasNext())) {
            $nodesCount++;
            $nodes->next();
        }

        $tx->success();
        $tx->finish();

        $this->assertEquals($nodesCount, 1, 'Database is not empty or reference node had been removed.');
    }

}