<!-- resources/views/movies/book.blade.php -->

<x-app-layout>
    <x-slot name="header">
        <a href="{{ route('dashboard') }}" class="nav-link">Dashboard</a>
        <a href="{{ route('movies.book', ['id' => $movie->id]) }}" class="nav-link">Booking Page</a>

        <link rel="stylesheet" href="{{ asset('css/userdash.css') }}">
        <link rel="stylesheet" href="{{ asset('css/book.css') }}">
    </x-slot>

    <div class="main-content">
        <div class="movies-container2">
            <h2 class="section-title">Book Your Ticket Here</h2>
            <div class="movie-item2">
                <img src="{{ asset('storage/' . $movie->poster) }}" alt="Movie Poster" class="poster">
                <div class="details2">
                    <h3 class="title">{{ $movie->title }}</h3>
                    <p class="amount">Price: {{ $movie->amount }} pesos</p>
                </div>
            </div>

            <div class="table-container-wrapper">
                <form action="{{ route('movies.reserve') }}" method="POST" id="bookingForm">
                    @csrf
                    <input type="hidden" name="movie_id" value="{{ $movie->id }}">

                    <div class="table-container">
                        <table>
                            <tr>
                                <td>Theater</td>
                                <td>Performance Art Theater</td>
                            </tr>
                            <tr>
                                <td>No. of Seats</td>
                                <td>
                                    <div class="input-wrapper">
                                        <input type="number" id="quantity" name="quantity" min="1" max="{{ $movie->seats_available }}" required>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>Seat Arrangement</td>
                                <td>
                                    <div id="seatSelections">
                                        <!-- Seat selection fields will be added here dynamically -->
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>Amount</td>
                                <td>
                                    <span id="totalAmount">0.00</span> pesos
                                </td>
                            </tr>
                        </table>
                    </div>

                    <div class="button-container">
                        <button type="submit" class="proceed-button">Proceed</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    var movieCostValue = {{ $movie->amount }};
    var reservedSeats = @json($reservedSeats ?? []);
    var confirmedSeats = @json($confirmedSeats ?? []);
    var seatsAvailable = {{ $movie->seats_available }};

    document.getElementById('quantity').addEventListener('input', function() {
        var quantity = parseInt(this.value);
        var seatSelections = document.getElementById('seatSelections');
        var totalAmount = quantity * movieCostValue;

        if (quantity > seatsAvailable) {
            alert('You cannot reserve more seats than available.');
            this.value = seatsAvailable;
            quantity = seatsAvailable;
        }

        seatSelections.innerHTML = ''; // Clear existing seat selection fields

        var availableSeats = [];

        // Generate the available seats list
        @foreach (range('A', 'L') as $row)
            @foreach (range(1, 8) as $seat)
                var seatValue = '{{ $row . $seat }}';
                if (!reservedSeats.includes(seatValue) && !confirmedSeats.includes(seatValue)) {
                    availableSeats.push(seatValue);
                }
            @endforeach
        @endforeach

        if (availableSeats.length < quantity) {
            alert('Not enough available seats for the selected quantity.');
            return;
        }

        // Automatically assign default seats
        for (var i = 0; i < quantity; i++) {
            var selectWrapper = document.createElement('div');
            selectWrapper.className = 'select-wrapper';

            var select = document.createElement('select');
            select.name = 'seatArrangement[]';

            availableSeats.forEach(function(seatValue) {
                var option = document.createElement('option');
                option.value = seatValue;
                option.text = seatValue;
                select.appendChild(option);
            });

            // Automatically select the first available seat
            select.value = availableSeats[0];
            availableSeats.splice(0, 1); // Remove the selected seat from available seats

            selectWrapper.appendChild(select);
            seatSelections.appendChild(selectWrapper);
        }

        document.getElementById('totalAmount').textContent = totalAmount.toFixed(2);
    });

    document.getElementById('bookingForm').addEventListener('submit', function(event) {
        var quantity = parseInt(document.getElementById('quantity').value);
        var seatSelections = document.querySelectorAll('#seatSelections select');

        if (seatSelections.length !== quantity) {
            event.preventDefault();
            alert('Please select the appropriate number of seats for the quantity specified.');
        }
    });
</script>


</x-app-layout>
