<?php
declare(strict_types=1);

namespace Yahiru\EntityFactory\Tests;

use Faker\Generator;
use PHPUnit\Framework\TestCase;
use Yahiru\EntityFactory\Exception\InvalidAttributeException;
use Yahiru\EntityFactory\Exception\OutOfRangeException;

final class FactoryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testCanBuildDefaultValue()
    {
        $entity = FakeEntityFactory::start()->make();
        $this->assertSame('testing name', $entity->getName());
    }

    public function testCanBuildGivenValue()
    {
        $entity = FakeEntityFactory::start()->make([
            'name' => 'testing name2',
        ]);
        $this->assertSame('testing name2', $entity->getName());
    }

    public function testCanBuildByRecipe()
    {
        $entity = FakeEntityFactory::start()->foo()->make();
        $this->assertSame('foo', $entity->getName());
    }

    public function testCanBuildWithFaker()
    {
        $entity = FakeEntityFactory::start()->randomName()->make();
        $this->assertInstanceOf(FakeEntity::class, $entity);
    }

    public function testCanMultipleMake()
    {
        $entities = FakeEntityFactory::start()->times(2)->make();

        $this->assertTrue(is_array($entities));
        $this->assertSame(2, count($entities));
    }

    public function testCanMultipleStore()
    {
        $factory = new class extends FakeEntityFactory {
            protected function persistEntity($entity): void
            {
                //
            }
        };
        $entities = $factory->times(2)->make();

        $this->assertTrue(is_array($entities));
        $this->assertSame(2, count($entities));
    }

    public function testCanNotSetNegativeNumber()
    {
        $this->expectException(OutOfRangeException::class);
        FakeEntityFactory::start()->times(0);
    }

    public function testCanFill()
    {
        $factory = new class extends FakeEntityFactory {
            protected function fillable(): array
            {
                return ['name'];
            }
        };
        $entity = $factory->make([
            'name' => 'testing name'
        ]);
        $this->assertSame('testing name', $entity->getName());
    }

    public function testCanNotFill()
    {
        $factory = new class extends FakeEntityFactory {
            protected function fillable(): array
            {
                return ['name'];
            }
        };

        $this->expectException(InvalidAttributeException::class);
        $factory->make(['not_fillable' => 'testing']);
    }

    public function testReturnOriginalCollection()
    {
        $factory = new class extends FakeEntityFactory {
            protected function newCollection(array $entities)
            {
                return new FakeCollection($entities);
            }
        };

        $entities = $factory->times(2)->make();
        $this->assertInstanceOf(FakeCollection::class, $entities);
    }

    public function testCanAccessCurrentAttributes()
    {
        $factory = new class extends FakeEntityFactory {
            public $current;
            public function testingRecipe()
            {
                $this->addRecipe(function (Generator $faker, array $currentAttributes) {
                    $this->current = $currentAttributes;
                    return [];
                });
                return $this;
            }
        };
        $factory->testingRecipe()->make();

        $this->assertSame(['name' => 'testing name'], $factory->current);
    }
}
