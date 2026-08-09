<?php
namespace App\Mail;
use App\Models\Topup;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TopupSuccessMail extends Mailable
{
    use Queueable, SerializesModels;
    public function __construct(public Topup $topup) {}

    public function build()
    {
        return $this->subject('เติมเครดิตสำเร็จ — iPart Store')
            ->view('emails.topup-success');
    }
}
