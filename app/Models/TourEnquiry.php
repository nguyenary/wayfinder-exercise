<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TourEnquiry extends Model
{
    public const STATUS_NEW = 'new';

    public const STATUSES = ['new', 'contacted', 'booked', 'closed'];

    public const ALLOWED_STATUS_TRANSITIONS = [
        'new' => ['contacted'],
        'contacted' => ['booked', 'closed'],
        'booked' => ['closed'],
    ];

    protected $table = 'tour_enquiries';

    protected $fillable = [
        'tour_id',
        'name',
        'email',
        'phone',
        'preferred_month',
        'message',
        'status',
    ];

    public function tour()
    {
        return $this->belongsTo(Tour::class);
    }

    public function canTransitionTo(string $status): bool
    {
        return in_array($status, self::ALLOWED_STATUS_TRANSITIONS[$this->status] ?? [], true);
    }
}
