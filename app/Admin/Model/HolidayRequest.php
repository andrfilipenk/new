<?php
// app/Admin/Model/HolidayRequest.php
namespace Admin\Model;

use Core\Database\Model;
use Admin\Model\User;

class HolidayRequest extends Model
{
    protected $table = 'user_holiday_request';
    protected $primaryKey = 'id';
    protected array $fillable = ['user_id', 'begin_date', 'end_date', 'granted'];

    /**
     * Get the user that owns the holiday request
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Create a new holiday request
     */
    public static function createRequest(int $userId, string $beginDate, string $endDate): ?self
    {
        $request = new self();
        $request->user_id = $userId;
        $request->begin_date = $beginDate;
        $request->end_date = $endDate;
        $request->granted = 0; // Use 0 instead of false for integer column
        return $request->save() ? $request : null;
    }

    /**
     * Grant or deny the request
     */
    public function setGranted(bool $granted): bool
    {
        $this->granted = $granted ? 1 : 0; // Convert boolean to integer
        return $this->save();
    }

    /**
     * Check if request is granted
     */
    public function isGranted(): bool
    {
        return (bool) $this->granted; // Convert integer to boolean
    }

    /**
     * Get duration in days
     */
    public function getDurationDays(): int
    {
        $begin = new \DateTime($this->begin_date);
        $end = new \DateTime($this->end_date);
        return $end->diff($begin)->days + 1;
    }

    /**
     * Check if request dates are valid
     */
    public function isValidDateRange(): bool
    {
        $begin = new \DateTime($this->begin_date);
        $end = new \DateTime($this->end_date);
        return $begin <= $end;
    }
}