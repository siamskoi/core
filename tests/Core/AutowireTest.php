<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Tests\Core;

use PHPUnit\Framework\TestCase;
use Spiral\Core\Container;
use Spiral\Core\Tests\Fixtures\Bucket;
use Spiral\Core\Tests\Fixtures\DependedClass;
use Spiral\Core\Tests\Fixtures\ExtendedSample;
use Spiral\Core\Tests\Fixtures\SampleClass;
use Spiral\Core\Tests\Fixtures\SoftDependedClass;
use Spiral\Core\Tests\Fixtures\TypedClass;

/**
 * The most fun test.
 */
class AutowireTest extends TestCase
{
    public function testSimple(): void
    {
        $container = new Container();

        $this->assertInstanceOf(SampleClass::class, $container->get(SampleClass::class));
        $this->assertInstanceOf(SampleClass::class, $container->make(SampleClass::class, []));
    }

    public function testGet(): void
    {
        $container = new Container();

        $container->bind(SampleClass::class, ExtendedSample::class);
        $this->assertInstanceOf(ExtendedSample::class, $container->get(SampleClass::class));
    }

    public function testMake(): void
    {
        $container = new Container();

        $container->bind(SampleClass::class, ExtendedSample::class);
        $this->assertInstanceOf(ExtendedSample::class, $container->make(SampleClass::class, []));
    }

    /**
     * @expectedException \Spiral\Core\Exception\Container\ArgumentException
     * @expectedExceptionMessage Unable to resolve 'name' argument in
     *                           'Spiral\Tests\Fixtures\Bucket::__construct'
     */
    public function testArgumentException(): void
    {
        $container = new Container();

        $bucket = $container->get(Bucket::class);
    }

    public function testDefaultValue(): void
    {
        $container = new Container();

        $bucket = $container->make(Bucket::class, ['name' => 'abc']);

        $this->assertInstanceOf(Bucket::class, $bucket);
        $this->assertSame('abc', $bucket->getName());
        $this->assertSame('default-data', $bucket->getData());
    }

    public function testCascade(): void
    {
        $container = new Container();

        $object = $container->make(DependedClass::class, [
            'name' => 'some-name'
        ]);

        $this->assertInstanceOf(DependedClass::class, $object);
        $this->assertSame('some-name', $object->getName());
        $this->assertInstanceOf(SampleClass::class, $object->getSample());
    }

    public function testRemoveBinding(): void
    {
        $container = new Container();

        $container->bind('alias', $this);

        $this->assertTrue($container->has('alias'));
        $this->assertTrue($container->hasInstance('alias'));

        $this->assertNotEmpty($container->getBindings());

        $container->removeBinding('alias');

        $this->assertFalse($container->has('alias'));
        $this->assertFalse($container->hasInstance('alias'));

        $container->bind('alias-b', 'alias');
        $this->assertFalse($container->hasInstance('alias-b'));
    }

    public function testCascadeFollowBindings(): void
    {
        $container = new Container();

        $container->bind(SampleClass::class, ExtendedSample::class);

        $object = $container->make(DependedClass::class, [
            'name' => 'some-name'
        ]);

        $this->assertInstanceOf(DependedClass::class, $object);
        $this->assertSame('some-name', $object->getName());
        $this->assertInstanceOf(ExtendedSample::class, $object->getSample());
    }

    /**
     * @expectedException \Spiral\Core\Exception\Container\NotFoundException
     * @expectedExceptionMessage Undefined class or binding 'WrongClass'
     */
    public function testAutowireException(): void
    {
        $container = new Container();

        $container->bind(SampleClass::class, \WrongClass::class);
        $container->make(DependedClass::class, [
            'name' => 'some-name'
        ]);
    }

    /**
     * See line 218 in Container, this behaviour allows system to pass on classes which can not be
     * automatically constructured or missing but ONLY when default value is set to NULL.
     */
    public function testAutowireWithDefaultOnWrongClass(): void
    {
        $container = new Container();

        $container->bind(SampleClass::class, \WrongClass::class);

        $object = $container->make(SoftDependedClass::class, [
            'name' => 'some-name'
        ]);

        $this->assertInstanceOf(SoftDependedClass::class, $object);
        $this->assertSame('some-name', $object->getName());
        $this->assertNull($object->getSample());
    }

    public function testAutowireTypecastingAndValidating(): void
    {
        $container = new Container();

        $object = $container->make(TypedClass::class, [
            'string' => 'string',
            'int'    => 123,
            'float'  => 123.00,
            'bool'   => true
        ]);

        $this->assertInstanceOf(TypedClass::class, $object);

        $container = new Container();

        $object = $container->make(TypedClass::class, [
            'string' => 'string',
            'int'    => '123',
            'float'  => '123.00',
            'bool'   => 1
        ]);

        $this->assertInstanceOf(TypedClass::class, $object);

        $container = new Container();

        $object = $container->make(TypedClass::class, [
            'string' => 'string',
            'int'    => 123,
            'float'  => 123.00,
            'bool'   => 0
        ]);

        $this->assertInstanceOf(TypedClass::class, $object);
    }

