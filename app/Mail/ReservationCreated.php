<?php

namespace App\Mail;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ReservationCreated extends Mailable
{
    use Queueable, SerializesModels;

    public Reservation $reservation;

    /**
     * Create a new message instance.
     */
    public function __construct(Reservation $reservation)
    {
        $this->reservation = $reservation;
    }

    /**
     * Build the message.
     */
    public function build(): self
    {
        // Eager-load items and related products for the email view
        $this->reservation->loadMissing(['reservationItems.product']);

        return $this->subject(__('Potvrzení rezervace'))
            ->view('emails.reservations.created')
            ->with([
                'reservation' => $this->reservation,
            ]);
    }
}
