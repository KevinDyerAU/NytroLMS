<!DOCTYPE html>
<html>
<head>
    <title>Inactivity Report</title>
</head>
<body>
    <h1>Inactivity Report for {{ $weeks }} Weeks</h1>
    <h3>{{ $inactiveDate.': '. $startOfWeek.' - '. $endOfWeek }}</h3>
    <p>The following students have been inactive for the past {{ $weeks }} weeks:</p>
    <ol>
        @foreach ($students as $student)
            <li>
                [{{ $student->id }}] - {{ $student->name }}  ({{ $student->email }}) - {{ implode(', ', $student->courseEnrolments->pluck('course.title')->toArray()) }}
            </li>
        @endforeach
    </ol>
</body>
</html>
