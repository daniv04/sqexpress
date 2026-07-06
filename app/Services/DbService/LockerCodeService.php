<?php

namespace App\Services\DbService;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class LockerCodeService
{
    /**
     * Generate the next sequential locker code.
     * Format: SQE01, SQE02, ..., SQE09, SQE10, SQE11, ..., SQE100, etc.
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

        // If no locker code exists, start with SQE01
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
     * Handles both old format "SQE-XXXX" and new format "SQEXX"
     * Example: "SQE00005" -> 5, "SQE05" -> 5, "SQE-1234" -> 1234
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
     * Example: 1 -> "SQE01", 9 -> "SQE09", 11 -> "SQE11", 245 -> "SQE245"
     *
     * @param int $number
     * @return string
     */
    private function formatLockerCode(int $number): string
    {
        // Format as SQE + number, padded to 2 digits minimum
        return 'SQE' . str_pad($number, 2, '0', STR_PAD_LEFT);
    }
}
