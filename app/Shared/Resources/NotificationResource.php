<?php

namespace App\Shared\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->ID,
            'type' => [
                'value' => $this->type->value,
                'label' => $this->type->label(),
                'category' => $this->type->category(),
                'action' => $this->type->action(),
            ],
            'message' => $this->massage,
            'created_at' => $this->CreationDate,
            'updated_at' => $this->ModificationDate,
            'notifiable' => $this->whenLoaded('notifiable'),
        ];
    }
}
