<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Booking;
use App\Models\Movie;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MovieController extends Controller
{
    public function destroy($id)
    {
        $movie = Movie::findOrFail($id);
        $movie->delete();

        return redirect()->route('admin.dashboard')->with('success', 'Movie deleted successfully!');
    }

    public function showBookingPage($id)
    {
        $movie = Movie::findOrFail($id);
        $reservedSeats = Booking::where('movie_id', $id)
            ->whereIn('status', ['reserved', 'confirmed'])
            ->get()
            ->flatMap(function ($booking) {
                return is_array($booking->seatArrangement) ? $booking->seatArrangement : json_decode($booking->seatArrangement, true);
            })
            ->toArray();

        return view('movies.book', compact('movie', 'reservedSeats'));
    }

    public function reserveSeat(Request $request)
    {
        $validated = $request->validate([
            'movie_id' => 'required|exists:movies,id',
            'seatArrangement' => 'required|array|min:1',
            'quantity' => 'required|integer|min:1',
        ]);

        $movie = Movie::findOrFail($validated['movie_id']);

        if ($validated['quantity'] > $movie->seats_available) {
            return redirect()->back()->withErrors(['quantity' => 'You cannot reserve more seats than available.']);
        }

        $reservedSeats = Booking::where('movie_id', $movie->id)
            ->whereIn('status', ['reserved', 'confirmed'])
            ->get()
            ->flatMap(function ($booking) {
                return is_array($booking->seatArrangement) ? $booking->seatArrangement : json_decode($booking->seatArrangement, true);
            })
            ->toArray();

        $selectedSeats = $validated['seatArrangement'];
        foreach ($selectedSeats as $seat) {
            if (in_array($seat, $reservedSeats)) {
                return redirect()->back()->withErrors(['seatArrangement' => 'One or more selected seats are already reserved.']);
            }
        }

        $totalAmount = $validated['quantity'] * $movie->amount;

        session([
            'booking' => [
                'user_id' => auth()->id(),
                'movie_id' => $movie->id,
                'movie_title' => $movie->title,
                'poster' => $movie->poster,
                'seatArrangement' => $selectedSeats,
                'quantity' => $validated['quantity'],
                'total_amount' => $totalAmount,
                'amount' => $movie->amount,
            ]
        ]);

        return redirect()->route('movies.proceed');
    }

    public function proceed()
    {
        $booking = session('booking');

        if (!$booking) {
            return redirect()->route('dashboard')->with('error', 'No booking data found.');
        }

        return view('movies.proceed', compact('booking'));
    }

    public function confirmBooking(Request $request)
    {
        $booking = session('booking');

        if (!$booking) {
            return redirect()->route('dashboard')->with('error', 'No booking data found.');
        }

        $request->validate([
            'payment_method' => 'required|string|in:credit_card,debit_card,paypal',
        ]);

        DB::beginTransaction();

        try {
            $createdBooking = Booking::create([
                'user_id' => auth()->id(),
                'movie_id' => $booking['movie_id'],
                'movie_title' => $booking['movie_title'],
                'poster' => $booking['poster'],
                'amount' => $booking['amount'],
                'seatArrangement' => json_encode($booking['seatArrangement']),
                'seats_booked' => $booking['quantity'],
                'total_amount' => $booking['total_amount'],
                'payment_method' => $request->payment_method,
                'status' => 'confirmed',
            ]);

            session()->forget('booking');

            DB::commit();

            return redirect()->route('movies.print.ticket', ['booking_id' => $createdBooking->id])->with('success', 'Booking confirmed!');
        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Error storing booking: ' . $e->getMessage());

            return redirect()->route('dashboard')->with('error', 'Failed to store booking.');
        }
    }

    public function printTicket($booking_id)
    {
        $booking = Booking::findOrFail($booking_id);

        return view('movies.print-ticket', compact('booking'));
    }

    public function index()
    {
        $movies = Movie::all();

        return view('admin.dashboard', compact('movies'));
    }
}
