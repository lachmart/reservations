<?php

namespace App\Http\Controllers;

use App\Mail\EnquiryReceived;
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

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'required_without:email', 'string', 'max:50'],
            'email' => ['nullable', 'required_without:phone', 'email', 'max:255'],
            'event_date' => ['nullable', 'date'],
            'event_type' => ['nullable', 'string', 'max:100'],
            'message' => ['required', 'string', 'max:3000'],
        ], [
            'name.required' => 'Vyplňte prosím jméno.',
            'phone.required_without' => 'Vyplňte telefon nebo e-mail, ať se vám můžeme ozvat.',
            'email.required_without' => 'Vyplňte telefon nebo e-mail, ať se vám můžeme ozvat.',
            'email.email' => 'E-mail nemá platný tvar.',
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
