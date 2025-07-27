<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'coefficient',
        'level',
        'description',
        'is_active',
    ];

    protected $casts = [
        'coefficient' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // Relations
    public function teachers()
    {
        return $this->belongsToMany(Teacher::class, 'teacher_subjects');
    }

    public function classes()
    {
        return $this->belongsToMany(Classe::class, 'class_subjects');
    }

    public function grades()
    {
        return $this->hasMany(Grade::class);
    }
}
