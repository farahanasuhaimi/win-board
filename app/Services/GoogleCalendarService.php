<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleCalendarService
{
    public function getTodayEvents(User $user): array
    {
        try {
            $token = $this->getValidToken($user);
            if (!$token) return [];

            $timeMin = now()->startOfDay()->toIso8601String();
            $timeMax = now()->endOfDay()->toIso8601String();

            $response = Http::withToken($token)
                ->get('https://www.googleapis.com/calendar/v3/calendars/primary/events', [
                    'timeMin'      => $timeMin,
                    'timeMax'      => $timeMax,
                    'singleEvents' => 'true',
                    'orderBy'      => 'startTime',
                    'maxResults'   => 20,
                ]);

            if (!$response->ok()) return [];

            return collect($response->json()['items'] ?? [])
                ->map(fn($e) => $this->formatEvent($e))
                ->filter()
                ->values()
                ->all();

        } catch (\Throwable $e) {
            Log::warning('Google Calendar fetch failed: ' . $e->getMessage());
            return [];
        }
    }

    private function getValidToken(User $user): ?string
    {
        if (!$user->google_access_token) return null;

        // Refresh if token expires within 5 minutes
        if ($user->google_token_expires_at && $user->google_token_expires_at->lt(now()->addMinutes(5))) {
            if (!$user->google_refresh_token) return null;

            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'client_id'     => config('services.google.client_id'),
                'client_secret' => config('services.google.client_secret'),
                'refresh_token' => $user->google_refresh_token,
                'grant_type'    => 'refresh_token',
            ]);

            if (!$response->ok()) return null;

            $data = $response->json();

            $user->update([
                'google_access_token'     => $data['access_token'],
                'google_token_expires_at' => now()->addSeconds($data['expires_in'] ?? 3600),
            ]);

            return $data['access_token'];
        }

        return $user->google_access_token;
    }

    private function formatEvent(array $event): ?array
    {
        $summary = $event['summary'] ?? '(No title)';
        $isAllDay = isset($event['start']['date']) && !isset($event['start']['dateTime']);

        if ($isAllDay) {
            return [
                'title'      => $summary,
                'all_day'    => true,
                'start_time' => null,
                'end_time'   => null,
                'duration'   => null,
            ];
        }

        $start = new \DateTime($event['start']['dateTime']);
        $end   = new \DateTime($event['end']['dateTime']);
        $duration = ($end->getTimestamp() - $start->getTimestamp()) / 60; // minutes

        return [
            'title'      => $summary,
            'all_day'    => false,
            'start_time' => $start->format('H:i'),
            'end_time'   => $end->format('H:i'),
            'duration'   => (int) $duration,
        ];
    }
}
