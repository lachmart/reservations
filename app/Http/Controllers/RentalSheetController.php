<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Support\RentalSheet;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

/**
 * The reservation's rental sheet as a PDF, opened in the browser to print or
 * save. Registered as an admin panel route, so only signed-in admins get it.
 */
class RentalSheetController extends Controller
{
    public function __invoke(Reservation $reservation): Response
    {
        $sheet = new RentalSheet($reservation);

        return Pdf::loadView('pdf.rental-sheet', ['sheet' => $sheet])
            ->setPaper('a4')
            // Embed only the letters used: ~70 kB instead of ~900 kB.
            ->setOption('isFontSubsettingEnabled', true)
            ->stream($sheet->filename());
    }
}
