<div>
    
    <div class="max-w-full w-full mx-auto p-4">
        <h2 class="text-xl font-bold mb-4">Dashboard</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach ($rooms as $room)
                <div class="border rounded-lg p-4 shadow-sm bg-white">
                    <h3 class="text-lg font-semibold">{{ $room->name }}</h3>
                    <p><strong>Capacity:</strong> {{ $room->capacity }}</p>
                    <p><strong>Location: </strong> {{ $room->location }}</p>
                    <p><strong>Available: </strong> {{ $room->available ? 'Yes' : 'No' }}</p>
                </div>
            @endforeach
        </div>
    </div>
</div>