    /**
     * @expectedException \Spiral\Core\Exception\Container\ArgumentException
     * @expectedExceptionMessage  Unable to resolve 'string' argument in
     *                            'Spiral\Tests\Core\Fixtures\TypedClass::__construct'
     */
    public function testAutowireTypecastingAndValidatingWrongString(): void
    {
        $container = new Container();

        $object = $container->make(TypedClass::class, [
            'string' => null,
            'int'    => 123,
            'float'  => 123.00,
            'bool'   => true
        ]);

        $this->assertInstanceOf(TypedClass::class, $object);
    }

    /**
     * @expectedException \Spiral\Core\Exception\Container\ArgumentException
     * @expectedExceptionMessage  Unable to resolve 'int' argument in
     *                            'Spiral\Tests\Core\Fixtures\TypedClass::__construct'
     */
    public function testAutowireTypecastingAndValidatingWrongInt(): void
    {
        $container = new Container();

        $object = $container->make(TypedClass::class, [
            'string' => '',
            'int'    => 'yo!',
            'float'  => 123.00,
            'bool'   => true
        ]);

        $this->assertInstanceOf(TypedClass::class, $object);
    }

    /**
     * @expectedException \Spiral\Core\Exception\Container\ArgumentException
     * @expectedExceptionMessage  Unable to resolve 'float' argument in
     *                            'Spiral\Tests\Core\Fixtures\TypedClass::__construct'
     */
    public function testAutowireTypecastingAndValidatingWrongFloat(): void
    {
        $container = new Container();

        $object = $container->make(TypedClass::class, [
            'string' => '',
            'int'    => 123,
            'float'  => '~',
            'bool'   => true
        ]);

        $this->assertInstanceOf(TypedClass::class, $object);
    }

    /**
     * @expectedException \Spiral\Core\Exception\Container\ArgumentException
     * @expectedExceptionMessage  Unable to resolve 'bool' argument in
     *                            'Spiral\Tests\Core\Fixtures\TypedClass::__construct'
     */
    public function testAutowireTypecastingAndValidatingWrongBool(): void
    {
        $container = new Container();

        $object = $container->make(TypedClass::class, [
            'string' => '',
            'int'    => 123,
            'float'  => 1.00,
            'bool'   => 'true'
        ]);

        $this->assertInstanceOf(TypedClass::class, $object);
    }

    /**
     * @expectedException \Spiral\Core\Exception\Container\ArgumentException
     * @expectedExceptionMessage  Unable to resolve 'array' argument in
     *                            'Spiral\Tests\Core\Fixtures\TypedClass::__construct'
     */
    public function testAutowireTypecastingAndValidatingWrongArray(): void
    {
        $container = new Container();

        $object = $container->make(TypedClass::class, [
            'string' => '',
            'int'    => 123,
            'float'  => 1.00,
            'bool'   => true,
            'array'  => 'not array'
        ]);

        $this->assertInstanceOf(TypedClass::class, $object);
    }

    public function testAutowireOptionalArray(): void
    {
        $container = new Container();

        $object = $container->make(TypedClass::class, [
            'string' => '',
            'int'    => 123,
            'float'  => 1.00,
            'bool'   => true
        ]);

        $this->assertInstanceOf(TypedClass::class, $object);
    }

    public function testAutowireOptionalString(): void
    {
        $container = new Container();

        $object = $container->make(TypedClass::class, [
            'string' => '',
            'int'    => 123,
            'float'  => 1.00,
            'bool'   => true,
            'pong'   => null
        ]);

        $this->assertInstanceOf(TypedClass::class, $object);
    }

    public function testAutowireDelegate(): void
    {
        $container = new Container();

        $container->bind('sample-binding', $s = new SampleClass());

        $object = $container->make(SoftDependedClass::class, [
            'name'   => 'some-name',
            'sample' => new Container\Autowire('sample-binding')
        ]);

        $this->assertSame($s, $object->getSample());
    }

    public function testSerializeAutowire(): void
    {
        $wire = new Container\Autowire('sample-binding', ['a' => new Container\Autowire('b')]);

        $wireb = unserialize(serialize($wire));

        $this->assertEquals($wire, $wireb);
    }

    public function testBingToAutowire(): void
    {
        $container = new Container();
        $container->bind('abc', new Container\Autowire(SoftDependedClass::class, [
            'name' => 'Fixed'
        ]));

        /**
         * @var SoftDependedClass $abc
         */
        $abc = $container->get('abc');

        $this->assertSame('Fixed', $abc->getName());
    }

    public function testGetAutowire(): void
    {
        $container = new Container();

        /**
         * @var SoftDependedClass $abc
         */
        $abc = $container->get(new Container\Autowire(SoftDependedClass::class, [
            'name' => 'Fixed'
        ]));

        $this->assertSame('Fixed', $abc->getName());
    }

    public function testBingToAutowireWithParameters(): void
    {
        $container = new Container();
        $container->bind('abc', new Container\Autowire(SoftDependedClass::class, [
            'name' => 'Fixed'
        ]));

        /**
         * @var SoftDependedClass $abc
         */
        $abc = $container->make('abc', ['name' => 'Overwritten']);

        $this->assertSame('Overwritten', $abc->getName());
    }

    public function testSerialize(): void
    {
        $a = new Container\Autowire(SoftDependedClass::class, [
            'name' => 'Fixed'
        ]);

        $b = Container\Autowire::__set_state([
            'alias'      => SoftDependedClass::class,
            'parameters' => ['name' => 'Fixed']
        ]);
        $this->assertEquals($a, $b);
    }
}
