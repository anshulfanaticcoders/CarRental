<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class UserReportDownloadController extends Controller
{
    public function downloadXML()
    {
        try {
            // Enable error reporting for debugging
            ini_set('display_errors', 1);
            error_reporting(E_ALL);

            Log::info('Starting XML report generation');

            // Get user data with error handling
            $users = User::where('role', 'customer')
                ->with(['activityLogs' => function ($query) {
                    $query->latest()->take(5);
                }])
                ->get();

            Log::info('Retrieved users data', ['count' => $users->count()]);

            // Create XML with error handling
            $dom = new \DOMDocument('1.0', 'UTF-8');
            $dom->formatOutput = true;

            // Create root element
            $root = $dom->createElement('userReport');
            $dom->appendChild($root);

            // Add metadata
            $metadata = $dom->createElement('metadata');
            $root->appendChild($metadata);

            $generatedDate = $dom->createElement('generatedDate', Carbon::now()->toDateTimeString());
            $metadata->appendChild($generatedDate);

            $totalUsers = $dom->createElement('totalUsers', $users->count());
            $metadata->appendChild($totalUsers);

            // Add users data
            $usersElement = $dom->createElement('users');
            $root->appendChild($usersElement);

            foreach ($users as $user) {
                $userElement = $dom->createElement('user');
                $usersElement->appendChild($userElement);

                // Basic user info
                $userElement->appendChild($dom->createElement('id', $user->id));
                $userElement->appendChild($dom->createElement('firstName', $this->sanitizeXMLString($user->first_name)));
                $userElement->appendChild($dom->createElement('lastName', $this->sanitizeXMLString($user->last_name)));
                $userElement->appendChild($dom->createElement('email', $this->sanitizeXMLString($user->email)));
                $userElement->appendChild($dom->createElement('createdAt', $user->created_at->toDateTimeString()));
                $userElement->appendChild($dom->createElement('lastLoginAt', $user->last_login_at ?? ''));

                // Add activities if they exist
                if ($user->activityLogs->isNotEmpty()) {
                    $activitiesElement = $dom->createElement('activities');
                    $userElement->appendChild($activitiesElement);

                    foreach ($user->activityLogs as $activity) {
                        $activityElement = $dom->createElement('activity');
                        $activitiesElement->appendChild($activityElement);

                        $activityElement->appendChild($dom->createElement('description', 
                            $this->sanitizeXMLString($activity->activity_description)));
                        $activityElement->appendChild($dom->createElement('date', 
                            $activity->created_at->toDateTimeString()));
                    }
                }
            }

            Log::info('XML generation completed');

            // Generate response with proper headers
            $xmlContent = $dom->saveXML();

            return response($xmlContent)
                ->header('Content-Type', 'application/xml')
                ->header('Content-Disposition', 'attachment; filename="user-report-' . Carbon::now()->format('Y-m-d') . '.xml"')
                ->header('Cache-Control', 'no-cache, no-store, must-revalidate');

        } catch (\Exception $e) {
            Log::error('Error generating XML report', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Error generating report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Sanitize string for XML content
     */
    private function sanitizeXMLString($string)
    {
        return htmlspecialchars(strip_tags($string), ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}