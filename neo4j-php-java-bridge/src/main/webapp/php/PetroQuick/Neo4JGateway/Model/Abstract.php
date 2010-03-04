<?php

require_once(PetroQuick_Neo4JGateway_Config::GATEWAY_URL);

/**
 * Base class for Neo4J models.
 */
abstract class PetroQuick_Neo4JGateway_Model_Abstract {
    const ID = '_id';
    const TYPE = '_type';

    protected $_subrefNodeName = null;
    protected $_nodeName = null;

    protected $_gateway = null;
    protected $_neo = null;
    protected $_index = null;
    protected $_fulltextIndex = null;

    protected $DIRECTION = null;

    private $_subrefNode = null;

    public function __construct() {
        $this->_gateway = java_context()->getServletContext()->getAttribute(
            PetroQuick_Neo4JGateway_Config::GATEWAY_CONTEXT_PARAM
        );
        $this->_neo = $this->_gateway->getGraphDb();
        $this->_index = $this->_gateway->factoryIndex();
        $this->_fulltextIndex = $this->_gateway->factoryFulltextIndex();

        $this->DIRECTION = java('org.neo4j.graphdb.Direction');

        if (empty($this->_subrefNodeName)) {
            $parts = explode('_', get_class($this));
            $this->_subrefNodeName = strtoupper($parts[count($parts) - 1]) . 'S';
        }
        if (empty($this->_nodeName)) {
            $parts = explode('_', get_class($this));
            $this->_nodeName = strtoupper($parts[count($parts) - 1]);
        }
    }

    /**
     * Populates a node with properties array. Should be run in existing transaction.
     *
     * @access private
     * @param  $node object - org.neo4j.graphdb.Node
     * @param  $data array
     * @return void
     */
    private function _populateData($node, $data) {
        foreach ($data as $key => $value) {
            if (isset($value)) {
                $node->setProperty($key, $value);
            } else {
                $node->removeProperty($key);
            }
        }
    }

    /**
     * Returns subreference node for this model (or null if it doesn't exist).
     *
     * @access private
     * @return object - org.neo4j.graphdb.Node
     */
    public function getSubrefNode() {
        if ($this->_subrefNode == null) {
            $tx = $this->getGateway()->factoryTransaction();
            try {
                $relType = $this->getGateway()->factoryRelationshipType($this->getSubrefNodeName());
                $rel = $this->getNeo()->getReferenceNode()->getSingleRelationship($relType, $this->DIRECTION->OUTGOING);

                if (!java_is_null($rel))
                    $this->_subrefNode = $rel->getEndNode();

                $tx->finish();
            } catch (Exception $e) {
                java_last_exception_get()->printStackTrace();
                $tx->failure();
            }
            $tx->finish();
        }

        return $this->_subrefNode;
    }

    /**
     * Creates and sets up the reference node for the model if it doesn't exist yet.
     *
     * @return void
     */
    public function ensureSchemaCreated() {
        $subrefNode = $this->getSubrefNode();
        if ($subrefNode == null) {
            $tx = $this->getGateway()->factoryTransaction();
            try {
                $refNode = $this->getNeo()->getReferenceNode();
                $subrefNode = $this->getNeo()->createNode();
                $relType = $this->getGateway()->factoryRelationshipType($this->getSubrefNodeName());
                $refNode->createRelationshipTo($subrefNode, $relType);
                
                $tx->success();
            } catch (Exception $e) {
                java_last_exception_get()->printStackTrace();
                $tx->failure();
            }
            $tx->finish();
        }
    }

    /**
     * Create a new node of this model.
     *
     * @return object - org.neo4j.graphdb.Node
     */
    public function create($data = null) {
        $node = null;

        $tx = $this->getGateway()->factoryTransaction();
        try {
            $subrefNode = $this->getSubrefNode();
            $node = $this->getNeo()->createNode();
            $relType = $this->getGateway()->factoryRelationshipType($this->getNodeName());
            $subrefNode->createRelationshipTo($node, $relType);

            $id = uniqid($this->getNodeName() . '::', true); 

            $node->setProperty(self::ID, $id);
            $node->setProperty(self::TYPE, $this->getNodeName());

            $this->getIndex()->index($node, self::TYPE, $this->getNodeName());
            $this->getIndex()->index($node, self::ID, $id);

            if (isset($data))
                $this->_populateData($node, $data);

            $tx->success();
        } catch (Exception $e) {
            java_last_exception_get()->printStackTrace();
            $tx->failure();
        }
        $tx->finish();

        return $node;
    }

    /**
     * Get single node by its Id.
     *
     * @param  $nodeId
     * @return object - org.neo4j.graphdb.Node
     */
    public function get($nodeId) {
        $node = null;

        $tx = $this->getGateway()->factoryTransaction();
        try {
            $node = $this->getIndex()->getSingleNode(PetroQuick_Model_Company::ID, $nodeId);
            $tx->success();
        } catch (Exception $e) {
            java_last_exception_get()->printStackTrace();
            $tx->failure();
        }
        $tx->finish();

        return $node;
    }

    /**
     * Deletes the node and it's obligatory system indexes (ID and TYPE).
     *
     * @param $node - org.neo4j.graphdb.Node 
     */
    public function delete($node) {
        $tx = $this->getGateway()->factoryTransaction();
        try {
            $this->getIndex()->removeIndex($node, self::ID, $node->getProperty(self::ID));
            $this->getIndex()->removeIndex($node, self::TYPE, $node->getProperty(self::TYPE));

            $rels = $node->getRelationships();
            while (java_values($rels->hasNext()))
                $rels->next()->delete();

            $node->delete();
            
            $tx->success();
        } catch (Exception $e) {
            java_last_exception_get()->printStackTrace();
            $tx->failure();
        }
        $tx->finish();
    }

    /**
     * Updates a node with a set of properties. A property value may be set to null to remove
     * corresponding property. 
     *
     * @param  $node       - org.neo4j.graphdb.Node
     * @param  $data array - properties
     */
    public function update($node, $data) {
        $tx = $this->getGateway()->factoryTransaction();
        try {
            $this->_populateData($node, $data);

            $tx->success();
        } catch (Exception $e) {
            java_last_exception_get()->printStackTrace();
            $tx->failure();
        }
        $tx->finish();
    }

    public function getGateway() {
        return $this->_gateway;
    }

    public function getNeo() {
        return $this->_neo;
    }

    public function getIndex() {
        return $this->_index;
    }

    public function getFulltextIndex() {
        return $this->_fulltextIndex;
    }

    public function getNodeName() {
        return $this->_nodeName;
    }

    public function getSubrefNodeName() {
        return $this->_subrefNodeName;
    }
}