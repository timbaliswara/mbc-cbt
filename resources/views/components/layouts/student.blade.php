<x-layouts.app :title="$title ?? 'Portal Siswa CBT'">
    <div class="premium-shell min-h-screen">
        <header class="border-b border-white/60 bg-white/75 backdrop-blur-xl">
            <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
                <a href="{{ route('student.token') }}" class="flex items-center gap-3">
                    <div class="flex h-11 w-11 items-center justify-center rounded-md bg-zinc-950 text-sm font-bold text-white shadow-lg shadow-emerald-900/10 ring-1 ring-emerald-400/30">CBT</div>
                    <div>
                        <p class="text-sm font-semibold text-zinc-950">MBC</p>
                        <p class="text-xs text-emerald-700">Portal Tes Siswa</p>
                    </div>
                </a>
                <a href="{{ route('login') }}" class="rounded-md border border-zinc-200 bg-white/90 px-3 py-2 text-sm font-medium text-zinc-700 shadow-sm transition hover:bg-white">Admin</a>
            </div>
        </header>
        <main>
            {{ $slot }}
        </main>
    </div>
</x-layouts.app>
