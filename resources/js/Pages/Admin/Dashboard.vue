<script setup>
import { computed, defineComponent, h } from "vue";
import { useForm } from "@inertiajs/vue3";

const props = defineProps({
    kpis: {
        type: Object,
        required: true,
    },
    noShowRiskSummary: {
        type: Object,
        required: true,
    },
    aiAnalytics: {
        type: Array,
        required: true,
    },
});

const logoutForm = useForm({});

const AdminLayout = defineComponent({
    name: "AdminLayout",
    setup(_, { slots }) {
        return () =>
            h(
                "div",
                {
                    class: "relative min-h-screen overflow-hidden bg-slate-950 text-slate-100",
                },
                [
                    h("div", {
                        class: "pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(79,70,229,0.20),_transparent_28%),radial-gradient(circle_at_top_right,_rgba(16,185,129,0.16),_transparent_25%),linear-gradient(180deg,_rgba(2,6,23,1)_0%,_rgba(15,23,42,1)_55%,_rgba(2,6,23,1)_100%)]",
                        "aria-hidden": "true",
                    }),
                    h(
                        "div",
                        {
                            class: "relative mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8",
                        },
                        slots.default ? slots.default() : [],
                    ),
                ],
            );
    },
});

const staticOperationalDate = "June 27, 2026";

const safeNumber = (value) => {
    const numericValue = Number(value);
    return Number.isFinite(numericValue) ? numericValue : 0;
};

const formatCurrency = (value) =>
    safeNumber(value).toLocaleString("en-US", {
        style: "currency",
        currency: "USD",
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });

const formatNumber = (value) => safeNumber(value).toLocaleString("en-US");

const formatCost = (value) => {
    const fixedValue = safeNumber(value).toFixed(6);
    const [integerPart, fractionalPart = ""] = fixedValue.split(".");
    const trimmedFraction = fractionalPart.replace(/0+$/, "");

    if (trimmedFraction.length >= 4) {
        return `${integerPart}.${trimmedFraction}`;
    }

    return `${integerPart}.${fractionalPart.slice(0, 4)}`;
};

const formatLatency = (value) => `${formatNumber(value)} ms`;

const featureLabels = {
    triage: "AI Triage",
    soap_draft: "SOAP Draft",
    drug_check: "Drug Interaction Check",
    no_show_pred: "No-Show Prediction",
};

const riskSummarySource = computed(() => props.noShowRiskSummary ?? {});

const analyticsSource = computed(() => props.aiAnalytics ?? []);

const normalizedAnalytics = computed(() => {
    if (!Array.isArray(analyticsSource.value)) {
        return [];
    }

    return analyticsSource.value.map((item) => ({
        feature: item?.feature ?? "unknown",
        label:
            featureLabels[item?.feature] ??
            item?.feature?.replace(/_/g, " ") ??
            "Unknown Pipeline",
        totalTokens: safeNumber(item?.total_tokens),
        accumulatedCost: safeNumber(item?.accumulated_cost),
        avgLatencyMs: safeNumber(item?.avg_latency_ms),
        status: "Active",
    }));
});

const telemetrySummary = computed(() => ({
    pipelines: normalizedAnalytics.value.length,
    tokens: normalizedAnalytics.value.reduce(
        (total, row) => total + row.totalTokens,
        0,
    ),
    cost: normalizedAnalytics.value.reduce(
        (total, row) => total + row.accumulatedCost,
        0,
    ),
    latency:
        normalizedAnalytics.value.length > 0
            ? Math.round(
                  normalizedAnalytics.value.reduce(
                      (total, row) => total + row.avgLatencyMs,
                      0,
                  ) / normalizedAnalytics.value.length,
              )
            : 0,
}));

const riskSummary = computed(() => ({
    high: safeNumber(riskSummarySource.value?.high),
    medium: safeNumber(riskSummarySource.value?.medium),
    low: safeNumber(riskSummarySource.value?.low),
}));

const totalRisk = computed(
    () =>
        riskSummary.value.high +
        riskSummary.value.medium +
        riskSummary.value.low,
);

