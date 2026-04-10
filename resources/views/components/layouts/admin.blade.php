@php
    $links = [
        ['label' => 'Dashboard', 'url' => route('admin.dashboard'), 'active' => request()->routeIs('admin.dashboard')],
        ['label' => 'Paket Ujian', 'url' => route('admin.exams'), 'active' => request()->routeIs('admin.exams')],
        ['label' => 'Soal', 'url' => route('admin.questions'), 'active' => request()->routeIs('admin.questions')],
        ['label' => 'Token', 'url' => route('admin.tokens'), 'active' => request()->routeIs('admin.tokens')],
        ['label' => 'Hasil', 'url' => route('admin.results'), 'active' => request()->routeIs('admin.results')],
        ['label' => 'Panduan', 'url' => route('admin.guide'), 'active' => request()->routeIs('admin.guide')],
    ];
@endphp

<x-layouts.app :title="$title ?? 'Admin CBT'">
    <div class="premium-shell min-h-screen">
        <aside class="fixed inset-y-0 left-0 z-30 hidden w-72 border-r border-emerald-900/20 bg-gradient-to-b from-emerald-800 via-emerald-700 to-teal-800 text-white shadow-2xl shadow-emerald-950/20 lg:block">
            <div class="flex h-full flex-col">
                <div class="border-b border-white/10 px-6 py-6">
                    <div class="flex items-center gap-3">
                        <div class="flex h-11 w-11 items-center justify-center rounded-md bg-white text-sm font-bold text-emerald-800 shadow-lg shadow-emerald-950/20 ring-1 ring-white/50">CBT</div>
                        <div>
                            <p class="text-sm font-semibold text-white">MBC</p>
                            <p class="text-xs text-emerald-50/75">Ruang kerja TIM MBC</p>
                        </div>
                    </div>
                </div>
                <nav class="flex-1 space-y-1 px-4 py-5">
                    @foreach ($links as $link)
                        <a href="{{ $link['url'] }}" @class([
                            'group flex items-center rounded-md px-3 py-2.5 text-sm font-medium transition',
                            'bg-white text-emerald-900 shadow-sm shadow-emerald-950/10' => $link['active'],
                            'text-emerald-50/82 hover:bg-white/12 hover:text-white' => ! $link['active'],
                        ])>{{ $link['label'] }}</a>
                    @endforeach
                </nav>
                <div class="mx-4 mb-4 rounded-md border border-white/15 bg-white/10 p-4 backdrop-blur">
                    <p class="text-xs font-medium uppercase tracking-[0.16em] text-emerald-50/80">Dikelola TIM MBC</p>
                    <p class="mt-2 text-sm leading-6 text-emerald-50/75">Token manual, soal bergambar, stimulus, esai, dan rekap nilai disiapkan dalam satu alur.</p>
                </div>
                <div class="border-t border-white/10 p-4">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="w-full rounded-md border border-white/15 bg-white/10 px-3 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-white/15">Keluar</button>
                    </form>
                </div>
            </div>
        </aside>

        <div class="lg:pl-72">
            <header class="sticky top-0 z-20 border-b border-white/60 bg-white/75 backdrop-blur-xl">
                <div class="flex min-h-20 items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-[0.16em] text-emerald-700">Aplikasi TIM MBC</p>
                        <h1 class="mt-1 text-2xl font-semibold tracking-tight text-zinc-950">{{ $heading ?? 'Dashboard' }}</h1>
                    </div>
                    <a href="{{ route('student.token') }}" class="premium-button rounded-md px-4 py-2.5 text-sm font-semibold text-white transition hover:brightness-105">Portal Siswa</a>
                </div>
                <nav class="flex gap-2 overflow-x-auto border-t border-zinc-100 px-4 py-2 lg:hidden">
                    @foreach ($links as $link)
                        <a href="{{ $link['url'] }}" @class([
                            'whitespace-nowrap rounded-md px-3 py-2 text-sm font-medium',
                            'bg-emerald-50 text-emerald-800' => $link['active'],
                            'text-zinc-600' => ! $link['active'],
                        ])>{{ $link['label'] }}</a>
                    @endforeach
                </nav>
            </header>

            <main class="px-4 py-8 sm:px-6 lg:px-8">
                {{ $slot }}
            </main>
        </div>
    </div>
</x-layouts.app>
