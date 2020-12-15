<?php declare(strict_types=1);
namespace Yahiru\EntityFactory;

use Faker\Generator as Faker;
use Yahiru\EntityFactory\Exception\InvalidRecipeException;

final class Recipe
{
    /** @var array<string, mixed>|callable */
    private $recipe;

    /**
     * @param array<string, mixed>|callable $recipe
     */
    public function __construct($recipe)
    {
        $this->setRecipe($recipe);
    }

    /**
     * @param array<string, mixed>|callable $recipe
     */
    private function setRecipe($recipe): void
    {
        // @phpstan-ignore-next-line
        if (! is_array($recipe) && ! is_callable($recipe)) {
            throw new InvalidRecipeException('recipe must be array or callable.');
        }

        $this->recipe = $recipe;
    }

    /**
     * @param array<string, mixed> $currentAttributes
     * @return array<string, mixed>
     */
    public function toAttribute(Faker $faker, array $currentAttributes): array
    {
        if (is_array($this->recipe)) {
            return $this->recipe;
        }

        return ($this->recipe)($faker, $currentAttributes);
    }
}
