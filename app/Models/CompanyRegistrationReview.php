<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyRegistrationReview extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'company_id',
        'reviewer_id',
        'decision',
        'reason',
        'ip_address',
        'user_agent',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }
}
