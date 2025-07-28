<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Grade extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'subject_id',
        'teacher_id',
        'class_id',
        'period',
        'grade_type',
        'value',
        'max_value',
        'date',
        'comment',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'max_value' => 'decimal:2',
        'date' => 'date',
    ];

    // Relations
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function classe()
    {
        return $this->belongsTo(Classe::class, 'class_id');
    }

    // Accessors
    public function getPercentageAttribute()
    {
        return ($this->value / $this->max_value) * 100;
    }
}
