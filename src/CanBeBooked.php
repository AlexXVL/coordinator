<?php

namespace BinaryCats\Coordinator;

use BinaryCats\Coordinator\Contracts\Booking;
use BinaryCats\Coordinator\Contracts\CanBookResources;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;

/**
 * @mixin Model
 */
trait CanBeBooked
{
    /**
     * Boot the BooksResources trait for the model.
     *
     * @return void
     */
    public static function bootCanBeBooked()
    {
        static::deleted(fn (Model $model) => $model->bookings()->delete());
    }

    /**
     * This model can be a part of many bookings.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function bookings(): MorphMany
    {
        return $this->morphMany(Coordinator::bookingModel(), 'resource');
    }

    /**
     * True if the resource is available at a given argument.
     *
     * @param  string|\DateTimeInterface|Carbon|\Spatie\Period\Period  $at
     * @param  bool  $includeCanceled
     * @return bool
     */
    public function isAvailableAt($at, $includeCanceled = false): bool
    {
        return $this->bookings
            ->filter(fn (Booking $booking) => $booking->isCurrent($at))
            ->reject(fn (Booking $booking) => $includeCanceled ? false : $booking->is_canceled)
            ->isEmpty();
    }

    public function isAvailableAtWithQuantity($at, $quantity = 1, $includeCanceled = false): bool
    {
        if (!isset($this->capacity)) {
            return $this->isAvailableAt($at, $includeCanceled);
        }

        // Суммировать quantity всех пересекающихся бронирований
        $bookedQuantity = $this->bookings()
            ->where(function($query) use ($at) {
                $query->where('starts_at', '<=', $at)
                    ->where('ends_at', '>=', $at);
            })
            ->when(!$includeCanceled, function($query) {
                $query->whereNull('canceled_at');
            })
            ->sum('quantity');

        return ($this->capacity - $bookedQuantity) >= $quantity;
    }
    public function alreadyBookedAt($at, $includeCanceled = false): int
    {
        return $this->bookings()
            ->where(function($query) use ($at) {
                $query->where('starts_at', '<=', $at)
                    ->where('ends_at', '>=', $at);
            })
            ->when(!$includeCanceled, function($query) {
                $query->whereNull('canceled_at');
            })
            ->sum('quantity');
    }

    /**
     * True if the resource is not available at a given argument.
     *
     * @param  string|\DateTimeInterface|Carbon|\Spatie\Period\Period  $at
     * @param  bool  $includeCanceled
     * @return bool
     */
    public function isBookedAt($at, $includeCanceled = false): bool
    {
        return ! $this->isAvailableAt($at, $includeCanceled);
    }

    /**
     * True if the resource is available at a given argument.
     *
     * @param  \BinaryCats\Coordinator\Contracts\CanBookResources  $model
     * @param  \Closure  $closure
     * @return bool
     */
    public function isAvailableFor(CanBookResources $model, Closure $closure): bool
    {
        return $closure($model, $this);
    }

    /**
     * Create new Booking.
     *
     * @param  \BinaryCats\Coordinator\Contracts\CanBookResources  $model
     * @param  array  $attributes
     * @return \BinaryCats\Coordinator\Contracts\Booking
     */
    public function createBookingFor(CanBookResources $model, $attributes = []): Booking
    {
        return tap($this->makeBookingFor($model, $attributes), fn ($model) => $model->save());
    }

    public function createBookingWithQuantity($model, $attributes = [], $quantity = 1): Booking
    {
        if (!$this->isAvailableAtWithQuantity($attributes['starts_at'], $quantity)) {
            throw new \RuntimeException("Недостаточно доступных единиц");
        }

        $attributes['quantity'] = $quantity;

        return $this->createBookingFor($model, $attributes);
    }

    /**
     * Make a new Booking without saving it.
     *
     * @param  \BinaryCats\Coordinator\Contracts\CanBookResources  $model
     * @param  array  $attributes
     * @return \BinaryCats\Coordinator\Contracts\Booking
     */
    public function makeBookingFor(CanBookResources $model, $attributes = []): Booking
    {
        return $this->bookings()
            ->make($attributes)
            ->model()
            ->associate($model);
    }
}
