<x-app-layout title="Login">
    <div class="grid min-h-screen place-items-center bg-[#eef2ef] px-4 py-10">
        <div class="w-full max-w-md rounded-md border border-[#d8ded8] bg-white p-6 shadow-sm">
            <div class="mb-6">
                <h1 class="text-2xl font-semibold text-[#17211c]">Daraz Manager</h1>
                <p class="mt-1 text-sm text-[#617068]">Sign in to manage requisitions, payments, stock, and reports.</p>
            </div>
            @include('partials.flash')
            <form method="post" action="{{ route('login.store') }}" class="space-y-4">
                @csrf
                <label class="block text-sm font-medium">Email
                    <input name="email" type="email" value="{{ old('email') }}" required class="mt-1 w-full rounded-md border border-[#cbd5ce] px-3 py-2 outline-none focus:border-[#287857]">
                </label>
                <label class="block text-sm font-medium">Password
                    <input name="password" type="password" required class="mt-1 w-full rounded-md border border-[#cbd5ce] px-3 py-2 outline-none focus:border-[#287857]">
                </label>
                <label class="flex items-center gap-2 text-sm text-[#617068]">
                    <input type="checkbox" name="remember" class="rounded border-[#cbd5ce]"> Remember me
                </label>
                <button class="w-full rounded-md bg-[#287857] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#1f6046]">Login</button>
            </form>
        </div>
    </div>
</x-app-layout>
