<script setup>
import { Head, useForm } from "@inertiajs/vue3";

const form = useForm({
    email: "",
    password: "",
    remember: false,
});

const submit = () => {
    // نستخدم المسار الصريح /login وتأكد أن السيرفر يرى طلب POST صحيح
    form.post("/login", {
        preserveScroll: true,
        onSuccess: () => {
            console.log("نجح تسجيل الدخول!");
        },
        onError: (errors) => {
            console.log("فشل تسجيل الدخول:", errors);
        }
    });
};
</script>

<template>
    <Head title="Admin Login" />

    <div
        class="relative min-h-screen overflow-hidden bg-slate-950 text-slate-100"
    >
        <div
            aria-hidden="true"
            class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(79,70,229,0.22),_transparent_28%),radial-gradient(circle_at_top_right,_rgba(16,185,129,0.16),_transparent_24%),linear-gradient(180deg,_rgba(2,6,23,1)_0%,_rgba(15,23,42,1)_45%,_rgba(2,6,23,1)_100%)]"
        ></div>

        <div
            class="relative mx-auto flex min-h-screen max-w-7xl items-center px-4 py-10 sm:px-6 lg:px-8"
        >
            <div
                class="grid w-full gap-8 lg:grid-cols-[1.05fr_0.95fr] lg:items-center"
            >
                <section class="space-y-8">
                    <div
                        class="inline-flex items-center gap-2 rounded-full border border-indigo-400/30 bg-indigo-500/10 px-4 py-2 text-xs font-semibold uppercase tracking-[0.22em] text-indigo-200"
                    >
                        SmartClinic AI
                    </div>

                    <div class="space-y-4">
                        <h1
                            class="max-w-2xl text-4xl font-semibold tracking-tight text-white sm:text-5xl"
                        >
                            Admin Panel Access
                        </h1>
                        <p
                            class="max-w-2xl text-sm leading-7 text-slate-300 sm:text-base"
                        >
                            Sign in to review operational KPIs, manage clinic
                            workflows, and monitor AI subsystem telemetry from
                            one unified Inertia workspace.
                        </p>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-3">
                        <div
                            class="rounded-2xl border border-slate-800 bg-slate-900/70 p-4 ring-1 ring-white/5"
                        >
                            <p
                                class="text-xs uppercase tracking-[0.18em] text-slate-500"
                            >
                                Operational mode
                            </p>
                            <p class="mt-2 text-sm font-medium text-slate-100">
                                Clinical admin
                            </p>
                        </div>
                        <div
                            class="rounded-2xl border border-slate-800 bg-slate-900/70 p-4 ring-1 ring-white/5"
                        >
                            <p
                                class="text-xs uppercase tracking-[0.18em] text-slate-500"
                            >
                                Platform
                            </p>
                            <p class="mt-2 text-sm font-medium text-slate-100">
                                Inertia + Vue 3
                            </p>
                        </div>
                        <div
                            class="rounded-2xl border border-slate-800 bg-slate-900/70 p-4 ring-1 ring-white/5"
                        >
                            <p
                                class="text-xs uppercase tracking-[0.18em] text-slate-500"
                            >
                                Workspace date
                            </p>
                            <p class="mt-2 text-sm font-medium text-slate-100">
                                June 27, 2026
                            </p>
                        </div>
                    </div>

                    <div
                        class="rounded-3xl border border-amber-400/20 bg-amber-500/10 p-4 text-sm leading-6 text-amber-100 ring-1 ring-amber-400/10"
                    >
                        Patients should use the mobile app for self-service
                        access and appointment actions.
                    </div>
                </section>

                <section
                    class="relative overflow-hidden rounded-3xl border border-slate-800 bg-slate-900/80 shadow-2xl shadow-slate-950/40 backdrop-blur-xl"
                >
                    <div
                        class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-indigo-400 via-emerald-400 to-amber-400"
                    ></div>

                    <div class="p-6 sm:p-8">
                        <div class="mb-8 space-y-2">
                            <p
                                class="text-sm font-medium uppercase tracking-[0.2em] text-slate-400"
                            >
                                Secure sign-in
                            </p>
                            <h2 class="text-2xl font-semibold text-white">
                                Administrator login
                            </h2>
                            <p class="text-sm leading-6 text-slate-400">
                                Enter your clinic admin credentials to continue
                                into the dashboard.
                            </p>
                        </div>

                        <form class="space-y-5" @submit.prevent="submit">
                            <div class="space-y-2">
                                <label
                                    for="email"
                                    class="text-sm font-medium text-slate-200"
                                    >Email address</label
                                >
                                <input
                                    id="email"
                                    v-model="form.email"
                                    type="email"
                                    autocomplete="email"
                                    required
                                    class="w-full rounded-2xl border border-slate-700 bg-slate-950/70 px-4 py-3 text-sm text-slate-100 outline-none transition placeholder:text-slate-500 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-500/20"
                                    placeholder="admin@smartclinic.ai"
                                />
                                <p
                                    v-if="form.errors.email"
                                    class="text-sm text-rose-300"
                                >
                                    {{ form.errors.email }}
                                </p>
                            </div>

                            <div class="space-y-2">
                                <label
                                    for="password"
                                    class="text-sm font-medium text-slate-200"
                                    >Password</label
                                >
                                <input
                                    id="password"
                                    v-model="form.password"
                                    type="password"
                                    autocomplete="current-password"
                                    required
                                    class="w-full rounded-2xl border border-slate-700 bg-slate-950/70 px-4 py-3 text-sm text-slate-100 outline-none transition placeholder:text-slate-500 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-500/20"
                                    placeholder="Enter your password"
                                />
                                <p
                                    v-if="form.errors.password"
                                    class="text-sm text-rose-300"
                                >
                                    {{ form.errors.password }}
                                </p>
                            </div>

                            <div
                                class="flex items-center justify-between gap-4"
                            >
                                <label
                                    class="inline-flex items-center gap-3 text-sm text-slate-300"
                                >
                                    <input
                                        v-model="form.remember"
                                        type="checkbox"
                                        class="h-4 w-4 rounded border-slate-700 bg-slate-950 text-indigo-500 focus:ring-indigo-500/20"
                                    />
                                    Remember me
                                </label>

                                <span
                                    class="text-xs uppercase tracking-[0.18em] text-slate-500"
                                    >Protected access</span
                                >
                            </div>

                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="inline-flex w-full items-center justify-center rounded-2xl bg-indigo-500 px-4 py-3 text-sm font-semibold text-white transition hover:bg-indigo-400 disabled:cursor-not-allowed disabled:bg-indigo-500/50"
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
                                    <path
                                        d="M12 2a10 10 0 1 1-7.07 2.93"
                                        stroke-linecap="round"
                                    />
                                </svg>
                                {{
                                    form.processing
                                        ? "Signing in..."
                                        : "Sign in to dashboard"
                                }}
                            </button>
                        </form>
                    </div>
                </section>
            </div>
        </div>
    </div>
</template>
