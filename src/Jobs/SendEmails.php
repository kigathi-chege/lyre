<?php

namespace Lyre\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendEmails implements ShouldQueue
{
    use Queueable;

    public $tries = 3;
    public $data, $email, $subject, $view;

    /**
     * Create a new job instance.
     */
    public function __construct($data, $email, $subject, $view)
    {
        $this->data = $data;
        $this->email = $email;
        $this->subject = $subject;
        $this->view = $view;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // TODO: Kigathi - July 2 2025 - Should monitor the status of Mail (Whether successfully dispatched or not)
        Mail::to($this->email)->send(new \Lyre\Mail\CommonMail($this->data, $this->subject, $this->view));
    }
}
