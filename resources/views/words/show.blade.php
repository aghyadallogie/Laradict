<x-layout>
    @if($words->isEmpty())
        <div class="mt-6 text-center text-gray-500 font-semibold">
            You have not translated anything yet!
        </div>
    @endif
    @if($words->isNotEmpty())
        <h1>Words</h1>
    @endif
    <ul class="list-none p-0 max-w-md mx-auto mt-8 bg-gray-50 rounded-lg shadow">
        @foreach ($words as $word)
            <div class="flex flex-col mb-4">
                <form action="{{ route('words.destroy', $word->id) }}" method="POST" class="mb-2 self-end">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                        class="px-3 py-1 bg-red-500 text-white rounded hover:bg-red-600 text-sm">Delete</button>
                </form>
                @foreach($word->translations as $lang => $translation)
                    <li class="flex justify-between items-center px-4 py-3 mb-1 bg-white text-lg shadow-sm">
                        <span class="font-semibold text-gray-700">{{ $lang }}</span>
                        <span class="text-gray-900">{{ $translation }}</span>
                    </li>
                @endforeach
            </div>
        @endforeach
    </ul>
    <div class="max-w-md mx-auto mb-8">
        {{ $words->links() }}
    </div>
</x-layout>