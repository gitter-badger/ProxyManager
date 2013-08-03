--TEST--
Verifies that generated access interceptors disallow private property direct isset check
--FILE--
<?php

require_once __DIR__ . '/init.php';

class Kitchen
{
    private $sweets;

    /** Defined to force magic methods generation */
    public $accessible;
}

$factory = new \ProxyManager\Factory\AccessInterceptorValueHolderFactory($configuration);

$proxy = $factory->createProxy(new Kitchen());

isset($proxy->sweets);
?>
--EXPECTF--
Fatal error: Cannot access private property %s::$sweets in %s on line %d