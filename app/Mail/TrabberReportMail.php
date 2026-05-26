<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TrabberReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $reportType,
        public readonly string $csv,
        public readonly string $filename,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Vrooem Trabber '.ucfirst($this->reportType).' Report - '.now()->toDateString(),
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'emails.trabber.report',
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => $this->csv, $this->filename)
                ->withMime('text/csv'),
        ];
    }
}
