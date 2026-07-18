<script setup>
import { Head, useForm } from "@inertiajs/vue3";

const form = useForm({
    email: "",
    password: "",
    remember: false,
});

const submit = () => {
    form.post("/login", {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Admin Login" />

    <div class="dashboard-shell bg-[#f5f3ea] text-slate-900">
        <main class="dashboard-main flex min-w-0 flex-1 flex-col">
            <header class="dashboard-header border-b border-[#e2dccf] bg-[#fbf8f1]/95 px-4 py-3 shadow-sm shadow-black/[0.02] backdrop-blur md:px-6">
                <div class="mx-auto flex max-w-6xl items-center justify-between gap-3">
                    <div class="flex min-w-0 items-center gap-3">
                        <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-[#0f6e56] text-sm font-semibold text-white shadow-sm shadow-emerald-900/15">
                            SC
                        </div>
                        <div class="min-w-0">
                            <h1 class="truncate text-lg font-semibold text-slate-900 md:text-xl">SmartClinic AI</h1>
                            <p class="text-sm text-slate-500">Admin login</p>
                        </div>
                    </div>
                    <div class="rounded-full border border-[#0f6e56]/15 bg-[#0f6e56]/8 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-[#0f6e56]">
                        Secure access
                    </div>
                </div>
            </header>

            <div class="dashboard-content flex-1 overflow-y-auto px-4 py-6 md:px-6 lg:px-8">
                <section class="mx-auto grid min-h-[calc(100vh-7rem)] max-w-6xl place-items-center py-6">
                    <article class="dashboard-panel w-full max-w-2xl rounded-[28px] border border-[#e3dccd] p-6 shadow-[0_16px_40px_rgba(15,23,42,0.06)] sm:p-8 lg:p-10">
                        <div class="space-y-5">
                            <div class="inline-flex items-center gap-2 rounded-full border border-[#0f6e56]/15 bg-[#0f6e56]/8 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-[#0f6e56]">
                                <span class="h-2 w-2 rounded-full bg-[#0f6e56]"></span>
                                SmartClinic AI
                            </div>

                            <div class="space-y-3">
                                <h2 class="text-3xl font-semibold tracking-tight text-slate-950 sm:text-4xl">
                                    Sign in to the dashboard
                                </h2>
                                <p class="max-w-xl text-sm leading-7 text-slate-600 sm:text-base">
                                    Use your clinic admin email and password to access appointments, analytics, and billing.
                                </p>
                            </div>

                            <form class="space-y-4" @submit.prevent="submit">
                                <div class="space-y-2">
                                    <label for="email" class="text-sm font-medium text-slate-700">Email address</label>
                                    <input
                                        id="email"
                                        v-model="form.email"
                                        type="email"
                                        autocomplete="email"
                                        required
                                        class="w-full rounded-2xl border border-[#d6cdb9] bg-white px-4 py-3 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-[#0f6e56] focus:ring-2 focus:ring-[#0f6e56]/15"
                                        placeholder="admin@smartclinic.ai"
                                    />
                                    <p v-if="form.errors.email" class="text-sm text-rose-600">{{ form.errors.email }}</p>
                                </div>

                                <div class="space-y-2">
                                    <label for="password" class="text-sm font-medium text-slate-700">Password</label>
                                    <input
                                        id="password"
                                        v-model="form.password"
                                        type="password"
                                        autocomplete="current-password"
                                        required
                                        class="w-full rounded-2xl border border-[#d6cdb9] bg-white px-4 py-3 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-[#0f6e56] focus:ring-2 focus:ring-[#0f6e56]/15"
                                        placeholder="Enter your password"
                                    />
                                    <p v-if="form.errors.password" class="text-sm text-rose-600">{{ form.errors.password }}</p>
                                </div>

                                <div class="flex items-center justify-between gap-4">
                                    <label class="inline-flex items-center gap-3 text-sm text-slate-600">
                                        <input
                                            v-model="form.remember"
                                            type="checkbox"
                                            class="h-4 w-4 rounded border-[#d6cdb9] bg-white text-[#0f6e56] focus:ring-[#0f6e56]/20"
                                        />
                                        Remember me
                                    </label>

                                    <span class="text-xs uppercase tracking-[0.18em] text-slate-500">Protected access</span>
                                </div>

                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="inline-flex w-full items-center justify-center rounded-2xl bg-[#0f6e56] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[#0c5f49] disabled:cursor-not-allowed disabled:bg-[#0f6e56]/50"
                                >
                                    <svg
                                        v-if="form.processing"
                                        class="mr-2 h-4 w-4 animate-spin"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.8"
                                        aria-hidden="true"
                                    >
                                        <path d="M12 2a10 10 0 1 1-7.07 2.93" stroke-linecap="round" />
                                    </svg>
                                    {{ form.processing ? 'Signing in...' : 'Sign in to dashboard' }}
                                </button>
                            </form>

                            <div class="grid gap-3 pt-2 sm:grid-cols-3">
                                <div class="dashboard-card border border-[#e3dccd] bg-white p-4">
                                    <p class="text-xs uppercase tracking-[0.18em] text-slate-500">Clean layout</p>
                                    <p class="mt-2 text-sm font-medium text-slate-950">Less noise, faster scan</p>
                                </div>
                                <div class="dashboard-card border border-[#e3dccd] bg-white p-4">
                                    <p class="text-xs uppercase tracking-[0.18em] text-slate-500">Shared system</p>
                                    <p class="mt-2 text-sm font-medium text-slate-950">Same visual language</p>
                                </div>
                                <div class="dashboard-card border border-[#e3dccd] bg-white p-4">
                                    <p class="text-xs uppercase tracking-[0.18em] text-slate-500">Fast access</p>
                                    <p class="mt-2 text-sm font-medium text-slate-950">Immediate sign-in flow</p>
                                </div>
                            </div>
                        </div>
                    </article>
                </section>
            </div>
        </main>
    </div>
</template>
