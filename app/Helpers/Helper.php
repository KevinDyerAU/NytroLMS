<?php

// Code within app\Helpers\Helper.php

namespace App\Helpers;

use App\Models\QuizAttempt;
use Carbon\Carbon;
use Config;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Helper
{
    public static function applClasses()
    {
        // default data array
        $DefaultData = [
            'mainLayoutType' => 'vertical',
            'theme' => 'light',
            'sidebarCollapsed' => false,
            'navbarColor' => '',
            'horizontalMenuType' => 'floating',
            'verticalMenuNavbarType' => 'floating',
            'footerType' => 'static', // footer
            'layoutWidth' => 'boxed',
            'showMenu' => true,
            'bodyClass' => '',
            'pageClass' => '',
            'pageHeader' => true,
            'contentLayout' => 'default',
            'blankPage' => false,
            'defaultLanguage' => 'en',
            'direction' => env('MIX_CONTENT_DIRECTION', 'ltr'),
        ];

        // if any key missing of array from custom.php file it will be merge and set a default value from dataDefault array and store in data variable
        $data = array_merge($DefaultData, config('custom.custom'));

        // All options available in the template
        $allOptions = [
            'mainLayoutType' => ['vertical', 'horizontal'],
            'theme' => ['light' => 'light', 'dark' => 'dark-layout', 'bordered' => 'bordered-layout', 'semi-dark' => 'semi-dark-layout'],
            'sidebarCollapsed' => [true, false],
            'showMenu' => [true, false],
            'layoutWidth' => ['full', 'boxed'],
            'navbarColor' => ['bg-primary', 'bg-info', 'bg-warning', 'bg-success', 'bg-danger', 'bg-dark'],
            'horizontalMenuType' => ['floating' => 'navbar-floating', 'static' => 'navbar-static', 'sticky' => 'navbar-sticky'],
            'horizontalMenuClass' => ['static' => '', 'sticky' => 'fixed-top', 'floating' => 'floating-nav'],
            'verticalMenuNavbarType' => ['floating' => 'navbar-floating', 'static' => 'navbar-static', 'sticky' => 'navbar-sticky', 'hidden' => 'navbar-hidden'],
            'navbarClass' => ['floating' => 'floating-nav', 'static' => 'navbar-static-top', 'sticky' => 'fixed-top', 'hidden' => 'd-none'],
            'footerType' => ['static' => 'footer-static', 'sticky' => 'footer-fixed', 'hidden' => 'footer-hidden'],
            'pageHeader' => [true, false],
            'contentLayout' => ['default', 'content-left-sidebar', 'content-right-sidebar', 'content-detached-left-sidebar', 'content-detached-right-sidebar'],
            'blankPage' => [false, true],
            'sidebarPositionClass' => ['content-left-sidebar' => 'default', 'content-right-sidebar' => 'sidebar-right', 'content-detached-left-sidebar' => 'sidebar-detached', 'content-detached-right-sidebar' => 'default', 'default' => 'default-sidebar-position'],
            'contentsidebarClass' => ['content-left-sidebar' => 'content-right', 'content-right-sidebar' => 'content-left', 'content-detached-left-sidebar' => 'content-detached content-right', 'content-detached-right-sidebar' => 'content-detached', 'default' => 'default-sidebar'],
            'defaultLanguage' => ['en' => 'en', 'fr' => 'fr', 'de' => 'de', 'pt' => 'pt'],
            'direction' => ['ltr', 'rtl'],
        ];

        // if mainLayoutType value empty or not match with default options in custom.php config file then set a default value
        foreach ($allOptions as $key => $value) {
            if (array_key_exists($key, $DefaultData)) {
                if (gettype($DefaultData[$key]) === gettype($data[$key])) {
                    // data key should be string
                    if (is_string($data[$key])) {
                        // data key should not be empty
                        if (isset($data[$key]) && $data[$key] !== null) {
                            // data key should not be exist inside allOptions array's sub array
                            if (!array_key_exists($data[$key], $value)) {
                                // ensure that passed value should be match with any of allOptions array value
                                $result = array_search($data[$key], $value, 'strict');
                                if (empty($result) && $result !== 0) {
                                    $data[$key] = $DefaultData[$key];
                                }
                            }
                        } else {
                            // if data key not set or
                            $data[$key] = $DefaultData[$key];
                        }
                    }
                } else {
                    $data[$key] = $DefaultData[$key];
                }
            }
        }

        // layout classes
        $layoutClasses = [
            'theme' => $data['theme'],
            'layoutTheme' => $allOptions['theme'][$data['theme']],
            'sidebarCollapsed' => $data['sidebarCollapsed'],
            'showMenu' => $data['showMenu'],
            'layoutWidth' => $data['layoutWidth'],
            'verticalMenuNavbarType' => $allOptions['verticalMenuNavbarType'][$data['verticalMenuNavbarType']],
            'navbarClass' => $allOptions['navbarClass'][$data['verticalMenuNavbarType']],
            'navbarColor' => $data['navbarColor'],
            'horizontalMenuType' => $allOptions['horizontalMenuType'][$data['horizontalMenuType']],
            'horizontalMenuClass' => $allOptions['horizontalMenuClass'][$data['horizontalMenuType']],
            'footerType' => $allOptions['footerType'][$data['footerType']],
            'sidebarClass' => '',
            'bodyClass' => $data['bodyClass'],
            'pageClass' => $data['pageClass'],
            'pageHeader' => $data['pageHeader'],
            'blankPage' => $data['blankPage'],
            'blankPageClass' => '',
            'contentLayout' => $data['contentLayout'],
            'sidebarPositionClass' => $allOptions['sidebarPositionClass'][$data['contentLayout']],
            'contentsidebarClass' => $allOptions['contentsidebarClass'][$data['contentLayout']],
            'mainLayoutType' => $data['mainLayoutType'],
            'defaultLanguage' => $allOptions['defaultLanguage'][$data['defaultLanguage']],
            'direction' => $data['direction'],
        ];
        // set default language if session hasn't locale value the set default language
        if (!session()->has('locale')) {
            app()->setLocale($layoutClasses['defaultLanguage']);
        }

        // sidebar Collapsed
        if ($layoutClasses['sidebarCollapsed'] == 'true') {
            $layoutClasses['sidebarClass'] = 'menu-collapsed';
        }

        // blank page class
        if ($layoutClasses['blankPage'] == 'true') {
            $layoutClasses['blankPageClass'] = 'blank-page';
        }

        return $layoutClasses;
    }

    public static function updatePageConfig($pageConfigs)
    {
        $demo = 'custom';
        if (isset($pageConfigs)) {
            if (count($pageConfigs) > 0) {
                foreach ($pageConfigs as $config => $val) {
                    Config::set('custom.'.$demo.'.'.$config, $val);
                }
            }
        }
    }

    public static function getTimeZoneOffset($fromZone = 'GMT')
    {
        $defaultTime = Carbon::now($fromZone);
        $userTime = Carbon::now(self::getTimeZone())->shiftTimezone($fromZone);

        return $defaultTime->diffInHours($userTime, false);
    }

    public static function getTimeZone()
    {
        if (!empty(session('UserTimezone'))) {
            return session('UserTimezone');
        }
        if (Auth::check()) {
            session(['UserTimezone' => Auth::user()->detail->timezone]);

            return Auth::user()->detail->timezone;
        }
        session(['UserTimezone' => 'Australia/Sydney']);

        return 'Australia/Sydney';
    }

    public static function errorResponse($messages, $code): \Illuminate\Http\JsonResponse
    {
        $errors = collect($messages)->map(function ($message) {
            return ['message' => $message];
        });

        return response()->json(['status' => 'error', 'errors' => $errors], $code);
    }

    public static function successResponse($data, $message, $code = 200): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'data' => $data,
            'success' => true, 'status' => 'success',
            'message' => $message,
        ], $code);
    }

    public static function populateAssessment($questionId, $content, ?int $lastAnswer = null, $disable = false)
    {
        preg_match_all('/\{(.*?)\}/im', $content, $matches);

        $assessmentTemp = '';

        for ($i = 0, $ci = count($matches[1]); $i < $ci; $i++) {
            $match = $matches[1][$i];
            preg_match_all('/\[([^\|\]]+)(?:\|(\d+))?\]/im', $match, $ms);

            $a = '';

            for ($j = 0, $cj = count($ms[1]); $j < $cj; $j++) {
                $v = $ms[1][$j] ?? '';

                $a .= '<span class="form-check form-check-inline">
                        <input type="radio" value="'.($j + 1).'" name="answer['.$questionId.']" class="form-check-input" data-index="'.$i.'"  data-ans="'.($lastAnswer ?? '').'"'.(($lastAnswer === $j + 1) ? 'checked' : ($disable ? 'disabled' : '')).'>
                        <label class="form-check-label" for="answer['.$questionId.']">'.$v.'</label>
				       </span>';
            }
            $assessmentTemp = $a;
        }
        if (strlen($assessmentTemp) > 0) {
            return preg_replace('/\{(.*?)\}/im', $assessmentTemp, $content);
        }
        $i = 0;
        $v = $matches[1][0] ?? '';
        $answerField = '<span class="form-check form-check-inline">
                        <input type="radio" value="'.($i + 1).'" name="answer['.$questionId.']" class="form-check-input" data-index="'.$i.'"  data-ans="'.($lastAnswer ?? '').'"'.(($lastAnswer === $i + 1) ? 'checked' : ($disable ? 'disabled' : '')).'>
                        <label class="form-check-label" for="answer['.$questionId.']">'.$v.'</label>
				       </span>';

        return preg_replace('/\{(.*?)\}/im', $answerField, $content);
    }

    public static function populateInput($questionId, $content, $lastAnswer = null, $disabled = false)
    {
        preg_match_all('/\{(.*?)\}/im', $content, $matches);
        $answerFields = [];

        foreach ($matches[1] as $index => $match) {
            $answerFields[] = '<span class="col-3">
                                <input type="text" name="answer['.$questionId.']['.$index.']" class="fill_in_blank" data-index="'.$index.'" value="'.($lastAnswer[$index] ?? '').'" '.($disabled ? 'disabled' : '').' />
                              </span>';
        }

        foreach ($answerFields as $index => $field) {
            $content = preg_replace('/\{'.preg_quote($matches[1][$index], '/').'\}/', $field, $content, 1);
        }

        return $content;
    }

    public static function getLogoAsset()
    {
        return config('settings.site.site_logo', 'https://v2.keyinstitute.com.au/storage/photos/1/Site/62f83337d1769.png');
    }

    public static function getLogoBase64()
    {
        $logo = self::getLogoAsset();

        return 'data:image/png;base64,'.base64_encode(file_get_contents($logo));
    }

    public static function formatHoursToHHMM(float $decimalInput): string
    {
        $hours = intval($decimalInput);
        $minutes = round(($decimalInput - $hours) * 60);

        return sprintf('%02d:%02d', $hours, $minutes); // sprintf( '%02d:%02d', (int)$decimalInput, fmod( $decimalInput, 1 ) * 60 );
    }

    public static function debug($data, $method = 'dump', $username = 'mohsina')
    {
        if (Auth::user()->username === $username) {
            $method($data);
        }
    }

    /**
     * Format attribute name to Title case and remove underscores.
     */
    public static function formatAttribute(string $attribute): string
    {
        return ucwords(str_replace('_', ' ', $attribute));
    }

    public static function ensureDirectoryWithPermissions($relativePath, $permission = 0755)
    {
        // Make sure the directory exists
        if (!Storage::exists($relativePath)) {
            Storage::makeDirectory($relativePath);
        }
        // v2.keyinstitute.com.au/storage/app/public/user/20308
        // Extract path after 'public/' only
        $prefix = 'public/';
        if (Str::startsWith($relativePath, $prefix)) {
            $subPath = Str::after($relativePath, $prefix);
            $segments = explode('/', $subPath);

            $basePath = storage_path('app/public');
            $currentPath = $basePath;

            foreach ($segments as $segment) {
                $currentPath .= '/'.$segment;

                if (is_dir($currentPath)) {
                    chmod($currentPath, $permission); // Apply chmod only to these subfolders
                }
            }
        }
    }

    public static function parseDate($value, $timezone = false, $format = 'd-m-Y')
    {
        if (empty($value)) {
            return;
        }
        $parsedDate = \Carbon\Carbon::parse($value);
        if ($timezone) {
            $parsedDate = $parsedDate->setTimezone($timezone);
        }

        return $parsedDate->format($format);
    }

    public static function parseDateTime($value, $timezone = false, $format = 'j F, Y g:i A')
    {
        if (empty($value)) {
            return;
        }
        $parsedDate = \Carbon\Carbon::parse($value);
        if ($timezone) {
            $parsedDate = $parsedDate->setTimezone($timezone);
        }

        return $parsedDate->format($format);
    }

    public static function isNewLLNDCompleted($userId): bool
    {
        $attempt = QuizAttempt::where('user_id', $userId)
            ->where('quiz_id', config('lln.quiz_id'))
            ->where('system_result', 'COMPLETED')
            ->where('status', 'SATISFACTORY')
            ->latest()
            ->first();

        return !empty($attempt);
    }

    public static function hasNewLLND($userId): bool
    {
        $attempt = QuizAttempt::where('user_id', $userId)
            ->where('quiz_id', config('lln.quiz_id'))
            ->first();

        return !empty($attempt);
    }

    /**
     * Check if a course is in excluded categories (skips LLND logic).
     *
     * @param string $courseCategory
     */
    public static function isLLNDExcluded($courseCategory): bool
    {
        $excludedCategories = config('lln.excluded_categories', ['non_accredited', 'accelerator']);

        return in_array($courseCategory, $excludedCategories);
    }

    /**
     * Check if a course is in excluded categories (skips PTR logic).
     *
     * @param string $courseCategory
     */
    public static function isPTRExcluded($courseCategory): bool
    {
        $excludedCategories = config('ptr.excluded_categories', ['non_accredited', 'accelerator']);

        return in_array($courseCategory, $excludedCategories);
    }

    /**
     * Convert query builder dump output to readable SQL with bindings replaced.
     *
     * @param string $queryDump The output from $query->dump()
     * @return string The formatted SQL statement with bindings replaced
     */
    public static function formatQueryDump($queryDump)
    {
        // Extract the SQL and bindings from the dump output
        if (preg_match('/SQL: (.+?)(?:\n|$)/', $queryDump, $sqlMatches)) {
            $sql = $sqlMatches[1];
        } else {
            return "Could not extract SQL from dump output";
        }

        // Extract bindings
        $bindings = [];
        if (preg_match('/Bindings: \[(.+?)\]/', $queryDump, $bindingMatches)) {
            $bindingString = $bindingMatches[1];
            // Parse the bindings array
            preg_match_all('/"([^"]+)"|(\d+)/', $bindingString, $bindingValues);
            $bindings = array_filter($bindingValues[0]); // Remove empty values
        }

        // Replace question marks with actual binding values
        $formattedSql = $sql;
        foreach ($bindings as $binding) {
            // Handle different binding types
            if (is_numeric($binding)) {
                $formattedSql = preg_replace('/\?/', $binding, $formattedSql, 1);
            } else {
                // String binding - wrap in quotes
                $formattedSql = preg_replace('/\?/', "'" . addslashes($binding) . "'", $formattedSql, 1);
            }
        }

        return $formattedSql;
    }

    /**
     * Get formatted SQL from query builder for debugging.
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @return string The formatted SQL statement with bindings replaced
     */
    public static function getFormattedSql($query)
    {
        $sql = $query->toSql();
        $bindings = $query->getBindings();

        $formattedSql = $sql;
        foreach ($bindings as $binding) {
            if (is_numeric($binding)) {
                $formattedSql = preg_replace('/\?/', $binding, $formattedSql, 1);
            } else {
                $formattedSql = preg_replace('/\?/', "'" . addslashes($binding) . "'", $formattedSql, 1);
            }
        }

        return $formattedSql;
    }
}
