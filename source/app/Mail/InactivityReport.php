<?php

namespace App\Mail;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InactivityReport extends Mailable
{
    use Queueable;
    use SerializesModels;

    public $students;

    public $weeks;

    public function __construct($students, $weeks)
    {
        $this->students = $students;
        $this->weeks = $weeks;
    }

    public function build()
    {
        $inactiveDate = Carbon::now()->subWeeks($this->weeks);
        $startOfWeek = $inactiveDate->startOfWeek(Carbon::MONDAY)->format('Y-m-d');
        $endOfWeek = $inactiveDate->endOfWeek(Carbon::SUNDAY)->format('Y-m-d');

        return $this->view('emails.cron.inactivity_report')
            ->with([
                'students' => $this->students,
                'weeks' => $this->weeks,
                'startOfWeek' => $startOfWeek,
                'endOfWeek' => $endOfWeek,
                'inactiveDate' => $inactiveDate,
            ]);
    }
}
