<script setup>
import { Head, router, useForm } from "@inertiajs/vue3";
import { computed, ref } from "vue";

const props = defineProps({
    clinic: {
        type: Object,
        required: true,
    },
    operational_date: {
        type: String,
        required: true,
    },
    kpis: {
        type: Object,
        required: true,
    },
    no_show_risk_summary: {
        type: Object,
        required: true,
    },
    appointments_today: {
        type: Array,
        required: true,
    },
    no_show_risk_appointments: {
        type: Array,
        required: true,
    },
    active_doctors: {
        type: Array,
        required: true,
    },
    recent_invoices: {
        type: Array,
        required: true,
    },
    ai_analytics: {
        type: Array,
        required: true,
    },
    appointment_chart: {
        type: Object,
        required: true,
    },
});

const logoutForm = useForm({});
const selectedTab = ref("all");

const sectionRefs = {
    overview: ref(null),
    appointments: ref(null),
    risk: ref(null),
    analytics: ref(null),
    doctors: ref(null),
    invoices: ref(null),
};

const featureLabels = {
    triage: "Symptom triage",
    soap_draft: "SOAP note drafts",
    drug_check: "Drug interaction check",
    no_show_pred: "No-show prediction",
};

const formatNumber = (value) =>
    new Intl.NumberFormat("en-US").format(Number(value ?? 0));