const riskRows = computed(() => {
    const total = totalRisk.value;
    const base = total > 0 ? total : 1;

    return [
        {
            key: "high",
            label: "High risk",
            tone: "bg-rose-500/15 text-rose-200 ring-1 ring-rose-400/30",
            bar: "bg-rose-500",
            value: riskSummary.value.high,
            percent: Math.round((riskSummary.value.high / base) * 100),
            emphasis: "Immediate review recommended",
        },
        {
            key: "medium",
            label: "Medium risk",
            tone: "bg-amber-500/15 text-amber-200 ring-1 ring-amber-400/30",
            bar: "bg-amber-400",
            value: riskSummary.value.medium,
            percent: Math.round((riskSummary.value.medium / base) * 100),
            emphasis: "Monitor closely",
        },
        {
            key: "low",
            label: "Low risk",
            tone: "bg-emerald-500/15 text-emerald-200 ring-1 ring-emerald-400/30",
            bar: "bg-emerald-400",
            value: riskSummary.value.low,
            percent: Math.round((riskSummary.value.low / base) * 100),
            emphasis: "Routine follow-up",
        },
    ];
});

const kpiCards = computed(() => [
    {
        label: "Today's Appointments",
        value: formatNumber(props.kpis?.today_appointments),
        note: "Scheduled for the active operational window",
        tone: "from-indigo-500/20 via-indigo-500/10 to-slate-900",
        icon: "calendar",
    },
    {
        label: "Monthly Revenue",
        value: formatCurrency(props.kpis?.monthly_revenue),
        note: "Recognized across the current billing cycle",
        tone: "from-emerald-500/20 via-emerald-500/10 to-slate-900",
        icon: "revenue",
    },
    {
        label: "New Patients This Month",
        value: formatNumber(props.kpis?.new_patients_this_month),
        note: "First-time registrations captured by reception",
        tone: "from-amber-500/20 via-amber-500/10 to-slate-900",
        icon: "patients",
    },
]);
</script>

