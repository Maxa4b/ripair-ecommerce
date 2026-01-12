<?php

namespace App\Services\Commerce;

use App\Models\Commerce\Order;
use App\Models\User;

class GuestOrderClaimService
{
    public function claimFor(User $user): int
    {
        $email = $this->normalizeEmail($user->email);
        if (! $email) {
            return 0;
        }

        $hours = (int) config('checkout.guest_order_claim_hours', 72);
        $since = now()->subHours(max(1, $hours));

        return (int) Order::query()
            ->whereNull('user_id')
            ->where('created_at', '>=', $since)
            ->where(function ($query) use ($email) {
                $query
                    ->where('billing_address->email', $email)
                    ->orWhere('metadata->guest_email', $email);
            })
            ->update(['user_id' => $user->id]);
    }

    private function normalizeEmail(?string $email): ?string
    {
        $email = is_string($email) ? trim($email) : null;
        if (! $email) {
            return null;
        }

        return mb_strtolower($email);
    }
}

