<?php

use Livewire\Component;
use Illuminate\Support\Facades\Auth;

new class extends Component
{
    public string $email = '';
    public string $password = '';

    public function login()
    {
        $credentials = $this->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt($credentials)) {
            $this->addError('email', 'Email atau password tidak sesuai.');
            return;
        }

        request()->session()->regenerate();

        return redirect()->intended(route('admin.dashboard'));
    }
};
?>

<div class="premium-shell grid min-h-screen lg:grid-cols-[1.08fr_0.92fr]">
    <section class="hero-panel hidden p-10 lg:flex lg:flex-col lg:justify-between">
        <div class="flex items-center gap-3">
            <div class="flex h-11 w-11 items-center justify-center rounded-md bg-white text-sm font-bold text-emerald-800 shadow-xl shadow-emerald-950/20">CBT</div>
            <div>
                <p class="text-sm font-semibold text-white">MBC</p>
                <p class="text-xs text-emerald-50/70">Dibuat oleh TIM MBC</p>
            </div>
        </div>
        <div class="max-w-xl">
            <p class="mb-4 text-sm font-medium uppercase tracking-[0.16em] text-emerald-50/80">Ruang Kerja TIM MBC</p>
            <h1 class="text-5xl font-semibold tracking-tight text-white">Tes online yang TIM MBC siapkan untuk berjalan lebih tenang.</h1>
            <p class="mt-5 max-w-lg text-base leading-7 text-emerald-50/78">Atur paket ujian, soal bergambar, token peserta, koreksi esai, sampai rekap nilai dari satu tempat yang familiar untuk tim.</p>
        </div>
        <div class="grid grid-cols-3 gap-3 text-sm">
            <div class="rounded-md border border-white/15 bg-white/10 p-4 backdrop-blur">
                <p class="font-semibold text-white">Token</p>
                <p class="mt-1 text-emerald-50/70">Sekali pakai</p>
            </div>
            <div class="rounded-md border border-white/15 bg-white/10 p-4 backdrop-blur">
                <p class="font-semibold text-white">Media</p>
                <p class="mt-1 text-emerald-50/70">Gambar soal</p>
            </div>
            <div class="rounded-md border border-white/15 bg-white/10 p-4 backdrop-blur">
                <p class="font-semibold text-white">Nilai</p>
                <p class="mt-1 text-emerald-50/70">Siap dibaca tim</p>
            </div>
        </div>
    </section>

    <section class="flex items-center justify-center px-4 py-10 sm:px-6">
        <div class="surface w-full max-w-md rounded-md p-7 backdrop-blur">
            <div>
                <p class="text-sm font-medium uppercase tracking-[0.16em] text-emerald-700">Login Admin</p>
                <h2 class="mt-2 text-3xl font-semibold tracking-tight text-zinc-950">Masuk ke ruang admin</h2>
                <p class="mt-2 text-sm text-zinc-500">Masuk sebagai admin TIM MBC untuk menyiapkan ujian dan memantau hasil peserta.</p>
            </div>

            <form wire:submit="login" class="mt-6 space-y-4">
                <div>
                    <label class="text-sm font-medium text-zinc-800">Email</label>
                    <input wire:model="email" type="email" class="premium-input mt-2 w-full rounded-md px-3 py-2.5 text-sm transition">
                    @error('email') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-sm font-medium text-zinc-800">Password</label>
                    <input wire:model="password" type="password" class="premium-input mt-2 w-full rounded-md px-3 py-2.5 text-sm transition">
                    @error('password') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <button class="premium-button w-full rounded-md px-4 py-2.5 text-sm font-semibold text-white transition hover:brightness-105">Masuk</button>
            </form>

            <div class="mt-5 rounded-md border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-900">
                Akun awal TIM MBC: admin@mbc.test / password
            </div>
        </div>
    </section>
</div>
