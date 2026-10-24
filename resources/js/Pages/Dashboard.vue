<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import Card from '@/Components/ui/Card.vue';
import CardHeader from '@/Components/ui/CardHeader.vue';
import CardTitle from '@/Components/ui/CardTitle.vue';
import CardContent from '@/Components/ui/CardContent.vue';
import Badge from '@/Components/ui/Badge.vue';
import Button from '@/Components/ui/Button.vue';
import {
    Users,
    UserCheck,
    Clock,
    UserX,
    Radio,
    FileSpreadsheet,
    ArrowUpRight,
    Sparkles,
    Calendar,
    ChevronRight,
    CheckCircle2,
} from 'lucide-vue-next';

const props = defineProps({
    stats: Object,
    class_stats: Array,
    recent_scans: Array,
    settings: Object,
    today_formatted: String,
});
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h2 class="text-xl font-bold leading-tight text-slate-800 dark:text-slate-100 flex items-center space-x-2">
                    <span>Dashboard Presensi</span>
                </h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 flex items-center space-x-1.5">
                    <Calendar class="w-3.5 h-3.5" />
                    <span>{{ today_formatted }}</span>
                    <span>•</span>
                    <span class="font-medium text-indigo-600 dark:text-indigo-400">{{ settings.nama_sekolah }}</span>
                </p>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Welcome Hero & Quick Action Kiosk Banner -->
            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 text-white p-6 sm:p-8 shadow-lg border border-indigo-950">
                <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                    <div class="max-w-xl">
                        <div class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-indigo-500/20 border border-indigo-400/30 text-indigo-300 text-xs font-medium mb-3">
                            <Sparkles class="w-3.5 h-3.5" />
                            <span>Presensi Real-time Berbasis RFID</span>
                        </div>
                        <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-white leading-snug">
                            Sistem Absensi Siswa Mandiri
                        </h1>
                        <p class="mt-2 text-sm text-slate-300 leading-relaxed">
                            Buka mode terminal untuk pos gerbang sekolah atau resepsionis. Tempelkan kartu RFID siswa untuk absensi otomatis tanpa repot.
                        </p>
                    </div>

                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                        <a
                            :href="route('attendance.scan')"
                            target="_blank"
                            class="inline-flex items-center justify-center px-5 py-3 rounded-xl bg-emerald-500 text-slate-950 font-bold text-sm hover:bg-emerald-400 transition-all shadow-lg shadow-emerald-500/25 group cursor-pointer"
                        >
                            <Radio class="w-4 h-4 mr-2 animate-pulse text-slate-950" />
                            <span>Buka Terminal Scan</span>
                            <ArrowUpRight class="w-4 h-4 ml-1.5 transition-transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5" />
                        </a>

                        <Link
                            :href="route('reports.index')"
                            class="inline-flex items-center justify-center px-4 py-3 rounded-xl bg-slate-800/80 text-white font-medium text-sm hover:bg-slate-700 transition border border-slate-700"
                        >
                            <FileSpreadsheet class="w-4 h-4 mr-2 text-indigo-400" />
                            <span>Lihat Laporan</span>
                        </Link>
                    </div>
                </div>

                <!-- Subtle Decorative Shapes -->
                <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>
                <div class="absolute -left-10 -top-10 w-48 h-48 bg-emerald-500/10 rounded-full blur-2xl pointer-events-none"></div>
            </div>

            <!-- 4 Key Metrics Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Total Students -->
                <Card class="hover:border-slate-300 dark:hover:border-slate-700 transition-all shadow-sm">
                    <CardContent class="p-5">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Siswa</span>
                            <div class="w-9 h-9 rounded-lg bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400 flex items-center justify-center">
                                <Users class="w-5 h-5" />
                            </div>
                        </div>
                        <div class="mt-3 flex items-baseline justify-between">
                            <span class="text-3xl font-extrabold text-slate-900 dark:text-white">{{ stats.total_students }}</span>
                            <span class="text-xs text-slate-500">
                                <strong class="text-indigo-600 dark:text-indigo-400">{{ stats.total_rfid_assigned }}</strong> punya RFID
                            </span>
                        </div>
                        <div class="mt-3 w-full bg-slate-100 dark:bg-slate-800 h-1.5 rounded-full overflow-hidden">
                            <div
                                class="bg-indigo-600 h-full rounded-full"
                                :style="{ width: `${stats.total_students ? (stats.total_rfid_assigned / stats.total_students) * 100 : 0}%` }"
                            ></div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Present Today -->
                <Card class="hover:border-emerald-300 dark:hover:border-emerald-800 transition-all shadow-sm">
                    <CardContent class="p-5">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Hadir Hari Ini</span>
                            <div class="w-9 h-9 rounded-lg bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                                <UserCheck class="w-5 h-5" />
                            </div>
                        </div>
                        <div class="mt-3 flex items-baseline justify-between">
                            <span class="text-3xl font-extrabold text-emerald-600 dark:text-emerald-400">{{ stats.today_present }}</span>
                            <Badge variant="emerald">{{ stats.attendance_percentage }}%</Badge>
                        </div>
                        <p class="mt-3 text-xs text-slate-500">
                            <span class="text-emerald-600 font-semibold">{{ stats.today_on_time }}</span> tepat waktu
                        </p>
                    </CardContent>
                </Card>

                <!-- Late Today -->
                <Card class="hover:border-amber-300 dark:hover:border-amber-800 transition-all shadow-sm">
                    <CardContent class="p-5">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Terlambat Hari Ini</span>
                            <div class="w-9 h-9 rounded-lg bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 flex items-center justify-center">
                                <Clock class="w-5 h-5" />
                            </div>
                        </div>
                        <div class="mt-3 flex items-baseline justify-between">
                            <span class="text-3xl font-extrabold text-amber-600 dark:text-amber-400">{{ stats.today_late }}</span>
                            <span class="text-xs text-slate-500">Batas: {{ settings.jam_masuk_batas?.substring(0, 5) }}</span>
                        </div>
                        <p class="mt-3 text-xs text-slate-500">
                            Lewat dari batas toleransi masuk
                        </p>
                    </CardContent>
                </Card>

                <!-- Absent / Unscanned Today -->
                <Card class="hover:border-rose-300 dark:hover:border-rose-800 transition-all shadow-sm">
                    <CardContent class="p-5">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Belum Tap / Absen</span>
                            <div class="w-9 h-9 rounded-lg bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 flex items-center justify-center">
                                <UserX class="w-5 h-5" />
                            </div>
                        </div>
                        <div class="mt-3 flex items-baseline justify-between">
                            <span class="text-3xl font-extrabold text-rose-600 dark:text-rose-400">{{ stats.today_absent }}</span>
                            <span class="text-xs text-slate-500">Siswa aktif</span>
                        </div>
                        <p class="mt-3 text-xs text-slate-500">
                            Belum terdeteksi di gerbang
                        </p>
                    </CardContent>
                </Card>
            </div>

            <!-- Two Columns: Class Breakdown & Live Recent Scans -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Class Attendance Breakdown (2 Cols) -->
                <div class="lg:col-span-2 space-y-4">
                    <Card>
                        <CardHeader class="flex flex-row items-center justify-between pb-3">
                            <div>
                                <CardTitle class="text-base">Kehadiran per Kelas Hari Ini</CardTitle>
                                <p class="text-xs text-slate-500 mt-0.5">Persentase siswa hadir di masing-masing kelas</p>
                            </div>
                            <Link :href="route('students.index')" class="text-xs text-indigo-600 hover:text-indigo-700 font-medium flex items-center">
                                <span>Kelola Siswa</span>
                                <ChevronRight class="w-3.5 h-3.5 ml-0.5" />
                            </Link>
                        </CardHeader>
                        <CardContent>
                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                                <div
                                    v-for="item in class_stats"
                                    :key="item.kelas"
                                    class="p-4 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 flex flex-col justify-between"
                                >
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="font-bold text-sm text-slate-800 dark:text-slate-200">Kelas {{ item.kelas }}</span>
                                        <Badge :variant="item.persentase >= 80 ? 'emerald' : item.persentase >= 50 ? 'amber' : 'outline'">
                                            {{ item.persentase }}%
                                        </Badge>
                                    </div>
                                    <div class="flex items-baseline justify-between text-xs text-slate-500 mb-2">
                                        <span>Hadir: <strong class="text-slate-900 dark:text-white">{{ item.hadir }}</strong></span>
                                        <span>Total: {{ item.total }} siswa</span>
                                    </div>
                                    <div class="w-full bg-slate-200 dark:bg-slate-700 h-2 rounded-full overflow-hidden">
                                        <div
                                            class="bg-indigo-600 h-full rounded-full transition-all duration-300"
                                            :style="{ width: `${item.persentase}%` }"
                                        ></div>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Recent Live Scans Feed (1 Col) -->
                <div class="lg:col-span-1">
                    <Card class="h-full flex flex-col">
                        <CardHeader class="flex flex-row items-center justify-between pb-3">
                            <div>
                                <CardTitle class="text-base flex items-center space-x-2">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span>
                                    <span>Aktivitas Tap Terkini</span>
                                </CardTitle>
                                <p class="text-xs text-slate-500 mt-0.5">Catatan presensi hari ini</p>
                            </div>
                            <Badge variant="outline">{{ recent_scans.length }} Tap</Badge>
                        </CardHeader>
                        <CardContent class="flex-1 overflow-y-auto max-h-[380px] space-y-3">
                            <div v-if="recent_scans.length === 0" class="text-center py-8 text-slate-400 text-xs">
                                <Radio class="w-8 h-8 mx-auto text-slate-300 dark:text-slate-700 mb-2" />
                                <p>Belum ada aktivitas tap kartu hari ini.</p>
                                <a :href="route('attendance.scan')" target="_blank" class="mt-2 inline-block text-indigo-600 text-xs font-semibold hover:underline">
                                    Buka Scanner untuk Mengetes
                                </a>
                            </div>

                            <div
                                v-for="scan in recent_scans"
                                :key="scan.id"
                                class="p-3 rounded-lg border border-slate-100 dark:border-slate-800 bg-white dark:bg-slate-800/60 shadow-xs flex items-center justify-between"
                            >
                                <div class="flex items-center space-x-3 min-w-0">
                                    <div class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-700 text-slate-800 dark:text-slate-200 flex items-center justify-center font-bold text-xs flex-shrink-0">
                                        {{ scan.student_name.charAt(0) }}
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-xs font-semibold text-slate-900 dark:text-white truncate">
                                            {{ scan.student_name }}
                                        </p>
                                        <p class="text-[11px] text-slate-500 truncate">
                                            Kelas {{ scan.student_class }} • {{ scan.waktu }} WIB
                                        </p>
                                    </div>
                                </div>

                                <div class="text-right flex-shrink-0 ml-2">
                                    <Badge :variant="scan.category_color">
                                        {{ scan.category_name }}
                                    </Badge>
                                    <p v-if="scan.menit_terlambat > 0" class="text-[10px] text-amber-600 font-medium mt-0.5">
                                        +{{ scan.menit_terlambat }}m
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
