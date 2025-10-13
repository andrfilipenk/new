<?php
// app/Base/Controller/Dashboard.php
namespace Base\Controller;

use Core\Mvc\Controller;

class Dashboard extends Controller
{

    public function indexAction()
    {
        // Get current date or date from request
        $currentDate = $this->getRequest()->get('date') ? new \DateTime($this->getRequest()->get('date')) : new \DateTime();
        $view = $this->getRequest()->get('view', 'month'); // Default to month view
        
        // Generate calendar data based on view
        $calendarData = $this->getCalendarData($currentDate, $view);
        
        return $this->render('index/index', [
            'calendarData' => $calendarData,
            'currentDate' => $currentDate,
            'display' => $view,
            'demoTasks' => $this->getDemoTasks()
        ]);
    }
    
    private function getCalendarData(\DateTime $date, string $view): array
    {
        switch ($view) {
            case 'week':
                return $this->getWeekData($date);
            case 'month':
                return $this->getMonthData($date);
            case 'year':
                return $this->getYearData($date);
            default:
                return $this->getWeekData($date);
        }
    }
    
    private function getWeekData(\DateTime $date): array
    {
        $startOfWeek = clone $date;
        $startOfWeek->modify('monday this week');
        
        $days = [];
        for ($i = 0; $i < 7; $i++) {
            $currentDay = clone $startOfWeek;
            $currentDay->modify("+{$i} days");
            
            $days[] = [
                'date' => $currentDay,
                'day' => $currentDay->format('j'),
                'dayName' => $currentDay->format('l'),
                'dayShort' => $currentDay->format('D'),
                'isToday' => $currentDay->format('Y-m-d') === (new \DateTime())->format('Y-m-d'),
                'isCurrentMonth' => $currentDay->format('m') === $date->format('m')
            ];
        }
        
        return [
            'type' => 'week',
            'title' => $startOfWeek->format('M j') . ' - ' . $startOfWeek->modify('+6 days')->format('M j, Y'),
            'days' => $days,
            'prev' => (clone $date)->modify('-1 week')->format('Y-m-d'),
            'next' => (clone $date)->modify('+1 week')->format('Y-m-d')
        ];
    }
    
    private function getMonthData(\DateTime $date): array
    {
        $firstDay = new \DateTime($date->format('Y-m-01'));
        $lastDay = new \DateTime($date->format('Y-m-t'));
        
        // Start from Monday of the week containing the first day
        $startDate = clone $firstDay;
        $startDate->modify('monday this week');
        
        // End on Sunday of the week containing the last day
        $endDate = clone $lastDay;
        $endDate->modify('sunday this week');
        
        $days = [];
        $current = clone $startDate;
        
        while ($current <= $endDate) {
            $days[] = [
                'date' => clone $current,
                'day' => $current->format('j'),
                'isToday' => $current->format('Y-m-d') === (new \DateTime())->format('Y-m-d'),
                'isCurrentMonth' => $current->format('m') === $date->format('m')
            ];
            $current->modify('+1 day');
        }
        
        return [
            'type' => 'month',
            'title' => $date->format('F Y'),
            'days' => $days,
            'prev' => (clone $date)->modify('-1 month')->format('Y-m-d'),
            'next' => (clone $date)->modify('+1 month')->format('Y-m-d')
        ];
    }
    
    private function getYearData(\DateTime $date): array
    {
        $months = [];
        
        for ($month = 1; $month <= 12; $month++) {
            $monthDate = new \DateTime($date->format('Y') . '-' . str_pad($month, 2, '0', STR_PAD_LEFT) . '-01');
            $months[] = [
                'date' => $monthDate,
                'name' => $monthDate->format('F'),
                'shortName' => $monthDate->format('M'),
                'isCurrentMonth' => $monthDate->format('m') === (new \DateTime())->format('m') && 
                                   $monthDate->format('Y') === (new \DateTime())->format('Y')
            ];
        }
        
        return [
            'type' => 'year',
            'title' => $date->format('Y'),
            'months' => $months,
            'prev' => (clone $date)->modify('-1 year')->format('Y-m-d'),
            'next' => (clone $date)->modify('+1 year')->format('Y-m-d')
        ];
    }
    
    private function getDemoTasks(): array
    {
        return [
            [
                'id' => 1,
                'title' => 'Project Meeting',
                'start' => date('Y-m-d', strtotime('+1 day')),
                'end' => date('Y-m-d', strtotime('+1 day')),
                'color' => '#007bff'
            ],
            [
                'id' => 2,
                'title' => 'Development Sprint',
                'start' => date('Y-m-d', strtotime('+3 days')),
                'end' => date('Y-m-d', strtotime('+15 days')),
                'color' => '#28a745'
            ],
            [
                'id' => 3,
                'title' => 'Client Review',
                'start' => date('Y-m-d', strtotime('+7 days')),
                'end' => date('Y-m-d', strtotime('+7 days')),
                'color' => '#ffc107'
            ],
            [
                'id' => 4,
                'title' => 'Testing Phase',
                'start' => date('Y-m-d', strtotime('+10 days')),
                'end' => date('Y-m-d', strtotime('+12 days')),
                'color' => '#dc3545'
            ]
        ];
    }
}