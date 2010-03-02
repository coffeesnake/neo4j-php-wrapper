<?php

    /* ----- bootstrap config ----- */
    set_include_path(get_include_path() . PATH_SEPARATOR . realpath(dirname(__FILE__)));
    require_once 'Zend/Loader/Autoloader.php';
    Zend_Loader_Autoloader::getInstance()->registerNamespace('PetroQuick_');
    /* ---------------------------- */

    set_time_limit(0);

    PetroQuick_Neo4J_Bench_SimpleTest::$NEO4J_DB = realpath(dirname(__FILE__) . '/../db-p');
    PetroQuick_Neo4J_Bench_SimpleTest::main();
