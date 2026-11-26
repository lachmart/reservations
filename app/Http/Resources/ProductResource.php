<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(\Illuminate\Http\Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'price_per_day' => (float) $this->price_per_day,
            'stock' => (int) $this->stock,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'tags' => TagResource::collection($this->whenLoaded('tags')),
            // Main photo, then the gallery; the old "default" collection only
            // when there is nothing else (same order as Product::mainImageUrl()).
            'images' => $this->getMedia('avatar')->concat($this->getMedia('product-images'))
                ->whenEmpty(fn ($media) => $this->getMedia())
                ->values()
                ->map(fn($media) => [
                'id' => $media->id,
                'url' => $media->getUrl(),
                'thumb' => $media->getUrl('thumb'),
                'preview' => $media->getUrl('preview'),
            ]),
            'available_count' => $this->when(
                $request->has(['start_date', 'end_date']),
                fn() => $this->availableForRange($request->input('start_date'), $request->input('end_date'))
            ),
        ];
    }
}
