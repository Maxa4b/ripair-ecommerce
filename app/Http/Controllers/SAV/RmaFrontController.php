<?php

namespace App\Http\Controllers\SAV;

use App\Http\Controllers\Controller;
use App\Http\Requests\Front\StoreRmaRequest;
use App\Models\Commerce\Order;
use App\Models\Commerce\OrderItem;
use App\Models\Sav\RmaRequest;
use App\Services\Sav\RmaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class RmaFrontController extends Controller
{
    public function __construct(
        protected RmaService $service,
    ) {
        $this->middleware('auth');
    }

    public function create(Request $request)
    {
        $available = $this->savAvailable();
        if (! $available) {
            return redirect()
                ->route('account.sav.index')
                ->with('error', 'Le module SAV n\'est pas encore disponible.');
        }

        $ticket = null;
        if ($request->filled('ticket')) {
            $ticket = RmaRequest::query()
                ->where('id', $request->integer('ticket'))
                ->where('user_id', $request->user()->id)
                ->with(['order', 'items.orderItem.product', 'comments', 'attachments'])
                ->first();
        }

        $orders = Order::query()
            ->with([
                'items.product',
                'items.product.brand',
                'items.variant',
            ])
            ->where('user_id', $request->user()->id)
            ->latest('placed_at')
            ->limit(10)
            ->get();

        return view('front.sav.request', [
            'orders' => $orders,
            'ticket' => $ticket,
        ]);
    }

    public function store(StoreRmaRequest $request): RedirectResponse
    {
        $available = $this->savAvailable();
        if (! $available) {
            return redirect()
                ->route('account.sav.index')
                ->with('error', 'Le module SAV n\'est pas encore disponible.');
        }

        $order = Order::where('number', $request->string('order_number')->toString())
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $item = OrderItem::where('order_id', $order->id)
            ->where('id', $request->integer('order_item_id'))
            ->firstOrFail();

        $rma = $this->service->create($order, $item, $request->validated());
        $rma->items()->create([
            'order_item_id' => $item->id,
            'quantity' => 1,
        ]);

        $message = trim((string) $request->input('description', ''));
        if ($message !== '') {
            $rma->comments()->create([
                'user_id' => $request->user()->id,
                'comment' => $message,
                'is_internal' => false,
            ]);
        }

        if ($request->hasFile('attachments')) {
            $storage = Storage::disk('public');
            $dir = 'rma/' . $rma->id;
            foreach ($request->file('attachments', []) as $file) {
                if (! $file) {
                    continue;
                }
                $originalName = $file->getClientOriginalName();
                $ext = $file->getClientOriginalExtension();
                $base = pathinfo($originalName, PATHINFO_FILENAME);
                $safeBase = Str::slug($base, '-');
                if ($safeBase === '') {
                    $safeBase = 'piece-jointe';
                }
                $extNormalized = $ext ? '.' . strtolower($ext) : '';
                $counter = 1;
                $candidate = $safeBase . $extNormalized;
                while ($storage->exists($dir . '/' . $candidate)) {
                    $suffix = sprintf('_%02d', $counter++);
                    $candidate = $safeBase . $suffix . $extNormalized;
                }

                $path = $file->storeAs($dir, $candidate, 'public');
                $rma->attachments()->create([
                    'path' => $path,
                    'type' => $file->getClientMimeType(),
                ]);
            }
        }

        return redirect()
            ->route('sav.request', ['ticket' => $rma->id])
            ->with('success', 'Ticket créé.');
    }

    private function savAvailable(): bool
    {
        try {
            return Schema::hasTable('rma_requests') && Schema::hasTable('rma_items');
        } catch (\Throwable) {
            return false;
        }
    }
}
