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
            'images' => $this->getMedia()->map(fn($media) => [
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
