<x-layout>
  <div class="mt-8 flex flex-col ">
    <form action="{{ route('welcome') }}" method="POST" class="flex space-y-4 min-w-full">
      @csrf
      <label for="text-to-translate" class="text-lg font-semibold"></label>
      <input type="text" class="outline-none" id="text-to-translate" name="text_to_translate"
        placeholder="Type a word or a sentence to translate.." required>
      <button type="submit" class="btn">Translate</button>
    </form>
    @if(isset($latestWord))
      @foreach($latestWord->translations as $lang => $translation)
      <li class="flex justify-between items-center px-4 py-3 mb-1 bg-white text-lg shadow-sm">
        <span class="font-semibold text-gray-700">{{ $lang }}</span>
        <span class="text-gray-900">{{ $translation }}</span>
      </li>
      @endforeach
    @else
      <div class="mt-6 text-center text-gray-500 font-semibold">
        You have not translated anything yet!
      </div>
    @endif
  </div>
</x-layout>