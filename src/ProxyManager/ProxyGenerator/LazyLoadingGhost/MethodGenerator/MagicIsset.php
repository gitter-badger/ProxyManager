<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license.
 */

namespace ProxyManager\ProxyGenerator\LazyLoadingGhost\MethodGenerator;

use ProxyManager\Generator\MagicMethodGenerator;
use ProxyManager\Generator\ParameterGenerator;
use ProxyManager\ProxyGenerator\LazyLoadingGhost\PropertyGenerator\PrivatePropertiesMap;
use ProxyManager\ProxyGenerator\LazyLoadingGhost\PropertyGenerator\ProtectedPropertiesMap;
use ProxyManager\ProxyGenerator\PropertyGenerator\PublicPropertiesMap;
use ProxyManager\ProxyGenerator\Util\PublicScopeSimulator;
use ReflectionClass;
use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\PropertyGenerator;

/**
 * Magic `__isset` method for lazy loading ghost objects
 *
 * @author Marco Pivetta <ocramius@gmail.com>
 * @license MIT
 */
class MagicIsset extends MagicMethodGenerator
{
    /**
     * @param ReflectionClass        $originalClass
     * @param PropertyGenerator      $initializerProperty
     * @param MethodGenerator        $callInitializer
     * @param PublicPropertiesMap    $publicProperties
     * @param ProtectedPropertiesMap $protectedProperties
     * @param PrivatePropertiesMap   $privateProperties
     */
    public function __construct(
        ReflectionClass $originalClass,
        PropertyGenerator $initializerProperty,
        MethodGenerator $callInitializer,
        PublicPropertiesMap $publicProperties,
        ProtectedPropertiesMap $protectedProperties,
        PrivatePropertiesMap $privateProperties
    ) {
        parent::__construct($originalClass, '__isset', [new ParameterGenerator('name')]);

        $override   = $originalClass->hasMethod('__isset');

        $this->setDocblock(($override ? "{@inheritDoc}\n" : '') . '@param string $name');

        $callParentTemplate = <<<'PHP'
if (isset(self::$%s[$name])) {
    return isset($this->$name);
}

if (isset(self::$%s[$name])) {
    // check protected property access via compatible class
    $callers      = debug_backtrace(\DEBUG_BACKTRACE_PROVIDE_OBJECT, 2);
    $caller       = isset($callers[1]) ? $callers[1] : [];
    $object       = isset($caller['object']) ? $caller['object'] : '';
    $expectedType = self::$%s[$name];

    if ($object instanceof $expectedType) {
        return isset($this->$name);
    }

    $class = isset($caller['class']) ? $caller['class'] : '';

    if ($class === $expectedType || is_subclass_of($class, $expectedType)) {
        return isset($this->$name);
    }
} else {
    // check private property access via same class
    $callers = debug_backtrace(\DEBUG_BACKTRACE_PROVIDE_OBJECT, 2);
    $caller  = isset($callers[1]) ? $callers[1] : [];
    $class   = isset($caller['class']) ? $caller['class'] : '';

    static $accessorCache = [];

    if (isset(self::$%s[$name][$class])) {
        $cacheKey = $class . '#' . $name;
        $accessor = isset($accessorCache[$cacheKey])
            ? $accessorCache[$cacheKey]
            : $accessorCache[$cacheKey] = \Closure::bind(function ($instance) use ($name) {
                return isset($instance->$name);
            }, null, $class);

        return $accessor($this);
    }

    if ('ReflectionProperty' === $class) {
        $tmpClass = key(self::$%s[$name]);
        $cacheKey = $tmpClass . '#' . $name;
        $accessor = isset($accessorCache[$cacheKey])
            ? $accessorCache[$cacheKey]
            : $accessorCache[$cacheKey] = \Closure::bind(function ($instance) use ($name) {
                return isset($instance->$name);
            }, null, $tmpClass);

        return $accessor($this);
    }
}

PHP;

        $callParent = sprintf(
            $callParentTemplate,
            $publicProperties->getName(),
            $protectedProperties->getName(),
            $protectedProperties->getName(),
            $privateProperties->getName(),
            $privateProperties->getName(),
            $privateProperties->getName()
        );

        if ($override) {
            $callParent .= 'return parent::__isset($name);';
        } else {
            $callParent .= PublicScopeSimulator::getPublicAccessSimulationCode(
                PublicScopeSimulator::OPERATION_ISSET,
                'name'
            );
        }

        $this->setBody(
            '$this->' . $initializerProperty->getName() . ' && $this->' . $callInitializer->getName()
            . '(\'__isset\', array(\'name\' => $name));'
            . "\n\n" . $callParent
        );
    }
}
