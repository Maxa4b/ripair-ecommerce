<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Sav\RmaRequest;
use App\Models\Sav\RmaComment;
use App\Models\Sav\RmaAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use App\Enums\RmaStatus;

class RmaController extends Controller
{
    public function index(Request $request)
    {
        $available = Schema::hasTable('rma_requests');

        $requests = collect();
        if ($available) {
            $requests = RmaRequest::query()
                ->where('user_id', $request->user()->id)
                ->with('order')
                ->latest()
                ->paginate(10);
        }

        return view('account.sav.index', [
            'requests' => $requests,
            'savAvailable' => $available,
        ]);
    }

    public function show(RmaRequest $rmaRequest)
    {
        abort_unless($rmaRequest->user_id === auth()->id(), 403);

        return view('account.sav.show', [
            'rma' => $rmaRequest->load([
                'order',
                'items.orderItem.product',
                'attachments',
                'comments' => function ($query) {
                    $query->where('is_internal', false)->orderBy('created_at');
                },
            ]),
        ]);
    }

    public function downloadAttachment(Request $request, RmaAttachment $attachment)
    {
        $rma = $attachment->request;
        abort_unless($rma && $rma->user_id === $request->user()->id, 403);

        $disk = Storage::disk('public');
        if (! $disk->exists($attachment->path)) {
            abort(404);
        }

        return response()->file($disk->path($attachment->path));
    }

    public function comment(Request $request, RmaRequest $rmaRequest)
    {
        abort_unless($rmaRequest->user_id === $request->user()->id, 403);

        $validated = $request->validate([
            'comment' => ['required', 'string', 'max:2000'],
            'attachments.*' => ['file', 'max:5120', 'mimes:jpg,jpeg,png,pdf'],
        ]);

        $comment = RmaComment::create([
            'rma_request_id' => $rmaRequest->id,
            'user_id' => $request->user()->id,
            'comment' => $validated['comment'],
            'is_internal' => false,
        ]);

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $originalName = $file->getClientOriginalName();
                $ext = $file->getClientOriginalExtension();
                $base = pathinfo($originalName, PATHINFO_FILENAME);
                $safeBase = Str::slug($base, '-');
                if ($safeBase === '') {
                    $safeBase = 'piece-jointe';
                }
                $safeName = $safeBase;
                $extNormalized = $ext ? '.' . strtolower($ext) : '';
                $counter = 1;
                $storage = Storage::disk('public');
                $dir = 'rma/' . $rmaRequest->id;

                $candidate = $safeName . $extNormalized;
                while ($storage->exists($dir . '/' . $candidate)) {
                    $suffix = sprintf('_%02d', $counter++);
                    $candidate = $safeName . $suffix . $extNormalized;
                }

                $path = $file->storeAs($dir, $candidate, 'public');
                RmaAttachment::create([
                    'rma_request_id' => $rmaRequest->id,
                    'path' => $path,
                    'type' => $file->getClientMimeType(),
                ]);
            }
        }

        return back()->with('success', 'Message envoyé.');
    }

    public function close(Request $request, RmaRequest $rmaRequest)
    {
        abort_unless($rmaRequest->user_id === $request->user()->id, 403);

        // Utilise un statut existant compatible avec la colonne (refused) + métadonnée de clôture
        $metadata = $rmaRequest->metadata ?? [];
        $metadata['closed_by_user'] = true;
        $rmaRequest->update([
            'status' => RmaStatus::Refused,
            'metadata' => $metadata,
        ]);

        return back()->with('success', 'Ticket clôturé.');
    }
}
