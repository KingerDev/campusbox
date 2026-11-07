<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Support\CurrentBusiness;
use App\Support\OrderPresenter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IssueController extends Controller
{
    public function __invoke(Request $request, CurrentBusiness $current): Response
    {
        $business = $current->business();
        $business->issue_tried_at ??= now();
        $business->save();

        $code = preg_replace('/\D/', '', (string) $request->query('kod'));
        $found = null;
        $error = null;

        if ($code !== '') {
            $code = substr(str_pad($code, 4, '0', STR_PAD_LEFT), -4);
            $order = $business->orders()->today()->with('items')->where('code', 'KV-'.$code)->first();
            $order
                ? $found = OrderPresenter::toArray($order, $current->can('money'))
                : $error = 'Kód KV‑'.$code.' medzi dnešnými rezerváciami nie je.';
        }

        return Inertia::render('Panel/Issue', [
            'code' => $code,
            'found' => $found,
            'error' => $error,
            'slot' => $business->rescue_slot,
        ]);
    }
}
