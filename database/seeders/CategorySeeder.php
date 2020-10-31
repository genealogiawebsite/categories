<?php

use Illuminate\Database\Seeder;
use LaravelEnso\Categories\Models\Category;

class CategorySeeder extends Seeder
{
    public function run()
    {
        Category::factory()->count(5)->create();
    }
}
