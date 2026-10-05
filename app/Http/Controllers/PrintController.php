<?php

namespace App\Http\Controllers;

use App\Models\Capa;
use App\Models\Ncr;
use Illuminate\Contracts\View\View;

/**
 * Print-ready reports. The browser's "Save as PDF" produces the PDF, so no PDF library is needed.
 */
class PrintController extends Controller
{
    public function ncr(Ncr $ncr): View
    {
        return view('print.ncr', ['ncr' => $ncr->load(['part', 'lot', 'supplier', 'creator', 'dispositionApprover', 'capas'])]);
    }

    public function capa(Capa $capa): View
    {
        return view('print.capa', ['capa' => $capa->load(['owner', 'verifier', 'ncrs', 'actions.owner'])]);
    }
}
