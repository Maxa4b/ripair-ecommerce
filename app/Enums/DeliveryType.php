<?php

namespace App\Enums;

enum DeliveryType: string
{
    case Shipping = 'shipping';
    case Relay = 'relay';
    case WorkshopPickup = 'workshop_pickup';
}
