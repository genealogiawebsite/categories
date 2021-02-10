<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use LaravelEnso\Categories\Models\Category;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    private Category $parent;
    private Category $middle;
    private Category $leaf;

    protected function setUp(): void
    {
        parent::setUp();

        $this->parent = Category::factory()->create();
        $this->middle = Category::factory()->create(['parent_id' => $this->parent->id]);
        $this->leaf = Category::factory()->create(['parent_id' => $this->middle->id]);
    }

    /** @test */
    public function can_get_level()
    {
        $this->assertEquals(0, $this->parent->level());
        $this->assertEquals(1, $this->middle->level());
        $this->assertEquals(2, $this->leaf->level());
    }

    /** @test */
    public function can_get_depth()
    {
        $this->assertEquals(2, $this->parent->depth());
        $this->assertEquals(1, $this->middle->depth());
        $this->assertEquals(0, $this->leaf->depth());
    }

    /** @test */
    public function can_get_flatten()
    {
        $this->assertEquals([1, 2, 3], $this->parent->flattenCurrentAndBelow()->pluck('id')->toArray());
        $this->assertEquals([2, 3], $this->middle->flattenCurrentAndBelow()->pluck('id')->toArray());
        $this->assertEquals([3], $this->leaf->flattenCurrentAndBelow()->pluck('id')->toArray());
    }

    //TODO fix test
    public function can_get_parentTree()
    {
        $this->assertEquals([1], $this->parent->parentTree()->map->id->toArray());
        $this->assertEquals([1, 2], $this->middle->parentTree()->map->id->toArray());
        $this->assertEquals([1, 2, 3], $this->leaf->parentTree()->map->id->toArray());
    }
}
