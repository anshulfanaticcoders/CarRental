<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when an external supplier reservation could not be confirmed because the
 * outcome is unknown (e.g. the supplier was contacted but timed out). The supplier
 * may already hold a reservation, so the booking must go to manual review rather
 * than being retried automatically — a blind retry risks a duplicate reservation.
 */
class ReservationOutcomeUnknownException extends RuntimeException {}
