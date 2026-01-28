<?php

namespace App\Console\Commands;

use App\Mail\InactivityEmail;
use App\Mail\InactivityReport;
use App\Models\Note;
use App\Models\User;
use App\Services\CronJobManagerService;
use App\Services\StudentActivityService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendInactivityEmail extends Command
{
    protected $signature = 'email:send-inactivity {weeks}';

    protected $description = 'Send inactivity email to students after specified weeks of inactivity';

    private StudentActivityService $activityService;

    protected string $cronjob = 'inactivity-email';

    public function __construct(StudentActivityService $activityService)
    {
        parent::__construct();
        $this->activityService = $activityService;
    }

    public function handle()
    {
        $weeks = $this->argument('weeks');
        $inactiveDate = Carbon::now()->subWeeks($weeks);
        $startOfWeek = $inactiveDate->startOfWeek(Carbon::MONDAY)->format('Y-m-d');
        $endOfWeek = $inactiveDate->endOfWeek(Carbon::SUNDAY)->format('Y-m-d');

        $this->info("Starting inactivity email process for {$weeks} weeks...");

        $manager = new CronJobManagerService($this->cronjob, $weeks);
        $startTime = microtime(true);
        $startMemory = memory_get_usage();
        $updated = $failed = 0;

        try {
            $students = User::with(['courseEnrolments', 'detail', 'companies'])
                ->onlyStudents()
                ->onlyActive()
                ->whereHas('courseEnrolments', function ($query) {
                    $query->where('status', '!=', 'DELIST')
                        ->where('course_start_at', '>=', '2024-01-01')
                        ->where('course_start_at', '<=', Carbon::today());
                })
                ->whereHas('companies', function ($query) {
                    $query->excludeSpecialNames();
                })
                ->whereHas('detail', function ($query) use ($startOfWeek, $endOfWeek) {
                    $query->whereNotNull('last_logged_in')
                        ->whereBetween(DB::raw("CONVERT_TZ(last_logged_in, '+00:00', '+10:00')"), [$startOfWeek, $endOfWeek.' 23:59:59']);
                })
                ->get()
                ->map(function ($student) {
                    $student->setRelation('courseEnrolments', $student->courseEnrolments->filter(function ($enrolment) {
                        return isset($enrolment->progress) && floatval($enrolment->progress->percentage) != 100.00;
                    }));

                    return $student;
                })
                ->filter(fn ($student) => $student->courseEnrolments->isNotEmpty());

            if (count($students) > 0) {
                foreach ($students as $student) {
                    foreach ($student->courseEnrolments as $courseEnrolment) {
                        try {
                            $course = $courseEnrolment->course;
                            Mail::to($student->email)
                                ->cc($student->leaders()->first()?->email ?? null)
                                ->send(new InactivityEmail($student, $course, $weeks));

                            $this->addNote($student, $courseEnrolment, $weeks);
                            $updated++;
                        } catch (\Throwable $e) {
                            $failed++;
                            Log::error("Failed to send inactivity email for student ID {$student->id}: {$weeks} weeks ".$e->getMessage(), [
                                'trace' => $e->getTraceAsString(),
                            ]);
                            $manager->logFailure($student->id, $e, false);
                        }
                    }
                }

                // Send list of students to admin
                try {
                    $adminEmail = 'admin@keycompany.com.au';
                    $bccEmail = 'mohsin.adeel@live.com';
                    Mail::to($adminEmail)
                        ->bcc($bccEmail)
                        ->send(new InactivityReport($students, $weeks));
                } catch (\Symfony\Component\Mailer\Exception\TransportException $e) {
                    $failed++;
                    Log::error('Email transport failed - inactivity report to admin: ' . $e->getMessage(), [
                        'weeks' => $weeks,
                        'exception' => $e
                    ]);
                } catch (\Exception $e) {
                    $failed++;
                    Log::error('Unexpected error sending inactivity report to admin: ' . $e->getMessage(), [
                        'weeks' => $weeks,
                        'exception' => $e
                    ]);
                }
            }

            $endTime = microtime(true);
            $endMemory = memory_get_usage();
            $totalRun = $manager->getTotalRunTimeForToday() ?? ($endTime - $startTime);
            $run_time = round($totalRun, 4).' seconds ('.round(($totalRun) / 60, 2).' minutes)';
            $memory_usage = round(($endMemory - $startMemory) / (1024 * 1024), 2).' MB';

            $manager->sendSummary($updated, $failed, [
                'run_time' => $run_time,
                'memory_usage' => $memory_usage,
                'weeks' => $weeks,
            ]);

            $this->info('Inactivity email process completed.');
            $this->info("Updated Records: {$updated}");
            $this->info("Failed Records: {$failed}");
            $this->info('Last Run At: '.now()->toDateTimeString());
            $this->info('Last Run Time: '.$run_time);
            $this->info('Last Memory Usage: '.$memory_usage);
        } catch (\Throwable $e) {
            Log::error("Failed to process inactivity emails: {$weeks} weeks ".$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            $manager->logFailure(0, $e, true);

            throw $e;
        }
    }

    private function addNote($student, $courseEnrolment, $weeks)
    {
        // Define the system user ID
        $systemUserId = 0;
        // Add Note
        $note = Note::create(
            [
            'user_id' => $systemUserId,
            'subject_type' => User::class,
            'subject_id' => $student->id,
            'note_body' => "<p>{$weeks} weeks inactivity notification sent for course: {$courseEnrolment->course->title} </p>",
        ]
        );
        $this->activityService->setActivity(
            [
            'user_id' => $student->id,
            'activity_event' => 'NOTE ADDED',
            'activity_details' => [
                'student' => $student->id,
                'course' => $courseEnrolment->course->id,
                'through' => $weeks.' weeks Inactivity Email cron job',
            ],
        ],
            $note
        );
    }
}
