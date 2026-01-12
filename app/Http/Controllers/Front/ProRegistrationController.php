<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Http\Requests\Front\ProRegistrationRequest;
use App\Models\User;
use Illuminate\Support\Str;

class ProRegistrationController extends Controller
{
    public function create()
    {
        return view('front.pro.apply');
    }

    public function store(ProRegistrationRequest $request)
    {
        [$firstName, $lastName] = $this->splitName($request->string('contact_name')->toString());

        $user = User::firstOrNew(['email' => $request->string('email')->toString()]);
        $user->fill([
            'first_name' => $user->first_name ?: $firstName,
            'last_name' => $user->last_name ?: $lastName,
            'company_name' => $request->string('company_name')->toString(),
            'siret' => $request->string('siret')->toString(),
            'vat_number' => $request->string('vat_number')->toString(),
            'phone' => $request->string('phone')->toString(),
            'account_type' => 'pro',
            'pro_status' => 'pending',
        ]);

        if (! $user->exists) {
            $user->password = bcrypt(Str::random(20));
        }

        $user->save();

        return redirect()->route('pro.apply')->with('success', 'Votre demande PRO a bien été transmise. Réponse sous 24h ouvrées.');
    }

    private function splitName(string $value): array
    {
        $parts = explode(' ', trim($value), 2);

        return [$parts[0], $parts[1] ?? $parts[0]];
    }
}
