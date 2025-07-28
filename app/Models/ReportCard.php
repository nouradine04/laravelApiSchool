<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportCard extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'class_id',
        'period',
        'academic_year',
        'general_average',
        'rank',
        'mention',
        'appreciation',
        'pdf_path',
        'generated_at',
    ];

    protected $casts = [
        'general_average' => 'decimal:2',
        'generated_at' => 'datetime',
    ];

    // Relations
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function classe()
    {
        return $this->belongsTo(Classe::class, 'class_id');
    }

    // Accessors
    public function getMentionTextAttribute()
    {
        $mentions = [
            'E' => 'Excellent',
            'TB' => 'Très Bien',
            'B' => 'Bien',
            'AB' => 'Assez Bien',
            'P' => 'Passable',
            'I' => 'Insuffisant',
        ];

        return $mentions[$this->mention] ?? 'Non défini';
    }
}
