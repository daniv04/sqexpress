<?php

namespace App\Services\DbService;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class LockerCodeService
{
    /**
     * Generate the next sequential locker code.
     * Format: SQE00001, SQE00002, SQE00003, etc.
     * 
     * @return string
     */
    public function generateNextLockerCode(): string
    {
        // Get the last user by ID (most recent registration)
        $lastUser = User::whereNotNull('locker_code')
            ->orderBy('id', 'desc')
            ->first();

        // If no locker code exists, start with SQE00001
        if (!$lastUser) {
            return $this->formatLockerCode(1);
        }

        // Extract the number from the last locker code
        $lastNumber = $this->extractNumberFromCode($lastUser->locker_code);
        // Increment and format the new locker code
        $nextNumber = $lastNumber + 1;

        return $this->formatLockerCode($nextNumber);
    }

    /**
     * Extract the numeric part from a locker code.
     * Handles both old format "SQE-XXXX" and new format "SQEXXXXX"
     * Example: "SQE00005" -> 5, "SQE-1234" -> 1234
     * 
     * @param string $code
     * @return int
     */
    private function extractNumberFromCode(string $code): int
    {
        // Remove the "SQE" prefix, then remove any dashes
        $number = str_replace(['SQE', '-'], '', $code);
        return (int) $number;
    }

    /**
     * Format a number as a locker code.
     * Example: 1 -> "SQE00001", 5 -> "SQE00005", 123 -> "SQE00123"
     * 
     * @param int $number
     * @return string
     */
    private function formatLockerCode(int $number): string
    {
        // Format as SQE + 5-digit number with leading zeros
        return 'SQE' . str_pad($number, 5, '0', STR_PAD_LEFT);
    }
}
