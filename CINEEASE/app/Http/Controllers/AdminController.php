<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Movie;
use App\Models\Booking;

class AdminController extends Controller
{
    public function index()
    {
        // Fetch all movies
        $movies = Movie::all();
        return view('admindash', compact('movies'));
    }

    public function manageUsers()
    {
        // Fetch all reserved and confirmed seats
        $bookings = Booking::whereIn('status', ['reserved', 'confirmed'])->get(['seatArrangement']);
        $reservedSeats = $bookings->flatMap(function ($booking) {
            return is_array($booking->seatArrangement) ? $booking->seatArrangement : json_decode($booking->seatArrangement, true);
        })->toArray();

        // Fetch all users with their bookings
        $users = User::with('bookings.movie')->get();

        return view('admin.manage-users', compact('reservedSeats', 'users'));
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return redirect()->route('admin.manage-users')->with('success', 'User deleted successfully.');
    }

    public function updateBooking(Request $request)
    {
        // Validate and update booking logic here
        $validated = $request->validate([
            'seatArrangement' => 'required|array',
            'booking_id' => 'required|integer|exists:bookings,id',
        ]);

        $booking = Booking::findOrFail($validated['booking_id']);
        $booking->seatArrangement = $validated['seatArrangement'];
        $booking->save();

        return redirect()->route('admin.manage-users')->with('success', 'Booking updated successfully.');
    }
}