<template>
    <AdminLayout>
        <div class="space-y-6">
            <section
                class="overflow-hidden rounded-3xl border border-slate-800/80 bg-slate-900/70 shadow-2xl shadow-slate-950/40 backdrop-blur-xl"
            >
                <div
                    class="grid gap-6 p-6 sm:p-8 lg:grid-cols-[minmax(0,1.5fr)_minmax(280px,0.95fr)] lg:items-stretch"
                >
                    <div class="space-y-6">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div
                                class="inline-flex items-center gap-2 rounded-full border border-indigo-400/30 bg-indigo-400/10 px-4 py-2 text-xs font-medium uppercase tracking-[0.22em] text-indigo-200"
                            >
                                <span
                                    class="h-2 w-2 rounded-full bg-emerald-400"
                                ></span>
                                Clinic command center
                            </div>

                            <form
                                class="shrink-0"
                                @submit.prevent="logoutForm.post('/logout')"
                            >
                                <button
                                    type="submit"
                                    class="inline-flex items-center gap-2 rounded-full border border-rose-400/30 bg-rose-500/10 px-4 py-2 text-xs font-semibold uppercase tracking-[0.18em] text-rose-200 transition hover:border-rose-300/50 hover:bg-rose-500/20 hover:text-rose-100 focus:outline-none focus:ring-2 focus:ring-rose-400/60 focus:ring-offset-2 focus:ring-offset-slate-950"
                                >
                                    <span class="h-2 w-2 rounded-full bg-rose-300"></span>
                                    Logout
                                </button>
                            </form>
                        </div>

                        <div class="space-y-4">
                            <div class="flex flex-wrap items-center gap-3">
                                <h1
                                    class="text-3xl font-semibold tracking-tight text-white sm:text-4xl"
                                >
                                    SmartClinic AI Admin Dashboard
                                </h1>
                                <span
                                    class="inline-flex items-center rounded-full border border-emerald-400/30 bg-emerald-500/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-emerald-200"
                                >
                                    Live operations
                                </span>
                            </div>

                            <p
                                class="max-w-3xl text-sm leading-6 text-slate-300 sm:text-base"
                            >
                                Operational visibility for today’s clinic
                                activity, AI subsystem telemetry, and
                                appointment risk stratification across the
                                active shift.
                            </p>
                        </div>

                        <div class="grid gap-3 md:grid-cols-3">
                            <div
                                class="rounded-2xl border border-slate-800 bg-slate-950/60 p-4 ring-1 ring-white/5"
                            >
                                <div
                                    class="flex items-center justify-between gap-3"
                                >
                                    <p
                                        class="text-xs uppercase tracking-[0.2em] text-slate-400"
                                    >
                                        System status
                                    </p>
                                    <span
                                        class="h-2.5 w-2.5 rounded-full bg-emerald-400 shadow-[0_0_0_6px_rgba(52,211,153,0.12)]"
                                    ></span>
                                </div>
                                <p
                                    class="mt-2 text-sm font-semibold text-slate-100"
                                >
                                    Stable and synchronized
                                </p>
                                <p
                                    class="mt-1 text-xs leading-5 text-slate-400"
                                >
                                    Admin monitoring, triage, and scheduling
                                    channels are ready.
                                </p>
                            </div>

                            <div
                                class="rounded-2xl border border-slate-800 bg-slate-950/60 p-4 ring-1 ring-white/5"
                            >
                                <p
                                    class="text-xs uppercase tracking-[0.2em] text-slate-400"
                                >
                                    Operational window
                                </p>
                                <p
                                    class="mt-2 text-sm font-semibold text-slate-100"
                                >
                                    {{ staticOperationalDate }}
                                </p>
                                <p
                                    class="mt-1 text-xs leading-5 text-slate-400"
                                >
                                    Daily admin review context for Phase 5
                                    dashboard operations.
                                </p>
                            </div>

                            <div
                                class="rounded-2xl border border-slate-800 bg-slate-950/60 p-4 ring-1 ring-white/5"
                            >
                                <p
                                    class="text-xs uppercase tracking-[0.2em] text-slate-400"
                                >
                                    Command scope
                                </p>
                                <p
                                    class="mt-2 text-sm font-semibold text-slate-100"
                                >
                                    Admin analytics and scheduling
                                </p>
                                <p
                                    class="mt-1 text-xs leading-5 text-slate-400"
                                >
                                    Focused on throughput, revenue, and
                                    appointment risk visibility.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div
                        class="flex h-full flex-col justify-between rounded-3xl border border-slate-800 bg-slate-950/55 p-5 ring-1 ring-white/5"
                    >
                        <div class="space-y-4">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p
                                        class="text-xs uppercase tracking-[0.22em] text-slate-500"
                                    >
                                        Workspace context
                                    </p>
                                    <p
                                        class="mt-2 text-lg font-semibold text-white"
                                    >
                                        Front desk to admin visibility
                                    </p>
                                </div>
                                <div
                                    class="rounded-2xl bg-indigo-500/10 px-3 py-2 text-right ring-1 ring-indigo-400/20"
                                >
                                    <p
                                        class="text-[11px] uppercase tracking-[0.18em] text-indigo-200"
                                    >
                                        Date
                                    </p>
                                    <p
                                        class="mt-1 text-sm font-semibold text-white"
                                    >
                                        Jun 27, 2026
                                    </p>
                                </div>
                            </div>

                            <div
                                class="grid gap-3 sm:grid-cols-2 lg:grid-cols-1 xl:grid-cols-2"
                            >
                                <div
                                    class="rounded-2xl border border-slate-800 bg-slate-900/70 px-4 py-3"
                                >
                                    <p
                                        class="text-xs uppercase tracking-[0.2em] text-slate-500"
                                    >
                                        Focus
                                    </p>
                                    <p
                                        class="mt-1 text-sm font-medium text-slate-100"
                                    >
                                        Clinic command center
                                    </p>
                                </div>
                                <div
                                    class="rounded-2xl border border-slate-800 bg-slate-900/70 px-4 py-3"
                                >
                                    <p
                                        class="text-xs uppercase tracking-[0.2em] text-slate-500"
                                    >
                                        Mode
                                    </p>
                                    <p
                                        class="mt-1 text-sm font-medium text-slate-100"
                                    >
                                        Operational monitoring
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 flex flex-wrap gap-3">
                            <button
                                type="button"
                                class="inline-flex flex-1 items-center justify-center gap-2 rounded-2xl border border-slate-700 bg-slate-900/80 px-4 py-3 text-sm font-semibold text-slate-100 transition hover:border-slate-600 hover:bg-slate-800"
                            >
                                <svg
                                    viewBox="0 0 24 24"
                                    class="h-4 w-4"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                    aria-hidden="true"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M4 12h16M12 4v16"
                                    />
                                </svg>
                                Open schedule
                            </button>
                            <button
                                type="button"
                                class="inline-flex flex-1 items-center justify-center gap-2 rounded-2xl border border-indigo-400/30 bg-indigo-500/15 px-4 py-3 text-sm font-semibold text-indigo-100 transition hover:border-indigo-300/40 hover:bg-indigo-500/25"
                            >
                                <svg
                                    viewBox="0 0 24 24"
                                    class="h-4 w-4"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                    aria-hidden="true"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M12 4v16m8-8H4"
                                    />
                                </svg>
                                Refresh data
                            </button>
                            <button
                                type="button"
                                class="inline-flex w-full items-center justify-center gap-2 rounded-2xl border border-emerald-400/30 bg-emerald-500/15 px-4 py-3 text-sm font-semibold text-emerald-100 transition hover:border-emerald-300/40 hover:bg-emerald-500/25"
                            >
                                <svg
                                    viewBox="0 0 24 24"
                                    class="h-4 w-4"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                    aria-hidden="true"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M12 6v6l4 2"
                                    />
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M20 12a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z"
                                    />
                                </svg>
                                Review AI queue
                            </button>
                        </div>
                    </div>
                </div>
            </section>

            <section class="grid gap-4 md:grid-cols-3">
                <article
                    v-for="card in kpiCards"
                    :key="card.label"
                    class="group relative overflow-hidden rounded-3xl border border-slate-800 bg-slate-900/80 p-6 shadow-lg shadow-slate-950/20 transition hover:-translate-y-0.5 hover:border-slate-700"
                >
                    <div
                        class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r"
                        :class="card.tone"
                    ></div>

                    <div class="flex items-start justify-between gap-4">
                        <div class="space-y-3">
                            <div
                                class="inline-flex items-center gap-2 rounded-full border border-slate-700 bg-slate-950/70 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-300"
                            >
                                KPI highlight
                            </div>

                            <p class="text-sm font-medium text-slate-400">
                                {{ card.label }}
                            </p>
                            <div
                                class="text-3xl font-semibold tracking-tight text-white sm:text-4xl"
                            >
                                {{ card.value }}
                            </div>
                            <p
                                class="max-w-sm text-sm leading-6 text-slate-400"
                            >
                                {{ card.note }}
                            </p>
                        </div>

                        <div
                            class="rounded-2xl bg-gradient-to-br p-3 ring-1 ring-white/5"
                            :class="card.tone"
                        >
                            <svg
                                v-if="card.icon === 'calendar'"
                                viewBox="0 0 24 24"
                                class="h-6 w-6 text-indigo-100"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                                aria-hidden="true"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M8 2v4m8-4v4M3 10h18M5 6h14a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2Z"
                                />
                            </svg>
                            <svg
                                v-else-if="card.icon === 'revenue'"
                                viewBox="0 0 24 24"
                                class="h-6 w-6 text-emerald-100"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                                aria-hidden="true"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M12 3v18m5-14.5C16.5 5.6 14.5 5 12 5s-4.5.6-4.5 1.5S9.5 8 12 8s4.5.6 4.5 1.5S14.5 11 12 11s-4.5.6-4.5 1.5S9.5 14 12 14s4.5.6 4.5 1.5S14.5 17 12 17s-4.5-.6-4.5-1.5"
                                />
                            </svg>
                            <svg
                                v-else
                                viewBox="0 0 24 24"
                                class="h-6 w-6 text-amber-100"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                                aria-hidden="true"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"
                                />
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M8 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm10-1h4m-2-2v4"
                                />
                            </svg>
                        </div>
                    </div>

                    <div class="mt-6 space-y-3">
                        <div
                            class="flex items-center justify-between text-xs uppercase tracking-[0.18em] text-slate-500"
                        >
                            <span>Trend signal</span>
                            <span>Live</span>
                        </div>
                        <div
                            class="h-2 overflow-hidden rounded-full bg-slate-800"
                        >
                            <div
                                class="h-full rounded-full bg-gradient-to-r"
                                :class="card.tone"
                                style="width: 100%"
                            ></div>
                        </div>
                    </div>
                </article>
            </section>

            <section class="grid gap-6 xl:grid-cols-[1.65fr_1fr]">
                <article
                    class="overflow-hidden rounded-3xl border border-slate-800 bg-slate-900/80 shadow-lg shadow-slate-950/20"
                >
                    <div class="border-b border-slate-800 px-6 py-5 sm:px-7">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p
                                    class="text-sm font-medium uppercase tracking-[0.2em] text-slate-400"
                                >
                                    AI subsystem telemetry
                                </p>
                                <h2
                                    class="mt-2 text-xl font-semibold text-white"
                                >
                                    Pipeline load and cost tracking
                                </h2>
                            </div>
                            <div
                                class="rounded-full border border-emerald-400/30 bg-emerald-500/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-emerald-200"
                            >
                                Active
                            </div>
                        </div>
                    </div>

                    <div
                        class="border-b border-slate-800/80 bg-slate-950/40 px-6 py-5 sm:px-7"
                    >
                        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                            <div
                                class="rounded-2xl border border-slate-800 bg-slate-950/60 px-4 py-4"
                            >
                                <p
                                    class="text-xs uppercase tracking-[0.18em] text-slate-500"
                                >
                                    Pipelines
                                </p>
                                <p
                                    class="mt-2 text-2xl font-semibold text-white"
                                >
                                    {{
                                        formatNumber(telemetrySummary.pipelines)
                                    }}
                                </p>
                            </div>
                            <div
                                class="rounded-2xl border border-slate-800 bg-slate-950/60 px-4 py-4"
                            >
                                <p
                                    class="text-xs uppercase tracking-[0.18em] text-slate-500"
                                >
                                    Tokens processed
                                </p>
                                <p
                                    class="mt-2 text-2xl font-semibold text-white"
                                >
                                    {{ formatNumber(telemetrySummary.tokens) }}
                                </p>
                            </div>
                            <div
                                class="rounded-2xl border border-slate-800 bg-slate-950/60 px-4 py-4"
                            >
                                <p
                                    class="text-xs uppercase tracking-[0.18em] text-slate-500"
                                >
                                    Accumulated cost
                                </p>
                                <p
                                    class="mt-2 text-2xl font-semibold text-white"
                                >
                                    {{ formatCost(telemetrySummary.cost) }}
                                </p>
                            </div>
                            <div
                                class="rounded-2xl border border-slate-800 bg-slate-950/60 px-4 py-4"
                            >
                                <p
                                    class="text-xs uppercase tracking-[0.18em] text-slate-500"
                                >
                                    Avg latency
                                </p>
                                <p
                                    class="mt-2 text-2xl font-semibold text-white"
                                >
                                    {{
                                        formatLatency(telemetrySummary.latency)
                                    }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table
                            class="min-w-full divide-y divide-slate-800 text-left"
                        >
                            <thead
                                class="bg-slate-950/60 text-xs uppercase tracking-[0.18em] text-slate-400"
                            >
                                <tr>
                                    <th class="px-6 py-4 sm:px-7">Feature</th>
                                    <th class="px-6 py-4 sm:px-7">
                                        Total tokens
                                    </th>
                                    <th class="px-6 py-4 sm:px-7">
                                        Accumulated cost
                                    </th>
                                    <th class="px-6 py-4 sm:px-7">
                                        Avg latency
                                    </th>
                                    <th class="px-6 py-4 sm:px-7">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800">
                                <tr v-if="normalizedAnalytics.length === 0">
                                    <td
                                        colspan="5"
                                        class="px-6 py-10 text-center text-sm text-slate-400 sm:px-7"
                                    >
                                        No AI telemetry has been reported for
                                        the current window.
                                    </td>
                                </tr>
                                <tr
                                    v-for="row in normalizedAnalytics"
                                    :key="row.feature"
                                    class="bg-slate-900/30 transition hover:bg-slate-800/40"
                                >
                                    <td class="px-6 py-5 sm:px-7">
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="flex h-10 w-10 items-center justify-center rounded-2xl border border-slate-800 bg-slate-950/80 text-sm font-semibold text-slate-100 ring-1 ring-white/5"
                                            >
                                                {{ row.label.charAt(0) }}
                                            </div>
                                            <div class="space-y-1">
                                                <div
                                                    class="text-sm font-semibold text-white"
                                                >
                                                    {{ row.label }}
                                                </div>
                                                <div
                                                    class="text-xs uppercase tracking-[0.16em] text-slate-500"
                                                >
                                                    {{ row.feature }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td
                                        class="px-6 py-5 text-sm text-slate-200 sm:px-7"
                                    >
                                        <div class="font-medium text-white">
                                            {{ formatNumber(row.totalTokens) }}
                                        </div>
                                        <div
                                            class="mt-1 text-xs text-slate-500"
                                        >
                                            Localized total
                                        </div>
                                    </td>
                                    <td
                                        class="px-6 py-5 text-sm text-slate-200 sm:px-7"
                                    >
                                        <div class="font-medium text-white">
                                            {{
                                                formatCost(row.accumulatedCost)
                                            }}
                                        </div>
                                        <div
                                            class="mt-1 text-xs text-slate-500"
                                        >
                                            USD spend, precise to 4-6 decimals
                                        </div>
                                    </td>
                                    <td
                                        class="px-6 py-5 text-sm text-slate-200 sm:px-7"
                                    >
                                        <div class="font-medium text-white">
                                            {{
                                                formatLatency(row.avgLatencyMs)
                                            }}
                                        </div>
                                        <div
                                            class="mt-1 text-xs text-slate-500"
                                        >
                                            Processing latency
                                        </div>
                                    </td>
                                    <td class="px-6 py-5 sm:px-7">
                                        <div class="flex flex-col gap-2">
                                            <span
                                                class="inline-flex w-fit items-center gap-2 rounded-full border border-emerald-400/30 bg-emerald-500/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] text-emerald-200"
                                            >
                                                <span
                                                    class="h-2 w-2 rounded-full bg-emerald-400"
                                                ></span>
                                                {{ row.status }}
                                            </span>
                                            <div
                                                class="h-2 w-28 overflow-hidden rounded-full bg-slate-800"
                                            >
                                                <div
                                                    class="h-full w-full rounded-full bg-gradient-to-r from-emerald-400 via-emerald-300 to-indigo-400"
                                                ></div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </article>

                <article
                    class="overflow-hidden rounded-3xl border border-slate-800 bg-slate-900/80 shadow-lg shadow-slate-950/20"
                >
                    <div class="border-b border-slate-800 px-6 py-5 sm:px-7">
                        <div
                            class="flex flex-wrap items-start justify-between gap-4"
                        >
                            <div>
                                <p
                                    class="text-sm font-medium uppercase tracking-[0.2em] text-slate-400"
                                >
                                    No-show risk matrix
                                </p>
                                <h2
                                    class="mt-2 text-xl font-semibold text-white"
                                >
                                    Priority overview for upcoming slots
                                </h2>
                            </div>
                            <div
                                class="inline-flex items-center gap-2 rounded-full border border-rose-400/30 bg-rose-500/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-rose-200"
                            >
                                <span
                                    class="h-2 w-2 rounded-full bg-rose-400"
                                ></span>
                                Attention required
                            </div>
                        </div>
                    </div>

                    <div class="space-y-5 p-6 sm:p-7">
                        <div
                            class="rounded-3xl border border-slate-800 bg-slate-950/70 p-5 ring-1 ring-white/5"
                        >
                            <div
                                class="grid gap-4 lg:grid-cols-[1.3fr_0.9fr] lg:items-center"
                            >
                                <div class="space-y-3">
                                    <p
                                        class="text-sm font-medium text-slate-300"
                                    >
                                        Matrix signal
                                    </p>
                                    <p class="text-sm leading-6 text-slate-400">
                                        Use this widget to filter high-risk
                                        upcoming appointments first, then work
                                        down through medium and low risk lanes.
                                    </p>
                                    <div class="flex flex-wrap gap-2">
                                        <span
                                            class="inline-flex items-center rounded-full border border-rose-400/20 bg-rose-500/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] text-rose-200"
                                            >High</span
                                        >
                                        <span
                                            class="inline-flex items-center rounded-full border border-amber-400/20 bg-amber-500/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] text-amber-200"
                                            >Medium</span
                                        >
                                        <span
                                            class="inline-flex items-center rounded-full border border-emerald-400/20 bg-emerald-500/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] text-emerald-200"
                                            >Low</span
                                        >
                                    </div>
                                </div>

                                <div
                                    class="rounded-3xl border border-slate-800 bg-slate-900/80 p-4"
                                >
                                    <div
                                        class="flex items-center justify-between gap-4"
                                    >
                                        <div>
                                            <p
                                                class="text-xs uppercase tracking-[0.18em] text-slate-500"
                                            >
                                                Total flagged
                                            </p>
                                            <p
                                                class="mt-2 text-3xl font-semibold text-white"
                                            >
                                                {{ formatNumber(totalRisk) }}
                                            </p>
                                        </div>
                                        <div
                                            class="h-16 w-16 rounded-2xl border border-rose-400/20 bg-gradient-to-br from-rose-500/20 via-amber-500/10 to-emerald-500/10 p-3"
                                        >
                                            <div
                                                class="flex h-full w-full items-center justify-center rounded-xl border border-slate-800 bg-slate-950/80 text-sm font-semibold text-slate-100"
                                            >
                                                Risk
                                            </div>
                                        </div>
                                    </div>

                                    <div
                                        class="mt-4 h-3 overflow-hidden rounded-full bg-slate-800"
                                    >
                                        <div
                                            class="h-full bg-gradient-to-r from-rose-500 via-amber-400 to-emerald-400"
                                            :style="{
                                                width:
                                                    totalRisk > 0
                                                        ? '100%'
                                                        : '0%',
                                            }"
                                        ></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="grid gap-3">
                            <div
                                v-for="row in riskRows"
                                :key="row.key"
                                class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-950/60"
                            >
                                <div
                                    class="grid gap-4 p-4 sm:p-5 md:grid-cols-[1fr_auto] md:items-center"
                                >
                                    <div class="space-y-3">
                                        <div
                                            class="flex flex-wrap items-center gap-3"
                                        >
                                            <span
                                                class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em]"
                                                :class="row.tone"
                                            >
                                                {{ row.label }}
                                            </span>
                                            <span
                                                class="text-sm text-slate-400"
                                                >{{ row.emphasis }}</span
                                            >
                                        </div>

                                        <div
                                            class="flex items-end justify-between gap-4"
                                        >
                                            <div>
                                                <p
                                                    class="text-xs uppercase tracking-[0.18em] text-slate-500"
                                                >
                                                    Appointments flagged
                                                </p>
                                                <p
                                                    class="mt-1 text-3xl font-semibold text-white"
                                                >
                                                    {{
                                                        formatNumber(row.value)
                                                    }}
                                                </p>
                                            </div>
                                            <div class="text-right md:hidden">
                                                <p
                                                    class="text-xs uppercase tracking-[0.18em] text-slate-500"
                                                >
                                                    Share
                                                </p>
                                                <p
                                                    class="mt-1 text-sm font-medium text-slate-200"
                                                >
                                                    {{ row.percent }}%
                                                </p>
                                            </div>
                                        </div>

                                        <div
                                            class="h-2 overflow-hidden rounded-full bg-slate-800"
                                        >
                                            <div
                                                class="h-full rounded-full"
                                                :class="row.bar"
                                                :style="{
                                                    width: `${row.percent}%`,
                                                }"
                                            ></div>
                                        </div>
                                    </div>

                                    <div
                                        class="hidden rounded-2xl border border-slate-800 bg-slate-900/70 px-4 py-3 text-right md:block"
                                    >
                                        <p
                                            class="text-xs uppercase tracking-[0.18em] text-slate-500"
                                        >
                                            Share
                                        </p>
                                        <p
                                            class="mt-1 text-lg font-semibold text-white"
                                        >
                                            {{ row.percent }}%
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </article>
            </section>
        </div>
    </AdminLayout>
</template>
