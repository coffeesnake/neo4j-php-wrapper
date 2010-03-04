<?php

/* ----- bootstrap config ----- */
set_include_path(get_include_path() . PATH_SEPARATOR . realpath(dirname(__FILE__) . '/../../../'));
require_once 'Zend/Loader/Autoloader.php';
Zend_Loader_Autoloader::getInstance()->registerNamespace('PetroQuick_');
/* ---------------------------- */

require_once(PetroQuick_Neo4JGateway_Config::GATEWAY_URL_TEST);

class PetroQuick_Neo4JGateway_Test_ModelTest extends PHPUnit_Framework_TestCase {
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
     * Tests getNodeName method with default $_nodeName and $_subrefNodeName
     * properties.
     */
    public function testGetNodesNames() {
        $userModel = new PetroQuick_Model_User();

        $this->assertEquals($userModel->getNodeName(), 'USER', 'getNodeName() returned wrong value.');
        $this->assertEquals($userModel->getSubrefNodeName(), 'USERS', 'getSubrefNodeName() returned wrong value.');

        $companyModel = new PetroQuick_Model_Company();
        
        $this->assertEquals($companyModel->getSubrefNodeName(), 'COMPANIES', 'getSubrefNodeName() returned wrong value.');
    }

    /**
     * Tests obtaining of the subreference node of the model.
     */
    public function testGetSubrefNode() {
        $userModel = new PetroQuick_Model_User();
        $subrefNode = $userModel->getSubrefNode();

        $this->assertNull($subrefNode, "subrefNode shouldn't exist until schema created");

        $userModel->ensureSchemaCreated();
        $subrefNode = $userModel->getSubrefNode();
        $this->assertNotNull($subrefNode, "subrefNode is null");
    }

    /**
     * Tests ensureSchemaCreated() method.
     */
    public function testEnsureSchemaCreated() {
        $userModel = new PetroQuick_Model_User();
        $subrefNode = $userModel->getSubrefNode();

        $this->assertNull($subrefNode, "subrefNode shouldn't exist until schema created");

        $userModel->ensureSchemaCreated();
        $subrefNode = $userModel->getSubrefNode();
        $this->assertNotNull($subrefNode, "subrefNode is null");
    }

    /**
     * Tests create() method.
     */
    public function testCreate() {
        $companyModel = new PetroQuick_Model_Company();
        $companyModel->ensureSchemaCreated();

        $node1 = $companyModel->create();

        $this->assertStringStartsWith($companyModel->getNodeName() . "::",
            java_values($node1->getProperty(PetroQuick_Model_Company::ID)));
        $this->assertEquals(java_values($node1->getProperty(PetroQuick_Model_Company::TYPE)), 'COMPANY');

        $node2 = $companyModel->create();

        $this->assertStringStartsWith($companyModel->getNodeName() . "::",
            java_values($node2->getProperty(PetroQuick_Model_Company::ID)));
        $this->assertEquals(java_values($node2->getProperty(PetroQuick_Model_Company::TYPE)), 'COMPANY');

        $node3 = $companyModel->create(array(
            'name' => 'Google',
            'type' => 'Internet',
            'stockId' => 'GOOG',
            'hq' => 'Mountain View, CA',
            'CEO' => 'Sergey Brin',
        ));

        $this->assertStringStartsWith($companyModel->getNodeName() . "::", 
            java_values($node3->getProperty(PetroQuick_Model_Company::ID)));
        $this->assertEquals(java_values($node3->getProperty('name')), 'Google');
        $this->assertTrue(java_values($node3->getProperty('name')->equals('Google')));

        $tx = $companyModel->getGateway()->factoryTransaction();
        $node = $companyModel->getIndex()->getSingleNode(PetroQuick_Model_Company::ID,
                $node1->getProperty(PetroQuick_Model_Company::ID));
        $tx->success();
        $tx->finish();
        
        $this->assertTrue(java_values($node->equals($node1)), 'node found in index is different');

        $this->assertNotEquals($node1->getProperty(PetroQuick_Model_Company::ID),
                $node2->getProperty(PetroQuick_Model_Company::ID));
        $this->assertNotEquals($node1->getProperty(PetroQuick_Model_Company::ID),
                $node3->getProperty(PetroQuick_Model_Company::ID));
        $this->assertNotEquals($node3->getProperty(PetroQuick_Model_Company::ID),
                $node2->getProperty(PetroQuick_Model_Company::ID));
    }

    /**
     * Tests get() method.
     */
    public function testGet() {
        $companyModel = new PetroQuick_Model_Company();
        $companyModel->ensureSchemaCreated();

        $node = $companyModel->create();
        $id = $node->getProperty(PetroQuick_Model_Company::ID);

        $nodeFound = $companyModel->get($id);
        $this->assertTrue(java_values($nodeFound->equals($node)));

        $id .= '::something';
        $nodeFound = $companyModel->get($id);
        $this->assertNull(java_values($nodeFound));
    }

    /**
     * Tests delete() method.
     */
    public function testDelete() {
        $companyModel = new PetroQuick_Model_Company();
        $companyModel->ensureSchemaCreated();

        $node = $companyModel->create();
        $id = $node->getProperty(PetroQuick_Model_Company::ID);

        $node = $companyModel->get($id);
        $this->assertNotNull(java_values($node));

        $companyModel->delete($node);

        $node = $companyModel->get($id);
        $this->assertNull(java_values($node));

        $tx = $companyModel->getGateway()->factoryTransaction();
        $node = $companyModel->getIndex()->getSingleNode(PetroQuick_Model_Company::ID, $id);
        $tx->success();
        $tx->finish();

        $this->assertNull(java_values($node));
    }

    /**
     * Tests update() method.
     */
    public function testUpdate() {
        $companyModel = new PetroQuick_Model_Company();
        $companyModel->ensureSchemaCreated();

        $node = $companyModel->create(array(
            'name' => 'Google',
            'type' => 'Internet',
        ));

        $this->assertEquals(java_values($node->getProperty('name')), 'Google');
        $this->assertEquals(java_values($node->getProperty('type')), 'Internet');

        $companyModel->update($node, array(
            'name' => 'Yahoo',
            'type' => null,
        ));

        $this->assertEquals(java_values($node->getProperty('name')), 'Yahoo');
        $this->assertFalse(java_values($node->hasProperty('type')));
    }
}