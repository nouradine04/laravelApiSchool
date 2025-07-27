<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Teacher extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'teacher_number',
        'specialization',
        'hire_date',
        'is_active',
    ];

    protected $casts = [
        'hire_date' => 'date',
        'is_active' => 'boolean',
    ];

    // Relations
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'teacher_subjects');
    }

    public function classes()
    {
        return $this->belongsToMany(Classe::class, 'teacher_classes');
    }

    public function grades()
    {
        return $this->hasMany(Grade::class);
    }

    // Accessors
    public function getFullNameAttribute()
    {
        return $this->user->name;
    }
}
