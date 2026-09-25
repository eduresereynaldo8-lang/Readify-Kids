<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>Student Reading Progress Report</title>
    @include('teacher.students.pdf-styles')
</head>
<body>
    <h1>READIFY KIDS</h1>
    <h2>Student Reading Progress Report</h2>
    <table class="meta">
        <tr>
            <td><strong>Teacher:</strong> {{ $teacher->firstname }} {{ $teacher->lastname }}</td>
            <td><strong>Section:</strong> {{ $filters['section'] ?? 'All sections' }}</td>
            <td><strong>Generated Date:</strong> {{ $generatedAt->format('M d, Y h:i A T') }}</td>
        </tr>
        <tr>
            <td><strong>Report Filter:</strong> {{ $reportFilter }}</td>
            <td><strong>Level:</strong> {{ $filters['level'] ?? 'All levels' }}</td>
            <td><strong>Search:</strong> {{ $filters['search'] ?? 'None' }}</td>
        </tr>
    </table>
    <table class="summary">
        <tr>
            <td>Total Students: <strong>{{ $summary['total'] }}</strong></td>
            <td>On Track: <strong>{{ $summary['on_track'] }}</strong></td>
            <td>Needs Help: <strong>{{ $summary['needs_help'] }}</strong></td>
            <td>Struggling: <strong>{{ $summary['struggling'] }}</strong></td>
            <td>No Data: <strong>{{ $summary['no_data'] }}</strong></td>
        </tr>
    </table>
    <table class="class-report">
        <thead>
            <tr>
                <th style="width:3%;">No.</th>
                <th style="width:15%;">Student Name</th>
                <th style="width:10%;">LRN No.</th>
                <th style="width:3%;">Age</th>
                <th style="width:5%;">Gender</th>
                <th style="width:8%;">Section</th>
                <th style="width:5%;">Current Level</th>
                <th style="width:7%;">Activities Completed</th>
                <th style="width:7%;">Average Score</th>
                <th style="width:9%;">Average Oral Reading</th>
                <th style="width:10%;">Average Comprehension</th>
                <th style="width:10%;">Reading Status</th>
                <th style="width:8%;">Total Points</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $row['student']->firstname }} {{ $row['student']->lastname }}</td>
                <td class="lrn">{{ $row['student']->lrn_no ?? '—' }}</td>
                <td>{{ $row['student']->age ?? '—' }}</td>
                <td>{{ $row['student']->gender ?? '—' }}</td>
                <td>{{ $row['student']->section ?? '—' }}</td>
                <td class="number">{{ $row['student']->current_level }}</td>
                <td class="number">{{ $row['activities_completed'] }}</td>
                <td class="number">{{ $row['average_score'] === null ? '—' : number_format($row['average_score'], 2).'%' }}</td>
                <td class="number">{{ $row['average_oral_reading'] === null ? '—' : number_format($row['average_oral_reading'], 2).'%' }}</td>
                <td class="number">{{ $row['average_comprehension'] === null ? '—' : number_format($row['average_comprehension'], 2).'%' }}</td>
                <td><span class="badge {{ $row['status']['key'] }}">{{ $row['status']['label'] }}</span></td>
                <td class="number">{{ $row['student']->total_points }}</td>
            </tr>
            @empty
            <tr><td colspan="13" class="empty">No students matched this report.</td></tr>
            @endforelse
        </tbody>
    </table>
    <p class="note">Average Score uses scored, completed activity results. On Track: 75% or above; Needs Help: 50% to below 75%; Struggling: below 50%. No Data: no completed scored result. Status uses the unrounded average.</p>
    <p class="note">Oral Reading and Comprehension use saved reading assessments. Missing values are excluded from averages; — means no data. Counts reflect only the students in this report.</p>
</body>
</html>
