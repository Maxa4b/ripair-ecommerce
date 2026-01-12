<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RmaStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RmaUpdateRequest;
use App\Models\Sav\RmaRequest;
use App\Services\Sav\RmaService;
use Illuminate\Http\RedirectResponse;

class RmaController extends Controller
{
    public function __construct(
        protected RmaService $service,
    ) {
    }

    public function index()
    {
        return view('admin.rma.index', [
            'requests' => RmaRequest::query()->with('order')->latest()->paginate(25),
        ]);
    }

    public function show(RmaRequest $rma)
    {
        return view('admin.rma.show', [
            'rma' => $rma->load('order', 'items.orderItem', 'comments'),
        ]);
    }

    public function update(RmaUpdateRequest $request, RmaRequest $rma): RedirectResponse
    {
        $this->service->updateStatus($rma, RmaStatus::from($request->string('status')->toString()), $request->input('comment'));

        return back()->with('success', 'Statut RMA mis à jour.');
    }
}
