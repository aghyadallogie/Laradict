<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Laradict</title>

  @vite('resources/css/app.css')
</head>

<body>
  @if (session('success'))
    <div id="flash" class="p-4 absolute top-0 left-1/2 z-10 text-center bg-green-50 opacity-90 text-green-500 border-2 border-green-600 font-bold">
    {{ session('success') }}
    </div>
  @endif
  <header>
    <nav>
      <h1>
        <a href="/">Laradict</a>
      </h1>
      @guest
      <span class="border-r-2 pr-2">Welcome Guest!</span>
      <a href="{{ route('show.register') }}" class="hover:text-red-500">Register</a>
      <a href="{{ route('show.login') }}" class="hover:text-red-500">Login</a>
    @endguest
      @auth
      <a href="{{ route('words.show') }}" class="btn">Your Words</a>
      <a href="{{ route('profile') }}" class="btn">Profile</a>
      <span class="border-r-2 pr-2">Hi there {{ Auth::user()->name }}!</span>
      <form action="{{ route('logout') }}" method="POST" class="m-0">
      @csrf
      <button type="submit" class="hover:text-red-500">Logout</button>
      </form>
    @endauth
    </nav>
  </header>
  <main class="container">
    {{ $slot }}
  </main>
</body>

</html>