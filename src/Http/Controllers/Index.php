<?php

namespace LaravelEnso\Categories\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Config;
use LaravelEnso\Categories\Http\Resources\Category as Resource;
use LaravelEnso\Categories\Models\Category;

class Index extends Controller
{
    public function __invoke()
    {
        return [
            'maxNestingLevel' => Config::get('enso.categories.maxNestingLevel'),
            'items' => Resource::collection(Category::tree()),
        ];
    }
}
