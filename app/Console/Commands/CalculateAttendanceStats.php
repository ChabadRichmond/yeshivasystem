<?php

namespace App\Console\Commands;

use App\Models\AttendanceStats;
use App\Models\SchoolClass;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CalculateAttendanceStats extends Command
{
    protected $signature = 'attendance:calculate-stats 
                            {--period=monthly : Period type: daily, weekly, monthly, yearly}
                            {--class= : Specific class ID to calculate for}
                            {--student= : Specific student ID to calculate for}
                            {--start= : Start date (Y-m-d)}
                            {--end= : End date (Y-m-d)}
                            {--all : Calculate for all classes and overall}';

    protected $description = 'Calculate and cache attendance statistics for reporting';

    public function handle()
    {
        $periodType = $this->option('period');
        $classId = $this->option('class');
        $studentId = $this->option('student');
        $all = $this->option('all');

        // Determine date range
        $startDate = $this->option('start') 
            ? Carbon::parse($this->option('start')) 
            : $this->getDefaultStart($periodType);
        
        $endDate = $this->option('end') 
            ? Carbon::parse($this->option('end')) 
            : $this->getDefaultEnd($periodType);

        $this->info("Calculating {$periodType} stats from {$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')}");

        $totalCalculated = 0;

        if ($studentId) {
            // Calculate for specific student
            AttendanceStats::calculateForStudent($studentId, $classId, $periodType, $startDate, $endDate);
            $totalCalculated = 1;
            $this->info("Calculated stats for student ID: {$studentId}");
        } elseif ($classId) {
            // Calculate for all students in a class
            $results = AttendanceStats::calculateForClass($classId, $periodType, $startDate, $endDate);
            $totalCalculated = count($results);
            $this->info("Calculated stats for {$totalCalculated} students in class ID: {$classId}");
        } elseif ($all) {
            // Calculate for all classes
            $classes = SchoolClass::all();
            $this->output->progressStart($classes->count());
            
            foreach ($classes as $class) {
                $results = AttendanceStats::calculateForClass($class->id, $periodType, $startDate, $endDate);
                $totalCalculated += count($results);
                $this->output->progressAdvance();
            }
            
            $this->output->progressFinish();
            
            // Also calculate overall stats (no class filter)
            $this->info('Calculating overall stats...');
            $overallResults = AttendanceStats::calculateOverall($periodType, $startDate, $endDate);
            $totalCalculated += count($overallResults);
            
            $this->info("Calculated stats for all classes + overall");
        } else {
            $this->warn('Please specify --class, --student, or --all');
            return 1;
        }

        $this->info("✅ Done! Calculated {$totalCalculated} stat records.");
        return 0;
    }

    private function getDefaultStart($periodType)
    {
        return match($periodType) {
            'daily' => Carbon::today(),
            'weekly' => Carbon::now()->startOfWeek(),
            'monthly' => Carbon::now()->startOfMonth(),
            'yearly' => Carbon::now()->startOfYear(),
            default => Carbon::now()->startOfMonth(),
        };
    }

    private function getDefaultEnd($periodType)
    {
        return match($periodType) {
            'daily' => Carbon::today(),
            'weekly' => Carbon::now()->endOfWeek(),
            'monthly' => Carbon::now()->endOfMonth(),
            'yearly' => Carbon::now()->endOfYear(),
            default => Carbon::now()->endOfMonth(),
        };
    }
}
