<?php

namespace App\Http\Controllers;

use App\Mail\EnquiryReceived;
use App\Services\ReservationBooking;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Lightweight "poptávka" (enquiry) for visitors who would rather ask than
 * build a cart. Sends the enquiry straight to the business inbox.
 */
class EnquiryController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        // Honeypot: real visitors never see or fill this field.
        if (filled($request->input('website'))) {
            return back()->with('enquiry_sent', true);
        }

        $data = $request->validate(ReservationBooking::contactRules() + [
            'event_date' => ['nullable', 'date'],
            'event_type' => ['nullable', 'string', 'max:100'],
            'event_place' => ['nullable', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:3000'],
        ], ReservationBooking::messages() + [
            'event_date.date' => 'Datum akce nemá platný tvar.',
            'message.required' => 'Napište nám prosím, co na akci potřebujete.',
        ]);

        $data['page'] = (string) $request->headers->get('referer', '');

        // Logged first so an enquiry is never lost, even if mail delivery fails.
        Log::info('Enquiry received', $data);

        try {
            Mail::to(config('contact.owner_notify'))->send(new EnquiryReceived($data));
        } catch (\Throwable $e) {
            Log::error('Enquiry email failed: '.$e->getMessage(), ['enquiry' => $data]);
        }

        return back()
            ->with('enquiry_sent', true)
            ->with('success', 'Děkujeme, poptávku jsme přijali. Ozveme se co nejdříve.');
    }
}
