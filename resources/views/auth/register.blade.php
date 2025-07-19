<x-layout>
    <form action="{{ route('register') }}" method="POST">
        @csrf

        <h2 class="mb-8">Register for an Account</h2>

        <label for="name">Name:</label>
        <input type="name" name="name" value="{{ old('name') }}" required>

        <label for="email">Email:</label>
        <input type="email" name="email" value="{{ old('email') }}" required>

        <label for="password">Password:</label>
        <input type="password" name="password" required>

        <label for="password_confirmation">Confirm Password:</label>
        <input type="password" name="password_confirmation" required>

        <div class="mt-4">
            <label>Languages you know:</label>
            <div class="mt-2 grid grid-cols-2 gap-x-4 gap-y-2">
                @php
                    $availableLanguages = array_keys(config('languages'));
                @endphp

                @foreach($availableLanguages as $lang)
                    <div>
                        <input class="w-auto inline" type="checkbox" name="languages[]" value="{{ $lang }}"
                            id="lang_{{ $lang }}" {{ in_array($lang, old('languages', [])) ? 'checked' : '' }}>
                        <label for="lang_{{ $lang }}">{{ $lang }}</label>
                    </div>
                @endforeach
            </div>
        </div>

        <button type="submit" class="btn mt-4">Register</button>
        <!-- validation errors -->
        @if ($errors->any())
            <div class="mt-3 text-center text-red-500 p-3 bg-red-100">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li class="text-red-500">{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </form>
</x-layout>