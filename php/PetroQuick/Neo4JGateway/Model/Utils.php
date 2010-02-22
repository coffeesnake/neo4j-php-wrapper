<?php

/**
 * Valious helper methods for Neo4J.
 *
 * Padding related methods are intended to be used when one is going to index
 * the value by Neo4J indexer, so sorting and comparing work correctly.
 * Default padding is usually 9 to ensure the value will be correctly unwrapped
 * back to PHP integer on 32-bit systems.  
 */
class PetroQuick_Neo4JGateway_Model_Utils {

    /**
     * Pads integer so it will be correctly indexed by Neo4J indexer.
     *
     * @static
     * @param int $value
     * @param int $padding
     * @return string
     */
    public static function padUnsignedInt($value, $padding = 9) {
        $value = intval($value);
        assert($value > 0);
        
        return sprintf("%0{$padding}d", $value);
    }
}