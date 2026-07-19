<?php

namespace App\Http\Resources;

use App\Services\CarrierTracking;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'status' => $this->status,
            'subtotal' => (float) $this->subtotal,
            'tax_amount' => (float) $this->tax_amount,
            'shipping_amount' => (float) $this->shipping_amount,
            'total_amount' => (float) $this->total_amount,
            'currency' => $this->currency,
            'discount_code' => $this->discount_code,
            'discount_amount' => (float) $this->discount_amount,
            'payment_status' => $this->payment_status,
            'user' => new UserResource($this->whenLoaded('user')),
            'shipping_address' => new AddressResource($this->whenLoaded('shippingAddress')),
            'billing_address' => new AddressResource($this->whenLoaded('billingAddress')),
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'approved_by' => new UserResource($this->whenLoaded('approvedBy')),
            'approved_at' => $this->approved_at?->toIso8601String(),
            'notes' => $this->notes,
            'tracking_number' => $this->tracking_number,
            'carrier' => $this->carrier,
            'tracking_url' => CarrierTracking::url($this->carrier, $this->tracking_number),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
