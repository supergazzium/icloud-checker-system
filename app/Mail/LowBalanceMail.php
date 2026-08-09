<?php
namespace App\Mail;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LowBalanceMail extends Mailable
{
    use Queueable, SerializesModels;
    public function __construct(public User $user) {}

    public function build()
    {
        return $this->subject('แจ้งเตือนเครดิตต่ำ — iPart Store')
            ->view('emails.low-balance');
    }
}
