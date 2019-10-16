
## Installation

```bash
composer require yahiru/entity-factory
```

## Basic Usage

```php
<?php

require '/path/to/autoload.php';

use Faker\Generator as Faker;
use Yahiru\EntityFactory\AbstractFactory;

/**
 * @method FooEntity|FooEntity[] make(array $attributes = [])
 * @method FooEntity|FooEntity[] store(array $attributes = [])
 */
class FooEntityFactory extends AbstractFactory
{
    protected function class(): string
    {
        // return class name you want to create
        return FooEntity::class;
    }

    protected function default(Faker $faker): array
    {
        // define default values
        return [
            'name' => 'default name'
        ];
    }
}

class FooEntity
{
    private $name;
    
    public function getName(): string
    {
        return $this->name;
    }
}

$foo = FooEntityFactory::start()->make(); // FooEntity
echo $foo->getName(); // "default name"

$foos = FooEntityFactory::start()->times(10)->make();
echo count($foos); // 10
```

## Using Recipe

```php
<?php

class FooEntityFactory extends AbstractFactory
{
    // ...

    public function fooName(): self
    {
        // must call addRecipe
        $this->addRecipe([
            'name' => 'foo name'
        ]);

        return $this;
    }
}
$foo = FooEntityFactory::start()->fooName()->make();
echo $foo->getName(); // "foo name"
```

## With Faker

```php
<?php

use Faker\Generator as Faker;

class FooEntityFactory extends AbstractFactory
{
    protected $locale = 'ja_JP'; // default 'en_US'

    // ...

    public function fakerRecipe(): self
    {
        $this->addRecipe(function (Faker $faker, array $currentAttributes) {
            return [
                'name' => $faker->name
            ];
        });

        return $this;
    }
}
$foo = FooEntityFactory::start()->fakerRecipe()->make();
echo $foo->getName();
```

## Return custom collection class

```php
<?php

class FooEntityFactory extends AbstractFactory
{
    // ...

    // override this method
    protected function newCollection(array $entities)
    {
        return new FooCollection($entities);
    }
}

$fooCollection = FooEntityFactory::start()->times(5)->make();
echo get_class($fooCollection); // FooCollection
```

## To store an entity in database

```php
<?php

class FooEntityFactory extends AbstractFactory
{
    // ...

    // override this method
    protected function persistEntity($entity): void
    {
        /** @var FooEntity $entity */
        $pdo = new \PDO('mysql:host=localhost;dbname=foo_db', 'user', 'password');
        $stmt = $pdo->prepare('INSERT INTO foo (name) VALUES (:name)');
        $stmt->bindValue(':name', $entity->getName());
        $stmt->execute();
    }
}

// call store method when you want to store an entity in database
$foo = FooEntityFactory::start()->store();
```

## Check attribute keys

```php
<?php

class FooEntityFactory extends AbstractFactory
{
    // ...

    // override this method
    protected function fillable(): array
    {
        return ['name', 'email']; 
    }
}

try {
    FooEntityFactory::start()->make(['foo' => 'bar']);
} catch (EntityFactoryExceptionInterface $e) {
    echo $e->getMessage(); // "foo is not fillable."
} 
```
