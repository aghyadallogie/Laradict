<x-layout>
    <form action="{{ route('login') }}" method="POST">
        @csrf

        <h2 class="mb-8">Log In to Your Account</h2>

        <label for="email">Email:</label>
        <input
            type="email"
            name="email"
            value="{{ old('email') }}"
            required>

        <label for="password">Password:</label>
        <input
            type="password"
            name="password"
            required>

        <button type="submit" class="btn mt-4">Log in</button>

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