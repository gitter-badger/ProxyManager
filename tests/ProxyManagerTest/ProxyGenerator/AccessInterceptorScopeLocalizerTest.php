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

namespace ProxyManagerTest\ProxyGenerator;

use ProxyManager\Proxy\AccessInterceptorInterface;
use ProxyManager\ProxyGenerator\AccessInterceptorScopeLocalizerGenerator;
use ReflectionClass;

/**
 * Tests for {@see \ProxyManager\ProxyGenerator\AccessInterceptorScopeLocalizerGenerator}
 *
 * @author Marco Pivetta <ocramius@gmail.com>
 * @license MIT
 *
 * @covers \ProxyManager\ProxyGenerator\AccessInterceptorScopeLocalizerGenerator
 * @group Coverage
 */
class AccessInterceptorScopeLocalizerTest extends AbstractProxyGeneratorTest
{
    /**
     * @dataProvider getTestedImplementations
     *
     * {@inheritDoc}
     */
    public function testGeneratesValidCode($className)
    {
        $reflectionClass = new ReflectionClass($className);

        if ($reflectionClass->isInterface()) {
            // @todo interfaces *may* be proxied by deferring property localization to the constructor (no hardcoding)
            $this->setExpectedException('ProxyManager\Exception\InvalidProxiedClassException');

            parent::testGeneratesValidCode($className);

            return;
        }

        parent::testGeneratesValidCode($className);
    }

    /**
     * {@inheritDoc}
     */
    protected function getProxyGenerator()
    {
        return new AccessInterceptorScopeLocalizerGenerator();
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedImplementedInterfaces()
    {
        return [AccessInterceptorInterface::class];
    }
}
