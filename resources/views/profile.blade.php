<x-layout>
    <div class="container mx-auto mt-10 px-6 md:px-0">
        <div class="bg-white shadow-md rounded-lg p-8 max-w-2xl mx-auto">
            <h1 class="text-2xl font-bold mb-6 text-center">Your Profile</h1>

            <div class="mb-4">
                <p class="text-sm font-semibold text-gray-600">Name</p>
                <p class="text-lg text-gray-800">{{ $user->name }}</p>
            </div>

            <hr class="my-4">

            <div class="mb-4">
                <p class="text-sm font-semibold text-gray-600">Email</p>
                <p class="text-lg text-gray-800">{{ $user->email }}</p>
            </div>

            <hr class="my-4">

            <form method="POST" action="{{ route('profile.languages.update') }}">
                @csrf
                @method('PATCH')

                <div>
                    <p class="text-sm font-semibold text-gray-600">Languages</p>
                    <div class="mt-2 space-y-2 flex flex-wrap gap-8">
                        @php
                            $availableLanguages = array_keys(config('languages'));
                        @endphp
                        @foreach($availableLanguages as $language)
                            <div class="flex items-center">
                                <input type="checkbox" name="languages[]" value="{{ $language }}" id="{{ $language }}"
                                    @checked(in_array($language, $user->languages ?? []))
                                    class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                <label for="{{ $language }}"
                                    class="ml-2 block text-lg text-gray-800 capitalize">{{ $language }}</label>
                            </div>
                        @endforeach
                    </div>
                    @error('languages.*')
                        <p class="text-red-500 text-xs mt-2">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mt-6 flex justify-between items-center">
                    <button type="submit" class="btn">Update Languages</button>
                    <a href="{{ route('welcome') }}" class="hover:text-red-500">Back</a>
                </div>
            </form>
        </div>
    </div>
</x-layout>