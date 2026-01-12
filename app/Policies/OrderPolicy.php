<?php

namespace App\Policies;

use App\Models\Commerce\Order;
use App\Models\User;

class OrderPolicy
{
    public function view(User $user, Order $order): bool
    {
        if ($order->user_id === $user->id) {
            return true;
        }

        return $user->roles()->whereIn('slug', ['super-admin', 'logistique'])->exists();
    }
}
