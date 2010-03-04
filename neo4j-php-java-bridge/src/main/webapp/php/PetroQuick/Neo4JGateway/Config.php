<?php

interface PetroQuick_Neo4JGateway_Config {
    const DBSCHEMA_NAMESPACE = 'PetroQuick_Model';

    const GATEWAY_URL = 'http://localhost:8080/java/Java.inc';
    const GATEWAY_URL_TEST = 'http://localhost:8080/java/Java.inc';
    
    const GATEWAY_CONTEXT_PARAM = 'NEO4J_PHPGATEWAY_INSTANCE';
}