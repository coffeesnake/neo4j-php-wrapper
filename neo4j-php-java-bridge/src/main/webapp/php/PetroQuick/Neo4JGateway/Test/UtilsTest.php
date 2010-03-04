<?php

/* ----- bootstrap config ----- */
set_include_path(get_include_path() . PATH_SEPARATOR . realpath(dirname(__FILE__) . '/../../../'));
require_once 'Zend/Loader/Autoloader.php';
Zend_Loader_Autoloader::getInstance()->registerNamespace('PetroQuick_');
/* ---------------------------- */

class PetroQuick_Neo4JGateway_Test_UtilsTest extends PHPUnit_Framework_TestCase {

    /**
     * Tests padInteger() method.
     */
    public function testPadInteger() {
        $paddedInt = PetroQuick_Neo4JGateway_Model_Utils::padUnsignedInt(10);
        $this->assertEquals($paddedInt, '0000000010', 'Padded wrong.');
        $paddedInt = PetroQuick_Neo4JGateway_Model_Utils::padUnsignedInt(10, 5);
        $this->assertEquals($paddedInt, '00010', 'Padded wrong.');

        $assertionFailed = false;
        try {
            $paddedInt = PetroQuick_Neo4JGateway_Model_Utils::padUnsignedInt(-10, 5);
        } catch (Exception $e) {
            $assertionFailed = true;
        }

        if (!$assertionFailed)
            $this->fail('Negatives should raise assertion error.');
    }

}