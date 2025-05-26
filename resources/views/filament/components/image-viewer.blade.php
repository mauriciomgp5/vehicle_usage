<div class="space-y-4">
    <div class="text-center">
        <img src="{{ $imageUrl }}" alt="{{ $filename }}" class="max-w-full h-auto mx-auto rounded-lg shadow-lg">
    </div>
    
    @if($caption)
        <div class="bg-gray-50 p-3 rounded-lg">
            <p class="text-sm text-gray-700">
                <strong>Legenda:</strong> {{ $caption }}
            </p>
        </div>
    @endif
    
    <div class="bg-gray-50 p-3 rounded-lg">
        <p class="text-sm text-gray-700">
            <strong>Nome do arquivo:</strong> {{ $filename }}
        </p>
    </div>
</div> 