<?php
declare(strict_types=1);

namespace Yahiru\EntityFactory;

use Faker\Generator as Faker;
use ReflectionClass;
use Yahiru\EntityFactory\Exception\InvalidAttributeException;
use Yahiru\EntityFactory\Exception\LogicException;
use Yahiru\EntityFactory\Exception\OutOfRangeException;

abstract class AbstractFactory
{
    protected $locale = 'en_US';
    private $times = 1;
    /** @var Recipe[] */
    private $recipes = [];
    private $cachedFillable = [];

    /**
     * @param mixed ...$args
     * @return static
     */
    public static function start(...$args)
    {
        return new static(...$args);
    }

    /**
     * @param int $times
     * @return static
     */
    final public function times(int $times): self
    {
        $this->setTimes($times);
        return $this;
    }

    final protected function setTimes(int $times): void
    {
        if ($times <= 0) {
            throw new OutOfRangeException('times must be positive number. but given ' . $times);
        }

        $this->times = $times;
    }

    final public function store(array $attributes = [])
    {
        $entities = $this->makeEntities($attributes);

        if (!$this->shouldReturnMultiple()) {
            $this->persistEntity($entities[0]);
            return $entities[0];
        }

        foreach ($entities as $entity) {
            $this->persistEntity($entity);
        }

        return $this->newCollection($entities);
    }

    private function makeEntities(array $attributes): array
    {
        $faker = $this->getFaker();
        $entities = [];
        $times = $this->times;
        while ($times--) {
            $entities[] = $this->makeEntity(
                $this->buildAttributes($faker, $attributes)
            );
        }

        return $entities;
    }

    protected function getFaker(): Faker
    {
        return \Faker\Factory::create($this->locale);
    }

    /**
     * @param array $attributes
     * @return mixed
     * @throws \ReflectionException
     */
    protected function makeEntity(array $attributes)
    {
        $ref = new ReflectionClass($this->class());
        $instance = $ref->newInstanceWithoutConstructor();

        foreach ($attributes as $key => $attribute) {
            $property = $ref->getProperty($key);

            $property->setAccessible(true);
            $property->setValue($instance, $attribute);
        }

        return $instance;
    }

    abstract protected function class(): string;

    private function buildAttributes(Faker $faker, array $attributes): array
    {
        $currentAttributes = [];

        $recipes[] = new Recipe($this->default($faker));
        $recipes = array_merge($recipes, $this->recipes);
        $recipes[] = new Recipe($attributes);

        /** @var Recipe[] $recipes */
        foreach ($recipes as $recipe) {
            $cooked = $recipe->toAttribute($faker, $currentAttributes);

            if ($this->shouldCheckFillable()) {
                $this->checkAttributes($cooked);
            }

            $currentAttributes = array_merge($currentAttributes, $cooked);
        }

        return $currentAttributes;
    }

    private function checkAttributes(array $attributes): void
    {
        foreach ($attributes as $key => $attribute) {
            if ($this->isFillable($key)) {
                continue;
            }
            throw new InvalidAttributeException($key.' is not fillable.');
        }
    }

    private function isFillable(string $key): bool
    {
        if (isset($this->cachedFillable[$key])) {
            return $this->cachedFillable[$key];
        }

        $isFillable = in_array($key, $this->fillable(), true);
        $this->cachedFillable[$key] = $isFillable;
        return $isFillable;
    }

    private function shouldCheckFillable(): bool
    {
        return $this->fillable()[0] !== '*';
    }

    protected function fillable(): array
    {
        return ['*'];
    }

    abstract protected function default(Faker $faker): array;

    final protected function shouldReturnMultiple(): bool
    {
        return $this->times !== 1;
    }

    protected function persistEntity($entity): void
    {
        throw new LogicException('should override this method if you want to persist an entity.');
    }

    protected function newCollection(array $entities)
    {
        return $entities;
    }

    /**
     * @param array $attributes
     * @return mixed
     */
    final public function make(array $attributes = [])
    {
        $entities = $this->makeEntities($attributes);
        return $this->shouldReturnMultiple()
            ? $this->newCollection($entities)
            : $entities[0];
    }

    final public function attributes(array $attributes = []): array
    {
        $times = $this->times;
        $built = [];
        while ($times--) {
            $built[] = $this->buildAttributes($this->getFaker(), $attributes);
        }

        return $this->shouldReturnMultiple()
            ? $built
            : $built[0];
    }

    /**
     * @param array|callable $attribute
     */
    final protected function addRecipe($attribute): void
    {
        $this->recipes[] = new Recipe($attribute);
    }
}
