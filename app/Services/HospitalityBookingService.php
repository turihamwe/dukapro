<?php

namespace App\Services;

use App\Enums\CatalogItemType;
use App\Enums\HospitalityBookingStatus;
use App\Models\Business;
use App\Models\HospitalityRoomBooking;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class HospitalityBookingService
{
    public function rentableRooms(Business $business): Collection
    {
        return Product::query()
            ->where('business_id', $business->id)
            ->where('catalog_item_type', CatalogItemType::RENTABLE)
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, HospitalityRoomBooking>
     */
    public function ledger(Business $business, ?Carbon $from = null, ?Carbon $to = null): Collection
    {
        $query = HospitalityRoomBooking::query()
            ->where('business_id', $business->id)
            ->with(['product', 'branch'])
            ->orderByDesc('check_in')
            ->orderByDesc('id');

        if ($from) {
            $query->whereDate('check_out', '>', $from->toDateString());
        }

        if ($to) {
            $query->whereDate('check_in', '<', $to->toDateString());
        }

        return $query->limit(200)->get();
    }

    public function create(Business $business, array $data): HospitalityRoomBooking
    {
        return DB::transaction(function () use ($business, $data) {
            $product = Product::query()
                ->where('business_id', $business->id)
                ->whereKey($data['product_id'])
                ->lockForUpdate()
                ->firstOrFail();

            if ($product->catalog_item_type !== CatalogItemType::RENTABLE) {
                throw ValidationException::withMessages([
                    'product_id' => 'Only rentable room assets can be booked.',
                ]);
            }

            $checkIn = Carbon::parse($data['check_in'])->startOfDay();
            $checkOut = Carbon::parse($data['check_out'])->startOfDay();

            if ($checkOut->lte($checkIn)) {
                throw ValidationException::withMessages([
                    'check_out' => 'Check-out must be after check-in.',
                ]);
            }

            if ($this->hasOverlap($business->id, (int) $product->id, $checkIn, $checkOut)) {
                throw ValidationException::withMessages([
                    'product_id' => 'This room is already booked for part of that stay.',
                ]);
            }

            return HospitalityRoomBooking::query()->create([
                'business_id' => $business->id,
                'product_id' => $product->id,
                'branch_id' => $data['branch_id'] ?? null,
                'guest_name' => $data['guest_name'],
                'guest_phone' => $data['guest_phone'] ?? null,
                'check_in' => $checkIn->toDateString(),
                'check_out' => $checkOut->toDateString(),
                'status' => HospitalityBookingStatus::RESERVED,
                'notes' => $data['notes'] ?? null,
            ]);
        });
    }

    public function checkIn(HospitalityRoomBooking $booking): HospitalityRoomBooking
    {
        if ($booking->status !== HospitalityBookingStatus::RESERVED) {
            throw ValidationException::withMessages([
                'status' => 'Only reserved bookings can be checked in.',
            ]);
        }

        $booking->status = HospitalityBookingStatus::CHECKED_IN;
        $booking->checked_in_at = now();
        $booking->save();

        return $booking;
    }

    public function checkOut(HospitalityRoomBooking $booking): HospitalityRoomBooking
    {
        if ($booking->status !== HospitalityBookingStatus::CHECKED_IN) {
            throw ValidationException::withMessages([
                'status' => 'Only in-house guests can be checked out.',
            ]);
        }

        $booking->status = HospitalityBookingStatus::CHECKED_OUT;
        $booking->checked_out_at = now();
        $booking->save();

        return $booking;
    }

    public function cancel(HospitalityRoomBooking $booking): HospitalityRoomBooking
    {
        if ($booking->status === HospitalityBookingStatus::CHECKED_OUT) {
            throw ValidationException::withMessages([
                'status' => 'Completed stays cannot be cancelled.',
            ]);
        }

        $booking->status = HospitalityBookingStatus::CANCELLED;
        $booking->save();

        return $booking;
    }

    public function hasOverlap(
        int $businessId,
        int $productId,
        Carbon $checkIn,
        Carbon $checkOut,
        ?int $excludeBookingId = null
    ): bool {
        $query = HospitalityRoomBooking::query()
            ->where('business_id', $businessId)
            ->where('product_id', $productId)
            ->whereIn('status', HospitalityBookingStatus::blocking())
            ->whereDate('check_in', '<', $checkOut->toDateString())
            ->whereDate('check_out', '>', $checkIn->toDateString());

        if ($excludeBookingId) {
            $query->whereKeyNot($excludeBookingId);
        }

        return $query->exists();
    }
}
