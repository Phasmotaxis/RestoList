<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;

/**
 * Concierge Controller: Deterministic dining recommendation engine.
 *
 * Ranks dining slots deterministically using rule-based scoring:
 * - Dietary preferences matching (+40 points)
 * - Non-peak dining windows (+30 points for quieter dining and kitchen pacing)
 * - Booking confidence / ample availability (+20 points for green status slots)
 * - High restaurant rating (+10 points for average rating >= 4.0)
 */
class ConciergeController
{
    /**
     * Get concierge recommendations for a dining date and party.
     *
     * Route: GET /concierge/recommendations
     *
     * @param Request $request HTTP request.
     * @param Response $response HTTP response.
     *
     * @return void
     */
    public function getRecommendations(Request $request, Response $response): void
    {
        $orgId = trim((string) ($request->getParam('restaurant_id') ?? $request->getParam('org_id') ?? ''));
        $dietary = trim((string) ($request->getParam('dietary') ?? ''));
        $partySize = max(1, min(20, (int) ($request->getParam('party_size') ?? 2)));
        $date = trim((string) ($request->getParam('date') ?? date('Y-m-d')));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $date = date('Y-m-d');
        }

        $pdo = Database::getConnection();

        // Target restaurants
        $restQuery = 'SELECT id, name, subdomain FROM tenants WHERE is_active = 1';
        $params = [];
        if ($orgId !== '') {
            $restQuery .= ' AND (id = :id OR subdomain = :id)';
            $params[':id'] = $orgId;
        }
        $restQuery .= ' ORDER BY name ASC LIMIT 10';

        $restStmt = $pdo->prepare($restQuery);
        $restStmt->execute($params);
        $restaurants = $restStmt->fetchAll();

        $recommendations = [];

        foreach ($restaurants as $rest) {
            $rId = $rest['id'];

            // 1. Dietary match check
            $hasDietaryMatch = false;
            if ($dietary !== '') {
                $dStmt = $pdo->prepare('SELECT COUNT(*) as cnt FROM menu_items WHERE org_id = :org_id AND is_available = 1 AND dietary_tags LIKE :dietary');
                $dStmt->execute([
                    ':org_id' => $rId,
                    ':dietary' => '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $dietary) . '%',
                ]);
                $hasDietaryMatch = ((int) ($dStmt->fetch()['cnt'] ?? 0)) > 0;
            }

            // 2. Average rating
            $revStmt = $pdo->prepare('SELECT AVG(rating) as avg_rating FROM reviews WHERE org_id = :org_id AND (approved = 1 OR approved IS NULL)');
            $revStmt->execute([':org_id' => $rId]);
            $avgRating = round((float) ($revStmt->fetch()['avg_rating'] ?? 0.0), 1);

            // 3. Fetch tables
            $tStmt = $pdo->prepare('SELECT id, capacity FROM dining_tables WHERE org_id = :org_id AND capacity >= :capacity AND archived_at IS NULL');
            $tStmt->execute([':org_id' => $rId, ':capacity' => $partySize]);
            $tables = $tStmt->fetchAll();

            if (empty($tables)) {
                continue;
            }

            // 4. Sample slot times (12:00, 14:00, 17:30, 19:00, 20:30, 21:00)
            $sampleTimes = ['12:00', '13:30', '15:00', '17:00', '18:30', '20:00', '21:00'];

            foreach ($sampleTimes as $time) {
                $slotStart = "{$date} {$time}:00";
                $slotEnd = date('Y-m-d H:i:s', strtotime($slotStart) + 5400);

                // Check conflict
                $conflictStmt = $pdo->prepare('SELECT COUNT(DISTINCT table_id) as booked FROM reservation_slots WHERE org_id = :org_id AND slot_start_utc >= :start AND slot_start_utc < :end');
                $conflictStmt->execute([':org_id' => $rId, ':start' => $slotStart, ':end' => $slotEnd]);
                $bookedCount = (int) ($conflictStmt->fetch()['booked'] ?? 0);

                $availableTables = count($tables) - $bookedCount;
                if ($availableTables <= 0) {
                    continue;
                }

                // Scoring rules
                $score = 100;
                $reasons = [];

                // Dietary bonus
                if ($hasDietaryMatch) {
                    $score += 40;
                    $reasons[] = "Features verified {$dietary} menu options";
                }

                // Non-peak bonus (14:00 - 17:00, 21:00)
                $hour = (int) substr($time, 0, 2);
                if (($hour >= 13 && $hour <= 16) || $hour >= 21) {
                    $score += 30;
                    $reasons[] = 'Non-peak dining window with relaxed kitchen pacing';
                }

                // Booking confidence bonus
                if ($availableTables > 1) {
                    $score += 20;
                    $reasons[] = 'High table availability guarantee';
                }

                // High rating bonus
                if ($avgRating >= 4.0) {
                    $score += 10;
                    $reasons[] = "Top diner rating ({$avgRating} / 5.0 stars)";
                }

                $recommendations[] = [
                    'restaurant_id' => $rId,
                    'restaurant_name' => $rest['name'],
                    'date' => $date,
                    'time' => $time,
                    'slot_start_utc' => $slotStart,
                    'party_size' => $partySize,
                    'score' => $score,
                    'reasons' => $reasons,
                    'booking_url' => "/bookings/new?restaurant_id={$rId}&party_size={$partySize}&date={$date}",
                ];
            }
        }

        // Sort by score descending, then time ascending
        usort($recommendations, function ($a, $b) {
            if ($b['score'] !== $a['score']) {
                return $b['score'] <=> $a['score'];
            }
            return strcmp($a['time'], $b['time']);
        });

        // Top 10 recommendations
        $top = array_slice($recommendations, 0, 10);

        $response->json([
            'date' => $date,
            'party_size' => $partySize,
            'dietary_preference' => $dietary !== '' ? $dietary : null,
            'total_recommendations' => count($top),
            'recommendations' => $top,
        ], 200);
    }
}
