<?php

namespace App\Mail;

use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ProcesOrderMail extends Mailable
{
    use Queueable, SerializesModels;

    public $order;
    public $customer;
    public $user;

    /**
     * Create a new message instance.
     */
    public function __construct(Order $order, Customer $customer, User $user)
    {
        $this->order = $order;
        $this->customer = $customer;
        $this->user = $user;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->view('emails.process-order')
            ->subject('Proses Order - Pesanan Kamu Sedang Diproses')
            ->with([
                'order' => $this->order,
                'customer' => $this->customer,
                'user' => $this->user,
            ]);
    }
}
