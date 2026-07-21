<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'status',
        'complexity',
        'project_id',
        'user_id',
        'transactions',
        'entities',
        'team_exp',
        'manager_exp',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function estimations()
    {
        return $this->hasMany(Estimation::class);
    }
}