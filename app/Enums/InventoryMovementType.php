<?php

namespace App\Enums;

enum InventoryMovementType: string
{
    case In = 'in';
    case Out = 'out';
    case Reservation = 'reservation';
    case Adjustment = 'adjustment';
    case Workshop = 'workshop';
    case Return = 'return';
}
