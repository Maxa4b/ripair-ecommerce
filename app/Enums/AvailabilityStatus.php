<?php

namespace App\Enums;

enum AvailabilityStatus: string
{
    case InStock = 'in_stock';
    case LowStock = 'low_stock';
    case Backorder = 'backorder';
    case Preorder = 'preorder';
    case OutOfStock = 'out_of_stock';
}
