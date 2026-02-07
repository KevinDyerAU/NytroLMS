<?php

namespace App\Helpers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class DateHelper
{
    public static function parse($date)
    {
        if (empty($date) || (is_string($date) && strtolower($date) === 'null')) {
            return;
        }
        // If already a Carbon instance, ensure it's in the correct timezone.
        if ($date instanceof Carbon) {
            return $date;
        }
        // Handle integer timestamps
        if (is_int($date) || ctype_digit($date)) {
            return Carbon::createFromTimestamp($date);
        }

        // For string parsing, it's better to use createFromFormat for precision and reliability.
        if (!is_string($date)) {
            // Not a string, and we've already checked other types.
            return;
        }

        // The list of formats to try. More specific formats should come first.
        // The format with the comma is included to handle the original string directly.
        $formats = [
            'Y-m-d\TH:i:s.uP',   // ISO 8601 with microseconds and timezone
            'Y-m-d\TH:i:sP',     // ISO 8601 with timezone
            'Y-m-d H:i:s.u',     // MySQL format with microseconds
            'Y-m-d H:i:s',
            'j F, Y g:i A',      // e.g., "2 May, 2025 12:38 PM"
            'j M Y g:i A',       // e.g., "2 May 2025 12:38 PM" (no comma)
            'd F, Y h:i A',       // e.g., '19 July, 2022 7:54 AM'; h: 12-hour format of an hour with leading zeros (01 to 12)
            'Y-m-d',
            'j F, Y',
            'd/m/Y',
        ];

        foreach ($formats as $format) {
            try {
                // Use createFromFormat which is stricter and more reliable than parse().
                return Carbon::createFromFormat($format, $date);
            } catch (\Exception $e) {
                // If this format fails, the loop will try the next one.
                continue;
            }
        }

        // If all explicit formats fail, try Carbon's general-purpose parser as a last resort.
        try {
            return Carbon::parse($date);
        } catch (\Exception $e) {
            Log::warning('DateHelper::parse failed for all known formats and the general parser.', [
                'date' => $date,
                'error' => $e->getMessage(),
            ]);

            return;
        }
    }

    public static function parseWithTimeZone($date, $timezone = null): ?Carbon
    {
        if (empty($date) || (is_string($date) && strtolower($date) === 'null')) {
            return null;
        }

        if (empty($timezone)) {
            $timezone = Helper::getTimeZone();
        }

        // If already a Carbon instance, ensure it's in the correct timezone.
        if ($date instanceof Carbon) {
            return $timezone ? $date->setTimezone($timezone) : $date;
        }

        // Handle integer timestamps.
        if (is_int($date) || ctype_digit($date)) {
            return Carbon::createFromTimestamp($date, $timezone);
        }

        // For string parsing, it's better to use createFromFormat for precision and reliability.
        if (!is_string($date)) {
            // Not a string, and we've already checked other types.
            return null;
        }

        // The list of formats to try. More specific formats should come first.
        // The format with the comma is included to handle the original string directly.
        $formats = [
            'Y-m-d\TH:i:s.uP',   // ISO 8601 with microseconds and timezone
            'Y-m-d\TH:i:sP',     // ISO 8601 with timezone
            'Y-m-d H:i:s.u',     // MySQL format with microseconds
            'Y-m-d H:i:s',
            'j F, Y g:i A',      // e.g., "2 May, 2025 12:38 PM"
            'j M Y g:i A',       // e.g., "2 May 2025 12:38 PM" (no comma)
            'Y-m-d',
            'j F, Y',
            'd/m/Y',
        ];

        foreach ($formats as $format) {
            try {
                // Use createFromFormat which is stricter and more reliable than parse().
                return Carbon::createFromFormat($format, $date, $timezone);
            } catch (\Exception $e) {
                // If this format fails, the loop will try the next one.
                continue;
            }
        }

        // If all explicit formats fail, try Carbon's general-purpose parser as a last resort.
        try {
            return Carbon::parse($date, $timezone);
        } catch (\Exception $e) {
            Log::warning('DateHelper::parse failed for all known formats and the general parser.', [
                'date' => $date,
                'timezone' => $timezone,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
