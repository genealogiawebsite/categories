<?php

namespace LaravelEnso\Categories\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\MissingValue;

class Category extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'orderIndex' => $this->order_index,
            'selected' => false,
            'items' => self::collection($this->subcategories()),
        ];
    }

    private function subcategories()
    {
        $subcategories = $this->relationLoaded('subcategories')
            ? $this->subcategories
            : $this->whenLoaded('recursiveSubcategories');

        return ! $subcategories || $subcategories instanceof MissingValue
            ? []
            : $subcategories;
    }
}
