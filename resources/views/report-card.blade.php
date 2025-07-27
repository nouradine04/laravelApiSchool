<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulletin de Notes</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            margin: 0;
            padding: 20px;
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .school-name {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .document-title {
            font-size: 16px;
            font-weight: bold;
            margin: 20px 0;
        }

        .student-info {
            display: table;
            width: 100%;
            margin-bottom: 30px;
        }

        .student-info-left,
        .student-info-right {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }

        .info-row {
            margin-bottom: 8px;
        }

        .info-label {
            font-weight: bold;
            display: inline-block;
            width: 120px;
        }

        .grades-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        .grades-table th,
        .grades-table td {
            border: 1px solid #333;
            padding: 8px;
            text-align: center;
        }

        .grades-table th {
            background-color: #f0f0f0;
            font-weight: bold;
        }

        .subject-name {
            text-align: left !important;
        }

        .summary {
            display: table;
            width: 100%;
            margin-top: 30px;
        }

        .summary-left,
        .summary-right {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }

        .summary-box {
            border: 2px solid #333;
            padding: 15px;
            margin: 10px;
        }

        .summary-title {
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 10px;
            text-align: center;
        }

        .appreciation {
            margin-top: 20px;
            padding: 15px;
            border: 1px solid #333;
        }

        .appreciation-title {
            font-weight: bold;
            margin-bottom: 10px;
        }

        .footer {
            margin-top: 40px;
            text-align: right;
        }

        .signature {
            margin-top: 30px;
        }
    </style>
</head>
<body>
<div class="header">
    <div class="school-name">ÉTABLISSEMENT SCOLAIRE</div>
    <div>Année Scolaire {{ $reportCard->academic_year }}</div>
    <div class="document-title">BULLETIN DE NOTES - {{ $reportCard->period }}</div>
</div>

<div class="student-info">
    <div class="student-info-left">
        <div class="info-row">
            <span class="info-label">Nom et Prénom :</span>
            {{ $student->user->name }}
        </div>
        <div class="info-row">
            <span class="info-label">N° Étudiant :</span>
            {{ $student->student_number }}
        </div>
        <div class="info-row">
            <span class="info-label">Date de naissance :</span>
            {{ $student->birth_date->format('d/m/Y') }}
        </div>
    </div>
    <div class="student-info-right">
        <div class="info-row">
            <span class="info-label">Classe :</span>
            {{ $student->classe->name }}
        </div>
        <div class="info-row">
            <span class="info-label">Niveau :</span>
            {{ $student->classe->level }}
        </div>
        <div class="info-row">
            <span class="info-label">Parent/Tuteur :</span>
            {{ $student->parent->user->name }}
        </div>
    </div>
</div>

<table class="grades-table">
    <thead>
    <tr>
        <th>Matières</th>
        <th>Coefficient</th>
        <th>Moyenne</th>
        <th>Points</th>
        <th>Rang</th>
    </tr>
    </thead>
    <tbody>
    @foreach($subjectAverages as $subjectAverage)
        <tr>
            <td class="subject-name">{{ $subjectAverage['subject']->name }}</td>
            <td>{{ $subjectAverage['coefficient'] }}</td>
            <td>{{ $subjectAverage['average'] }}/20</td>
            <td>{{ $subjectAverage['points'] }}</td>
            <td>-</td>
        </tr>
    @endforeach
    </tbody>
</table>

<div class="summary">
    <div class="summary-left">
        <div class="summary-box">
            <div class="summary-title">RÉSULTATS</div>
            <div class="info-row">
                <span class="info-label">Moyenne générale :</span>
                {{ $reportCard->general_average }}/20
            </div>
            <div class="info-row">
                <span class="info-label">Rang :</span>
                {{ $reportCard->rank }}/{{ $totalStudents }}
            </div>
            <div class="info-row">
                <span class="info-label">Mention :</span>
                {{ $reportCard->mention_text }}
            </div>
        </div>
    </div>
    <div class="summary-right">
        <div class="summary-box">
            <div class="summary-title">STATISTIQUES CLASSE</div>
            <div class="info-row">
                <span class="info-label">Effectif :</span>
                {{ $totalStudents }} élèves
            </div>
            <div class="info-row">
                <span class="info-label">Moyenne classe :</span>
                -/20
            </div>
        </div>
    </div>
</div>

<div class="appreciation">
    <div class="appreciation-title">APPRÉCIATION GÉNÉRALE :</div>
    <div>{{ $reportCard->appreciation }}</div>
</div>

<div class="footer">
    <div>Le {{ now()->format('d/m/Y') }}</div>
    <div class="signature">
        <div>Le Directeur</div>
        <div style="margin-top: 50px;">_________________</div>
    </div>
</div>
</body>
</html>
