<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Classe extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'level',
        'academic_year',
        'capacity',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relations
    public function students()
    {
        return $this->hasMany(Student::class, 'class_id');
    }

    public function teachers()
    {
        return $this->belongsToMany(Teacher::class, 'teacher_classes');
    }

    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'class_subjects');
    }

    public function grades()
    {
        return $this->hasMany(Grade::class);
    }

    // Accessors
    public function getStudentCountAttribute()
    {
        return $this->students()->count();
    }
}
