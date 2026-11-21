<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;

/**
 * Customer Controller: Unified facade for Customer Portal operations (Userspace 3).
 *
 * Coordinates diner-facing interactions across discovery, slot availability,
 * booking creation, waitlist registration, and review submission.
 */
class CustomerController
{
    private DiscoveryController $discovery;
    private SlotAvailabilityController $slots;
    private BookingFlowController $booking;
    private MenuSyncController $menu;
    private WaitlistPortalController $waitlist;
    private ReviewSubmissionController $reviews;
    private DepositPortalController $deposits;
    private LoyaltyPortalController $loyalty;
    private ConciergeController $concierge;

    public function __construct()
    {
        $this->discovery = new DiscoveryController();
        $this->slots = new SlotAvailabilityController();
        $this->booking = new BookingFlowController();
        $this->menu = new MenuSyncController();
        $this->waitlist = new WaitlistPortalController();
        $this->reviews = new ReviewSubmissionController();
        $this->deposits = new DepositPortalController();
        $this->loyalty = new LoyaltyPortalController();
        $this->concierge = new ConciergeController();
    }

    /**
     * Customer portal index: Delegates to discovery.
     */
    public function index(Request $request, Response $response): void
    {
        $this->discovery->index($request, $response);
    }

    /**
     * Customer dashboard / profile view.
     */
    public function dashboard(Request $request, Response $response): void
    {
        $this->discovery->index($request, $response);
    }

    /**
     * Restaurant detail view.
     */
    public function restaurant(Request $request, Response $response): void
    {
        $this->discovery->show($request, $response);
    }

    /**
     * Search restaurants.
     */
    public function search(Request $request, Response $response): void
    {
        $this->discovery->search($request, $response);
    }

    /**
     * Check availability.
     */
    public function availability(Request $request, Response $response): void
    {
        $this->slots->getSlots($request, $response);
    }

    /**
     * New booking form.
     */
    public function newBooking(Request $request, Response $response): void
    {
        $this->booking->showForm($request, $response);
    }

    /**
     * Create booking.
     */
    public function createBooking(Request $request, Response $response): void
    {
        $this->booking->create($request, $response);
    }

    /**
     * Booking confirmation.
     */
    public function showBooking(Request $request, Response $response): void
    {
        $this->booking->showConfirmation($request, $response);
    }

    /**
     * Cancel booking.
     */
    public function cancelBooking(Request $request, Response $response): void
    {
        $this->booking->cancel($request, $response);
    }

    /**
     * Menu sync.
     */
    public function menu(Request $request, Response $response): void
    {
        $this->menu->getMenu($request, $response);
    }

    /**
     * Join waitlist.
     */
    public function joinWaitlist(Request $request, Response $response): void
    {
        $this->waitlist->join($request, $response);
    }

    /**
     * Track waitlist position.
     */
    public function waitlistPosition(Request $request, Response $response): void
    {
        $this->waitlist->trackPosition($request, $response);
    }

    /**
     * Cancel waitlist entry.
     */
    public function cancelWaitlist(Request $request, Response $response): void
    {
        $this->waitlist->cancel($request, $response);
    }

    /**
     * Submit review.
     */
    public function submitReview(Request $request, Response $response): void
    {
        $this->reviews->create($request, $response);
    }

    /**
     * Deposit balance.
     */
    public function depositBalance(Request $request, Response $response): void
    {
        $this->deposits->getBalance($request, $response);
    }

    /**
     * Loyalty points.
     */
    public function loyaltyPoints(Request $request, Response $response): void
    {
        $this->loyalty->getPoints($request, $response);
    }

    /**
     * Concierge recommendations.
     */
    public function concierge(Request $request, Response $response): void
    {
        $this->concierge->getRecommendations($request, $response);
    }
}