const formatCurrency = (value) =>
    new Intl.NumberFormat("en-US", {
        style: "currency",
        currency: "USD",
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(Number(value ?? 0));

const formatPercent = (value) => `${Number(value ?? 0).toFixed(1)}%`;

const formatDate = (value) => {
    if (!value) {
        return "-";
    }

    return new Intl.DateTimeFormat("en-US", {
        month: "short",
        day: "numeric",
    }).format(new Date(value));
};

const formatTime = (value) => {
    if (!value) {
        return "-";
    }

    return String(value).slice(0, 5);
};

const initials = (value) => {
    const parts = String(value ?? "")
        .trim()
        .split(/\s+/)
        .filter(Boolean);

    if (parts.length === 0) {
        return "SC";
    }

    return parts
        .slice(0, 2)
        .map((part) => part.charAt(0))
        .join("")
        .toUpperCase();
};

const statusTone = (status) => {
    switch (status) {
        case "confirmed":
        case "completed":
            return {
                badge: "bg-emerald-500/15 text-emerald-200 ring-1 ring-emerald-400/30",
                dot: "bg-emerald-400",
            };
        case "in_progress":
            return {
                badge: "bg-sky-500/15 text-sky-200 ring-1 ring-sky-400/30",
                dot: "bg-sky-400",
            };
        case "pending":
            return {
                badge: "bg-amber-500/15 text-amber-200 ring-1 ring-amber-400/30",
                dot: "bg-amber-400",
            };
        case "cancelled":
        case "failed":
        case "no_show":
            return {
                badge: "bg-rose-500/15 text-rose-200 ring-1 ring-rose-400/30",
                dot: "bg-rose-400",
            };
        default:
            return {
                badge: "bg-slate-500/15 text-slate-200 ring-1 ring-slate-400/30",
                dot: "bg-slate-400",
            };
    }
};

const riskTone = (bucket) => {
    switch (bucket) {
        case "high":
            return {
                badge: "bg-rose-500/15 text-rose-200 ring-1 ring-rose-400/30",
                bar: "bg-rose-400",
            };
        case "medium":
            return {
                badge: "bg-amber-500/15 text-amber-200 ring-1 ring-amber-400/30",
                bar: "bg-amber-400",
            };
        default:
            return {
                badge: "bg-emerald-500/15 text-emerald-200 ring-1 ring-emerald-400/30",
                bar: "bg-emerald-400",
            };
    }
};

const appointmentTabs = computed(() => {
    const counts = props.appointments_today.reduce(
        (accumulator, appointment) => {
            const status = appointment.status ?? "unknown";
            accumulator.all += 1;
            if (status === "confirmed") accumulator.confirmed += 1;
            if (status === "pending") accumulator.pending += 1;
            if (status === "in_progress") accumulator.in_progress += 1;
            return accumulator;
        },
        {
            all: 0,
            confirmed: 0,
            pending: 0,
            in_progress: 0,
        },
    );

    return [
        { key: "all", label: "All", count: counts.all },
        { key: "confirmed", label: "Confirmed", count: counts.confirmed },
        { key: "pending", label: "Pending", count: counts.pending },
        { key: "in_progress", label: "In progress", count: counts.in_progress },
    ];
});

const filteredAppointments = computed(() => {
    if (selectedTab.value === "all") {
        return props.appointments_today;
    }

    return props.appointments_today.filter(
        (appointment) => appointment.status === selectedTab.value,
    );
});

const clinicInitials = computed(() => initials(props.clinic?.name));

const kpiCards = computed(() => [
    {
        label: "Today's appointments",
        value: formatNumber(props.kpis?.today_appointments),
        note: "Scheduled for the active clinic window",
        icon: "calendar",
        tone: "from-sky-500/20 via-sky-500/10 to-slate-900",
    },
    {
        label: "New patients this month",
        value: formatNumber(props.kpis?.new_patients_this_month),
        note: "First-time patients with clinic activity",
        icon: "patients",
        tone: "from-amber-500/20 via-amber-500/10 to-slate-900",
    },
    {
        label: "Revenue this month",
        value: formatCurrency(props.kpis?.monthly_revenue),
        note: "Paid invoices recorded for the clinic",
        icon: "revenue",
        tone: "from-emerald-500/20 via-emerald-500/10 to-slate-900",
    },
    {
        label: "No-show rate",
        value: formatPercent(props.kpis?.no_show_rate),
        note: "Month-to-date appointments marked no-show",
        icon: "risk",
        tone: "from-rose-500/20 via-rose-500/10 to-slate-900",
    },
]);

const noShowSummaryCards = computed(() => [
    {
        label: "High risk",
        value: formatNumber(props.no_show_risk_summary?.high),
        tone: "text-rose-200",
    },
    {
        label: "Medium risk",
        value: formatNumber(props.no_show_risk_summary?.medium),
        tone: "text-amber-200",
    },
    {
        label: "Low risk",
        value: formatNumber(props.no_show_risk_summary?.low),
        tone: "text-emerald-200",
    },
]);

const riskRows = computed(() =>
    props.no_show_risk_appointments.map((appointment) => {
        const tone = riskTone(appointment.risk_bucket);

        return {
            ...appointment,
            tone,
        };
    }),
);

const aiRows = computed(() =>
    props.ai_analytics.map((item) => ({
        ...item,
        label: featureLabels[item.feature] ?? item.feature?.replace(/_/g, " ") ?? "Unknown",
    })),
);

const chartRows = computed(() => {
    const labels = props.appointment_chart?.labels ?? [];
    const completed = props.appointment_chart?.completed ?? [];
    const cancelled = props.appointment_chart?.cancelled ?? [];
    const noShow = props.appointment_chart?.no_show ?? [];

    return labels.map((label, index) => ({
        label,
        completed: Number(completed[index] ?? 0),
        cancelled: Number(cancelled[index] ?? 0),
        noShow: Number(noShow[index] ?? 0),
    }));
});

const chartMax = computed(() =>
    Math.max(1, ...chartRows.value.map((row) => row.completed + row.cancelled + row.noShow)),
);

const scrollToSection = (key) => {
    const target = sectionRefs[key]?.value;

    if (target && typeof target.scrollIntoView === "function") {
        target.scrollIntoView({ behavior: "smooth", block: "start" });
    }
};

const setSectionRef = (key) => (element) => {
    sectionRefs[key].value = element;
};

const refreshDashboard = () => {
    router.reload({ preserveScroll: true });
};

const viewAppointments = (tab = "all") => {
    selectedTab.value = tab;
    scrollToSection("appointments");
};

const sidebarItems = [
    { label: "Overview", key: "overview" },
    { label: "Appointments", key: "appointments" },
    { label: "Risk alerts", key: "risk" },
    { label: "Doctors", key: "doctors" },
    { label: "AI analytics", key: "analytics" },
    { label: "Invoices", key: "invoices" },
];
</script>

<template>
    <Head title="SmartClinic AI Dashboard" />

    <div class="dashboard-shell min-h-screen overflow-hidden bg-[#f5f3ea] text-slate-900">
        <aside class="dashboard-sidebar hidden w-[248px] shrink-0 flex-col border-r border-[#e2dccf] bg-[#fbf8f1] lg:flex">
            <div class="dashboard-sidebar__header border-b border-[#e2dccf] px-5 py-4">
                <div class="flex items-center gap-3">
                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-[#0f6e56] text-sm font-semibold text-white shadow-sm shadow-emerald-900/15">
                        <span>{{ clinicInitials }}</span>
                    </div>
                    <div>
                        <div class="text-sm font-semibold text-slate-900">
                            SmartClinic AI
                        </div>
                        <div class="text-xs text-slate-500">{{ clinic?.name ?? 'Clinic dashboard' }}</div>
                    </div>
                </div>
            </div>

            <nav class="dashboard-nav flex-1 space-y-1 overflow-y-auto px-3 py-4">
                <button
                    v-for="item in sidebarItems"
                    :key="item.key"
                    type="button"
                    class="flex w-full items-center gap-3 rounded-2xl px-3 py-2.5 text-left text-sm font-medium text-slate-600 transition hover:bg-[#f1ebdf] hover:text-slate-900"
                    @click="scrollToSection(item.key)"
                >
                    <span class="h-2.5 w-2.5 rounded-full bg-[#0f6e56]/30"></span>
                    <span>{{ item.label }}</span>
                </button>
            </nav>

            <div class="dashboard-sidebar__footer border-t border-[#e2dccf] p-4">
                <div class="rounded-2xl bg-white p-3 shadow-sm ring-1 ring-black/5">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#d4edea] text-xs font-semibold text-[#0f6e56]">
                            {{ clinicInitials }}
                        </div>
                        <div class="min-w-0">
                            <div class="truncate text-sm font-semibold text-slate-900">{{ clinic?.name ?? 'Clinic' }}</div>
                            <div class="text-xs text-slate-500">
                                {{ clinic?.subscription_plan ?? 'Active plan' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </aside>

        <main class="dashboard-main flex min-w-0 flex-1 flex-col">
            <header class="dashboard-header border-b border-[#e2dccf] bg-[#fbf8f1]/95 px-4 py-3 shadow-sm shadow-black/[0.02] backdrop-blur md:px-6">
                <div class="dashboard-header__inner flex flex-wrap items-center gap-3">
                    <div class="min-w-0 flex-1">
                        <h1 class="truncate text-lg font-semibold text-slate-900 md:text-xl">
                            Dashboard
                        </h1>
                        <p class="text-sm text-slate-500">
                            {{ operational_date }}
                        </p>
                    </div>

                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-full border border-[#cfd7d3] bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-[#0f6e56]/30 hover:text-[#0f6e56]"
                        @click="viewAppointments('pending')"
                    >
                        <span class="text-base leading-none">+</span>
                        New appointment
                    </button>

                    <button
                        type="button"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-[#cfd7d3] bg-white text-slate-600 transition hover:border-[#0f6e56]/30 hover:text-[#0f6e56]"
                        @click="refreshDashboard"
                    >
                        <span class="text-sm font-semibold">↻</span>
                    </button>

                    <form @submit.prevent="logoutForm.post('/logout')">
                        <button
                            type="submit"
                            class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-[#0f6e56] text-sm font-semibold text-white transition hover:bg-[#0c5f49]"
                        >
                            <span>{{ initials('FA') }}</span>
                        </button>
                    </form>
                </div>
            </header>

            <div class="dashboard-content flex-1 overflow-y-auto px-4 py-5 md:px-6 lg:px-7">
                <section :ref="setSectionRef('overview')" class="dashboard-grid dashboard-grid--hero grid gap-4 xl:grid-cols-[minmax(0,1.5fr)_minmax(280px,0.9fr)]">
                    <div class="dashboard-panel dashboard-panel--hero rounded-[28px] border border-[#e3dccd] bg-[#fffdf8] p-5 shadow-[0_16px_40px_rgba(15,23,42,0.06)] lg:p-6">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <div class="inline-flex items-center gap-2 rounded-full border border-[#0f6e56]/15 bg-[#0f6e56]/8 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-[#0f6e56]">
                                    <span class="h-2 w-2 rounded-full bg-[#0f6e56]"></span>
                                    Live operations
                                </div>
                                <h2 class="mt-4 text-2xl font-semibold tracking-tight text-slate-950 md:text-4xl">
                                    SmartClinic AI admin dashboard
                                </h2>
                                <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600 md:text-base">
                                    Operational visibility for today’s clinic activity, appointment flow, no-show risk, AI telemetry, and invoice activity.
                                </p>
                            </div>

                            <div class="rounded-3xl border border-[#e3dccd] bg-white px-4 py-3 shadow-sm">
                                <p class="text-[11px] uppercase tracking-[0.18em] text-slate-500">Clinic</p>
                                <p class="mt-1 text-sm font-semibold text-slate-900">{{ clinic?.name ?? 'SmartClinic' }}</p>
                                <p class="text-xs text-slate-500">{{ clinic?.subscription_plan ?? 'Pro plan' }}</p>
                            </div>
                        </div>

                        <div class="mt-6 grid gap-3 sm:grid-cols-3">
                            <div class="rounded-3xl border border-[#e3dccd] bg-[#fcfaf4] p-4">
                                <p class="text-xs uppercase tracking-[0.18em] text-slate-500">System status</p>
                                <p class="mt-2 text-sm font-semibold text-slate-950">Stable and synchronized</p>
                                <p class="mt-1 text-xs leading-5 text-slate-500">Admin monitoring, triage, and scheduling data are current.</p>
                            </div>
                            <div class="rounded-3xl border border-[#e3dccd] bg-[#fcfaf4] p-4">
                                <p class="text-xs uppercase tracking-[0.18em] text-slate-500">Operational window</p>
                                <p class="mt-2 text-sm font-semibold text-slate-950">{{ operational_date }}</p>
                                <p class="mt-1 text-xs leading-5 text-slate-500">Daily admin review context for the active shift.</p>
                            </div>
                            <div class="rounded-3xl border border-[#e3dccd] bg-[#fcfaf4] p-4">
                                <p class="text-xs uppercase tracking-[0.18em] text-slate-500">Command scope</p>
                                <p class="mt-2 text-sm font-semibold text-slate-950">Appointments and analytics</p>
                                <p class="mt-1 text-xs leading-5 text-slate-500">Throughput, revenue, and AI risk visibility in one place.</p>
                            </div>
                        </div>
                    </div>

                    <div class="dashboard-panel dashboard-panel--hero rounded-[28px] border border-[#e3dccd] bg-[#fffdf8] p-5 shadow-[0_16px_40px_rgba(15,23,42,0.06)] lg:p-6">
                        <div class="space-y-4">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-[11px] uppercase tracking-[0.2em] text-slate-500">Quick actions</p>
                                    <h3 class="mt-2 text-lg font-semibold text-slate-950">Front desk to admin visibility</h3>
                                </div>
                                <div class="rounded-2xl bg-[#0f6e56]/10 px-3 py-2 text-right ring-1 ring-[#0f6e56]/15">
                                    <p class="text-[11px] uppercase tracking-[0.18em] text-[#0f6e56]">Date</p>
                                    <p class="mt-1 text-sm font-semibold text-slate-950">{{ operational_date }}</p>
                                </div>
                            </div>

                            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-2">
                                <button type="button" class="rounded-2xl border border-[#e3dccd] bg-white px-4 py-3 text-left transition hover:border-[#0f6e56]/30" @click="viewAppointments('all')">
                                    <p class="text-xs uppercase tracking-[0.18em] text-slate-500">Focus</p>
                                    <p class="mt-1 text-sm font-semibold text-slate-950">Clinic command center</p>
                                </button>
                                <button type="button" class="rounded-2xl border border-[#e3dccd] bg-white px-4 py-3 text-left transition hover:border-[#0f6e56]/30" @click="refreshDashboard">
                                    <p class="text-xs uppercase tracking-[0.18em] text-slate-500">Mode</p>
                                    <p class="mt-1 text-sm font-semibold text-slate-950">Refresh data</p>
                                </button>
                            </div>

                            <div class="grid gap-3">
                                <button type="button" class="inline-flex items-center justify-center gap-2 rounded-2xl border border-[#0f6e56]/20 bg-[#0f6e56]/10 px-4 py-3 text-sm font-semibold text-[#0f6e56] transition hover:bg-[#0f6e56]/15" @click="scrollToSection('analytics')">
                                    <span>↗</span>
                                    Review AI queue
                                </button>
                                <button type="button" class="inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 transition hover:border-slate-300" @click="scrollToSection('appointments')">
                                    <span>↗</span>
                                    Open schedule
                                </button>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="dashboard-grid dashboard-grid--kpi mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <article v-for="card in kpiCards" :key="card.label" class="dashboard-card rounded-[28px] border border-[#e3dccd] bg-[#fffdf8] p-5 shadow-[0_16px_40px_rgba(15,23,42,0.06)]">
                        <div class="flex items-start justify-between gap-4">
                            <div class="space-y-2">
                                <div class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">
                                    KPI highlight
                                </div>
                                <p class="text-sm font-medium text-slate-500">{{ card.label }}</p>
                                <div class="text-3xl font-semibold tracking-tight text-slate-950">{{ card.value }}</div>
                                <p class="max-w-sm text-sm leading-6 text-slate-600">{{ card.note }}</p>
                            </div>

                            <div class="rounded-2xl bg-gradient-to-br p-3 ring-1 ring-black/5" :class="card.tone">
                                <span v-if="card.icon === 'calendar'" class="text-lg font-semibold text-sky-100">⌁</span>
                                <span v-else-if="card.icon === 'patients'" class="text-lg font-semibold text-amber-100">◉</span>
                                <span v-else-if="card.icon === 'revenue'" class="text-lg font-semibold text-emerald-100">¤</span>
                                <span v-else class="text-lg font-semibold text-rose-100">!</span>
                            </div>
                        </div>

                        <div class="mt-6 space-y-3">
                            <div class="flex items-center justify-between text-xs uppercase tracking-[0.18em] text-slate-500">
                                <span>Trend signal</span>
                                <span>Live</span>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-[#f1ebdf]">
                                <div class="h-full rounded-full bg-gradient-to-r" :class="card.tone"></div>
                            </div>
                        </div>
                    </article>
                </section>

                <section :ref="setSectionRef('appointments')" class="dashboard-grid dashboard-grid--split mt-5 grid gap-4 xl:grid-cols-[minmax(0,1.5fr)_minmax(320px,0.95fr)]">
                    <article class="dashboard-panel rounded-[28px] border border-[#e3dccd] bg-[#fffdf8] shadow-[0_16px_40px_rgba(15,23,42,0.06)]">
                        <div class="border-b border-[#e3dccd] px-5 py-4 lg:px-6">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-sm font-medium uppercase tracking-[0.2em] text-slate-500">Appointments today</p>
                                    <h2 class="mt-2 text-xl font-semibold text-slate-950">Clinic flow and live status</h2>
                                </div>
                                <button type="button" class="text-sm font-semibold text-[#0f6e56] transition hover:text-[#0c5f49]" @click="refreshDashboard">
                                    Refresh ↗
                                </button>
                            </div>
                        </div>

                        <div class="dashboard-panel__body px-5 py-4 lg:px-6">
                            <div class="dashboard-tabs mb-4 flex flex-wrap gap-2 border-b border-[#e3dccd] pb-4">
                                <button
                                    v-for="tab in appointmentTabs"
                                    :key="tab.key"
                                    type="button"
                                    class="rounded-full px-4 py-2 text-sm font-medium transition"
                                    :class="selectedTab === tab.key ? 'bg-[#0f6e56] text-white' : 'bg-white text-slate-600 ring-1 ring-[#e3dccd] hover:bg-[#f1ebdf]'"
                                    @click="selectedTab = tab.key"
                                >
                                    {{ tab.label }} ({{ tab.count }})
                                </button>
                            </div>

                            <div class="space-y-3">
                                <div v-if="filteredAppointments.length === 0" class="rounded-2xl border border-dashed border-[#d8d2c3] bg-[#fcfaf4] px-4 py-8 text-center text-sm text-slate-500">
                                    No appointments in this status bucket.
                                </div>

                                <div
                                    v-for="appointment in filteredAppointments"
                                    :key="appointment.id"
                                    class="flex flex-wrap items-center gap-3 rounded-2xl border border-[#e3dccd] bg-white px-4 py-3"
                                >
                                    <div class="w-16 shrink-0 text-center text-sm font-semibold text-slate-500">
                                        {{ formatTime(appointment.time) }}
                                    </div>
                                    <div class="h-2.5 w-2.5 shrink-0 rounded-full" :class="statusTone(appointment.status).dot"></div>
                                    <div class="min-w-0 flex-1">
                                        <div class="truncate text-sm font-semibold text-slate-950">{{ appointment.patient_name }}</div>
                                        <div class="truncate text-xs text-slate-500">
                                            {{ appointment.doctor_name }} · {{ appointment.doctor_specialty }}
                                        </div>
                                    </div>
                                    <span class="rounded-full px-3 py-1 text-xs font-semibold" :class="statusTone(appointment.status).badge">
                                        {{ appointment.status_label }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </article>

                    <div class="dashboard-stack space-y-4">
                        <article :ref="setSectionRef('risk')" class="dashboard-panel rounded-[28px] border border-[#e3dccd] bg-[#fffdf8] p-5 shadow-[0_16px_40px_rgba(15,23,42,0.06)] lg:p-6">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-sm font-medium uppercase tracking-[0.2em] text-slate-500">No-show risk</p>
                                    <h2 class="mt-2 text-lg font-semibold text-slate-950">Today’s highest-risk appointments</h2>
                                </div>
                                <button type="button" class="text-sm font-semibold text-[#0f6e56] transition hover:text-[#0c5f49]" @click="scrollToSection('appointments')">
                                    AI advice ↗
                                </button>
                            </div>

                            <div class="mt-4 space-y-3">
                                <div v-if="riskRows.length === 0" class="rounded-2xl border border-dashed border-[#d8d2c3] bg-[#fcfaf4] px-4 py-6 text-sm text-slate-500">
                                    No elevated risk detected for the current window.
                                </div>

                                <div v-for="appointment in riskRows" :key="appointment.id" class="rounded-2xl border border-[#e3dccd] bg-white p-4">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <div class="text-sm font-semibold text-slate-950">{{ appointment.patient_name }}</div>
                                            <div class="text-xs text-slate-500">{{ formatTime(appointment.time) }} · {{ appointment.doctor_name }}</div>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-sm font-semibold text-slate-950">{{ appointment.risk_percent }}%</div>
                                            <div class="text-[11px] uppercase tracking-[0.18em] text-slate-500">risk</div>
                                        </div>
                                    </div>

                                    <div class="mt-3 flex items-center gap-3">
                                        <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-[#f1ebdf]">
                                            <div class="h-full rounded-full" :class="appointment.tone.bar" :style="{ width: `${appointment.risk_percent}%` }"></div>
                                        </div>
                                        <span class="rounded-full px-3 py-1 text-xs font-semibold" :class="appointment.tone.badge">
                                            {{ appointment.risk_label }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </article>

                        <article :ref="setSectionRef('doctors')" class="dashboard-panel rounded-[28px] border border-[#e3dccd] bg-[#fffdf8] p-5 shadow-[0_16px_40px_rgba(15,23,42,0.06)] lg:p-6">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-sm font-medium uppercase tracking-[0.2em] text-slate-500">Active doctors</p>
                                    <h2 class="mt-2 text-lg font-semibold text-slate-950">Today’s appointment load</h2>
                                </div>
                            </div>

                            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                <div v-for="doctor in active_doctors" :key="doctor.id" class="rounded-2xl border border-[#e3dccd] bg-white p-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-11 w-11 items-center justify-center rounded-full bg-[#d4edea] text-sm font-semibold text-[#0f6e56]">
                                            {{ doctor.initials }}
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <div class="truncate text-sm font-semibold text-slate-950">{{ doctor.name }}</div>
                                            <div class="truncate text-xs text-slate-500">{{ doctor.specialty }}</div>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-lg font-semibold text-slate-950">{{ doctor.appointment_count }}</div>
                                            <div class="text-[11px] uppercase tracking-[0.18em] text-slate-500">
                                                {{ doctor.appointment_count > 0 ? 'appts' : 'off today' }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </article>
                    </div>
                </section>

                <section class="dashboard-grid dashboard-grid--analytics mt-5 grid gap-4 xl:grid-cols-[minmax(0,1.65fr)_minmax(0,1fr)]">
                    <article :ref="setSectionRef('analytics')" class="dashboard-panel rounded-[28px] border border-[#e3dccd] bg-[#fffdf8] shadow-[0_16px_40px_rgba(15,23,42,0.06)]">
                        <div class="border-b border-[#e3dccd] px-5 py-4 lg:px-6">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-sm font-medium uppercase tracking-[0.2em] text-slate-500">Appointments last 30 days</p>
                                    <h2 class="mt-2 text-xl font-semibold text-slate-950">Stacked volume and outcomes</h2>
                                </div>
                                <button type="button" class="text-sm font-semibold text-[#0f6e56] transition hover:text-[#0c5f49]" @click="scrollToSection('analytics')">
                                    Export ↗
                                </button>
                            </div>
                        </div>

                        <div class="px-5 py-5 lg:px-6">
                            <div class="mb-4 flex flex-wrap gap-4 text-xs text-slate-500">
                                <div class="flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-sm bg-sky-500"></span>Completed</div>
                                <div class="flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-sm bg-amber-500"></span>Cancelled</div>
                                <div class="flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-sm bg-rose-500"></span>No-show</div>
                            </div>

                            <div class="overflow-x-auto pb-2">
                                <div class="flex min-w-[760px] items-end gap-2" style="height: 280px;">
                                    <div v-for="row in chartRows" :key="row.label" class="flex h-full flex-1 flex-col items-center justify-end gap-2">
                                        <div class="relative h-[220px] w-full max-w-[18px] rounded-full bg-[#f1ebdf]">
                                            <div
                                                class="absolute inset-x-0 bottom-0 rounded-full bg-rose-500"
                                                :style="{ height: `${(row.noShow / chartMax) * 100}%` }"
                                            ></div>
                                            <div
                                                class="absolute inset-x-0 rounded-full bg-amber-500"
                                                :style="{ height: `${(row.cancelled / chartMax) * 100}%`, bottom: `${(row.noShow / chartMax) * 100}%` }"
                                            ></div>
                                            <div
                                                class="absolute inset-x-0 rounded-full bg-sky-500"
                                                :style="{ height: `${(row.completed / chartMax) * 100}%`, bottom: `${((row.noShow + row.cancelled) / chartMax) * 100}%` }"
                                            ></div>
                                        </div>
                                        <div class="text-[10px] text-slate-500">{{ row.label }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </article>

                    <article class="dashboard-panel rounded-[28px] border border-[#e3dccd] bg-[#fffdf8] p-5 shadow-[0_16px_40px_rgba(15,23,42,0.06)] lg:p-6">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-sm font-medium uppercase tracking-[0.2em] text-slate-500">AI usage</p>
                                <h2 class="mt-2 text-lg font-semibold text-slate-950">This month’s telemetry</h2>
                            </div>
                            <button type="button" class="text-sm font-semibold text-[#0f6e56] transition hover:text-[#0c5f49]" @click="scrollToSection('analytics')">
                                Optimize ↗
                            </button>
                        </div>

                        <div class="mt-4 rounded-2xl border border-[#e3dccd] bg-white px-4 py-3">
                            <div class="flex items-baseline gap-2">
                                <span class="text-2xl font-semibold text-slate-950">
                                    {{ formatCurrency(aiRows.reduce((total, item) => total + Number(item.accumulated_cost ?? 0), 0)) }}
                                </span>
                                <span class="text-sm text-slate-500">total cost</span>
                            </div>
                        </div>

                        <div class="mt-4 space-y-3">
                            <div v-for="item in aiRows" :key="item.feature" class="flex items-start gap-3 rounded-2xl border border-[#e3dccd] bg-white p-4">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#d4edea] text-[#0f6e56]">AI</div>
                                <div class="min-w-0 flex-1">
                                    <div class="truncate text-sm font-semibold text-slate-950">{{ item.label }}</div>
                                    <div class="text-xs text-slate-500">{{ formatNumber(item.total_tokens) }} tokens · {{ formatNumber(item.avg_latency_ms) }} ms avg</div>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm font-semibold text-[#0f6e56]">{{ item.accumulated_cost === 0 ? 'Free' : formatCurrency(item.accumulated_cost) }}</div>
                                    <div class="text-[11px] uppercase tracking-[0.18em] text-slate-500">{{ item.feature }}</div>
                                </div>
                            </div>
                        </div>
                    </article>
                </section>

                <section :ref="setSectionRef('invoices')" class="dashboard-panel dashboard-panel--table mt-5 rounded-[28px] border border-[#e3dccd] bg-[#fffdf8] shadow-[0_16px_40px_rgba(15,23,42,0.06)]">
                    <div class="border-b border-[#e3dccd] px-5 py-4 lg:px-6">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-sm font-medium uppercase tracking-[0.2em] text-slate-500">Recent invoices</p>
                                <h2 class="mt-2 text-xl font-semibold text-slate-950">Billing activity</h2>
                            </div>
                            <button type="button" class="text-sm font-semibold text-[#0f6e56] transition hover:text-[#0c5f49]" @click="scrollToSection('invoices')">
                                View all ↗
                            </button>
                        </div>
                    </div>

                    <div class="dashboard-table-wrap overflow-x-auto">
                        <table class="dashboard-table min-w-full border-separate border-spacing-0 text-left text-sm">
                            <thead>
                                <tr class="text-xs uppercase tracking-[0.18em] text-slate-500">
                                    <th class="px-5 py-4 lg:px-6">Patient</th>
                                    <th class="px-5 py-4 lg:px-6">Doctor</th>
                                    <th class="px-5 py-4 lg:px-6">Date</th>
                                    <th class="px-5 py-4 lg:px-6">Amount</th>
                                    <th class="px-5 py-4 lg:px-6">Method</th>
                                    <th class="px-5 py-4 lg:px-6">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="invoice in recent_invoices" :key="invoice.id" class="border-t border-[#e3dccd]">
                                    <td class="px-5 py-4 font-medium text-slate-950 lg:px-6">{{ invoice.patient_name }}</td>
                                    <td class="px-5 py-4 text-slate-600 lg:px-6">{{ invoice.doctor_name }}</td>
                                    <td class="px-5 py-4 text-slate-500 lg:px-6">{{ formatDate(invoice.date) }}</td>
                                    <td class="px-5 py-4 font-medium text-slate-950 lg:px-6">{{ formatCurrency(invoice.amount) }}</td>
                                    <td class="px-5 py-4 text-slate-600 lg:px-6">{{ invoice.payment_method_label }}</td>
                                    <td class="px-5 py-4 lg:px-6">
                                        <span class="rounded-full px-3 py-1 text-xs font-semibold" :class="statusTone(invoice.status).badge">
                                            {{ invoice.status_label }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </main>
    </div>
</template>

<style scoped>
:global(:root) {
    --dashboard-surface: #fffdf8;
    --dashboard-surface-soft: #fcfaf4;
    --dashboard-surface-muted: #f1ebdf;
    --dashboard-border: #e3dccd;
    --dashboard-border-strong: #d6cdb9;
    --dashboard-accent: #0f6e56;
    --dashboard-accent-strong: #0c5f49;
    --dashboard-shadow: 0 16px 40px rgba(15, 23, 42, 0.06);
    --dashboard-radius: 28px;
}

.dashboard-shell {
    display: flex;
    min-height: 100vh;
}

.dashboard-sidebar {
    position: sticky;
    top: 0;
    height: 100dvh;
}

.dashboard-main {
    min-width: 0;
}

.dashboard-header {
    position: sticky;
    top: 0;
    z-index: 20;
}

.dashboard-content {
    contain: layout style;
}

.dashboard-grid {
    align-items: stretch;
}

.dashboard-panel,
.dashboard-card {
    background: var(--dashboard-surface);
    border-color: var(--dashboard-border);
    border-radius: var(--dashboard-radius);
    box-shadow: var(--dashboard-shadow);
}

.dashboard-panel {
    min-width: 0;
}

.dashboard-panel--table {
    overflow: hidden;
}

.dashboard-panel--hero {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.dashboard-panel__body {
    min-width: 0;
}

.dashboard-tabs {
    overflow-x: auto;
    scrollbar-width: thin;
}

.dashboard-table-wrap {
    scrollbar-width: thin;
}

.dashboard-table {
    min-width: 760px;
}

.dashboard-card {
    transition:
        transform 180ms ease,
        box-shadow 180ms ease,
        border-color 180ms ease;
}

.dashboard-card:hover {
    transform: translateY(-2px);
    border-color: var(--dashboard-border-strong);
    box-shadow: 0 18px 46px rgba(15, 23, 42, 0.09);
}

.dashboard-sidebar__header,
.dashboard-sidebar__footer,
.dashboard-header {
    background: color-mix(in srgb, var(--dashboard-surface) 94%, white);
}

@media (max-width: 1023px) {
    .dashboard-shell {
        flex-direction: column;
    }

    .dashboard-content {
        padding-inline: 1rem;
        padding-top: 1rem;
    }

    .dashboard-header {
        border-radius: 0 0 1.5rem 1.5rem;
    }
}

@media (max-width: 639px) {
    .dashboard-content {
        padding-inline: 0.75rem;
        padding-bottom: 0.75rem;
    }

    .dashboard-panel,
    .dashboard-card {
        border-radius: 22px;
    }
}
</style>
