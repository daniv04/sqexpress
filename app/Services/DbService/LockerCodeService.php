<?php

namespace App\Services\DbService;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class LockerCodeService
{
    /**
     * Generate the next sequential locker code.
     * Format: SQECR01, SQECR02, ..., SQECR09, SQECR10, ..., SQECR100, etc.
     * Ensures the generated code doesn't already exist in the database.
     *
     * @return string
     */
    public function generateNextLockerCode(): string
    {
        // Get the last user by ID (most recent registration)
        $lastUser = User::whereNotNull('locker_code')
            ->orderBy('id', 'desc')
            ->first();

        // If no locker code exists, start with SQECR01
        if (!$lastUser) {
            $nextNumber = 1;
        } else {
            // Extract the number from the last locker code
            $lastNumber = $this->extractNumberFromCode($lastUser->locker_code);
            // Increment for the new locker code
            $nextNumber = $lastNumber + 1;
        }

        // Ensure the generated code doesn't already exist
        while (User::where('locker_code', $this->formatLockerCode($nextNumber))->exists()) {
            $nextNumber++;
        }

        return $this->formatLockerCode($nextNumber);
    }

    /**
     * Extract the numeric part from a locker code.
     * Handles legacy formats "SQE-XXXX" and "SQEXX" and current format "SQECRXX"
     * Example: "SQECR05" -> 5, "SQE05" -> 5, "SQE-1234" -> 1234
     *
     * @param string $code
     * @return int
     */
    private function extractNumberFromCode(string $code): int
    {
        // Keep only the digits, regardless of prefix
        return (int) preg_replace('/\D/', '', $code);
    }

    /**
     * Format a number as a locker code.
     * Example: 1 -> "SQECR01", 9 -> "SQECR09", 11 -> "SQECR11", 245 -> "SQECR245"
     *
     * @param int $number
     * @return string
     */
    private function formatLockerCode(int $number): string
    {
        // Format as SQECR + number, padded to 2 digits minimum
        return 'SQECR' . str_pad($number, 2, '0', STR_PAD_LEFT);
    }
}
