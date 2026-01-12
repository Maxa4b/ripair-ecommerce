<?php

namespace App\Enums;

enum RmaStatus: string
{
    case Received = 'received';
    case InReview = 'in_review';
    case Accepted = 'accepted';
    case Refused = 'refused';
    case Refunded = 'refunded';
    case Replaced = 'replaced';
}
