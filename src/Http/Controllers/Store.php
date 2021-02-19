<?php

namespace LaravelEnso\Categories\Http\Controllers;

use Illuminate\Routing\Controller;
use LaravelEnso\Categories\Http\Requests\ValidateStoreRequest;
use LaravelEnso\Categories\Http\Resources\Category as Resource;
use LaravelEnso\Categories\Models\Category;

class Store extends Controller
{
    public function __invoke(ValidateStoreRequest $request, Category $category)
    {
        $category->fill($request->validated())->save();

        return ['item' => new Resource($category->load('subcategories'))];
    }
}
