<script setup>
import { ref, watch } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import Card from '@/Components/ui/Card.vue';
import CardContent from '@/Components/ui/CardContent.vue';
import Badge from '@/Components/ui/Badge.vue';
import Button from '@/Components/ui/Button.vue';
import Input from '@/Components/ui/Input.vue';
import {
    FileSpreadsheet,
    Download,
    Printer,
    Search,
    Filter,
    Calendar,
    CheckCircle,
    Clock,
    UserCheck,
    AlertCircle,
    Table as TableIcon,
    CalendarDays,
} from 'lucide-vue-next';

const props = defineProps({
    logs: Object,
    summary: Object,
    filters: Object,
    classes: Array,
    categories: Array,
    matrix: Object,
    settings: Object,
});

const activeTab = ref('logs'); // 'logs' or 'matrix'

const dateFrom = ref(props.filters.date_from);
const dateTo = ref(props.filters.date_to);
const selectedClass = ref(props.filters.kelas || 'Semua');
const selectedCategory = ref(props.filters.category_id || 'all');
const search = ref(props.filters.search || '');

// Matrix filters
const selectedMonth = ref(props.filters.month || new Date().getMonth() + 1);
const selectedYear = ref(props.filters.year || new Date().getFullYear());

const applyFilters = () => {
    router.get(
        route('reports.index'),
        {
            date_from: dateFrom.value,
            date_to: dateTo.value,
            kelas: selectedClass.value,
            category_id: selectedCategory.value,
            search: search.value || undefined,
            month: selectedMonth.value,
            year: selectedYear.value,
        },
        { preserveState: true, replace: true }
    );
};

let searchTimer = null;
watch(search, () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(applyFilters, 400);
});

const exportUrl = () => {
    const params = new URLSearchParams({
        date_from: dateFrom.value,
        date_to: dateTo.value,
        kelas: selectedClass.value,
        category_id: selectedCategory.value,
    });
    return `${route('reports.export')}?${params.toString()}`;
};

const printReport = () => {
    window.print();
};
</script>

<template>
    <Head title="Laporan Absensi Siswa" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="text-xl font-bold leading-tight text-slate-800 dark:text-slate-100 flex items-center space-x-2">
                        <FileSpreadsheet class="w-5 h-5 text-indigo-600" />
                        <span>Laporan & Rekapitulasi Presensi</span>
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">Pantau riwayat kehadiran, rekap bulanan, dan ekspor data</p>
                </div>

                <div class="flex items-center space-x-2 print:hidden">
                    <button
                        @click="printReport"
                        class="inline-flex items-center h-8 rounded-md px-3 text-xs border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 transition cursor-pointer"
                    >
                        <Printer class="w-4 h-4 mr-1.5 text-slate-500" />
                        <span>Cetak / Print</span>
                    </button>
                    <a
                        :href="exportUrl()"
                        target="_blank"
                        class="inline-flex items-center h-8 rounded-md px-3 text-xs bg-emerald-600 hover:bg-emerald-700 text-white font-bold transition shadow-sm cursor-pointer"
                    >
                        <Download class="w-4 h-4 mr-1.5" />
                        <span>Export Excel (.xlsx)</span>
                    </a>
                </div>
            </div>
        </template>

        <div class="space-y-4">
            <!-- Filter & Range Bar (Hidden on print) -->
            <Card class="print:hidden">
                <CardContent class="p-4 space-y-3">
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1">
                                Dari Tanggal
                            </label>
                            <Input type="date" v-model="dateFrom" @change="applyFilters" class="text-sm font-mono" />
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1">
                                Sampai Tanggal
                            </label>
                            <Input type="date" v-model="dateTo" @change="applyFilters" class="text-sm font-mono" />
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1">
                                Filter Kelas
                            </label>
                            <select
                                v-model="selectedClass"
                                @change="applyFilters"
                                class="w-full h-10 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 px-3 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                            >
                                <option value="Semua">Semua Kelas</option>
                                <option v-for="c in classes" :key="c" :value="c">Kelas {{ c }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1">
                                Filter Status / Kategori
                            </label>
                            <select
                                v-model="selectedCategory"
                                @change="applyFilters"
                                class="w-full h-10 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 px-3 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                            >
                                <option value="all">Semua Kategori</option>
                                <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                                    {{ cat.nama }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="relative max-w-md pt-1">
                        <Search class="w-4 h-4 text-slate-400 absolute left-3 top-3.5" />
                        <Input
                            v-model="search"
                            placeholder="Cari nama siswa atau NISN..."
                            class="pl-9 text-sm"
                        />
                    </div>
                </CardContent>
            </Card>

            <!-- Summary Stat Cards for Filtered Range (Hidden on print) -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 print:hidden">
                <div class="p-3.5 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 block">Total Log</span>
                    <span class="text-2xl font-black text-slate-900 dark:text-white">{{ summary.total_logs }}</span>
                </div>
                <div class="p-3.5 rounded-xl bg-emerald-50/60 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 shadow-xs">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-400 block">Tepat Waktu</span>
                    <span class="text-2xl font-black text-emerald-600 dark:text-emerald-400">{{ summary.total_on_time }}</span>
                </div>
                <div class="p-3.5 rounded-xl bg-amber-50/60 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 shadow-xs">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-amber-700 dark:text-amber-400 block">Terlambat</span>
                    <span class="text-2xl font-black text-amber-600 dark:text-amber-400">{{ summary.total_late }}</span>
                </div>
                <div class="p-3.5 rounded-xl bg-purple-50/60 dark:bg-purple-950/40 border border-purple-200 dark:border-purple-800 shadow-xs">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-purple-700 dark:text-purple-400 block">Sakit & Izin</span>
                    <span class="text-2xl font-black text-purple-600 dark:text-purple-400">{{ summary.total_sick + summary.total_permission }}</span>
                </div>
            </div>

            <!-- Tab Switcher (Logs vs Monthly Matrix) -->
            <div class="flex items-center space-x-2 border-b border-slate-200 dark:border-slate-800 print:hidden">
                <button
                    @click="activeTab = 'logs'"
                    :class="[
                        activeTab === 'logs'
                            ? 'border-indigo-600 text-indigo-600 font-bold'
                            : 'border-transparent text-slate-500 hover:text-slate-700',
                        'px-4 py-2.5 border-b-2 text-sm flex items-center space-x-2 transition cursor-pointer',
                    ]"
                >
                    <TableIcon class="w-4 h-4" />
                    <span>Riwayat Log Detail</span>
                </button>
                <button
                    @click="activeTab = 'matrix'"
                    :class="[
                        activeTab === 'matrix'
                            ? 'border-indigo-600 text-indigo-600 font-bold'
                            : 'border-transparent text-slate-500 hover:text-slate-700',
                        'px-4 py-2.5 border-b-2 text-sm flex items-center space-x-2 transition cursor-pointer',
                    ]"
                >
                    <CalendarDays class="w-4 h-4" />
                    <span>Rekap Matriks Bulanan Siswa</span>
                </button>
            </div>

            <!-- TAB 1: Detailed Logs View -->
            <div v-if="activeTab === 'logs'">
                <Card>
                    <div class="p-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                        <div>
                            <h3 class="font-bold text-sm text-slate-900 dark:text-white">
                                Riwayat Presensi Siswa ({{ logs.total }} catatan)
                            </h3>
                            <p class="text-xs text-slate-500">
                                Periode: {{ dateFrom }} s/d {{ dateTo }} • Kelas: {{ selectedClass }}
                            </p>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs text-slate-600 dark:text-slate-300">
                            <thead class="bg-slate-100/75 dark:bg-slate-800/60 uppercase font-semibold text-slate-700 dark:text-slate-200 border-b border-slate-200 dark:border-slate-800">
                                <tr>
                                    <th class="p-3 w-12 text-center">No</th>
                                    <th class="p-3">Tanggal</th>
                                    <th class="p-3">Jam Tap</th>
                                    <th class="p-3">NISN</th>
                                    <th class="p-3">Nama Siswa</th>
                                    <th class="p-3">Kelas</th>
                                    <th class="p-3">Sesi</th>
                                    <th class="p-3">Status / Kategori</th>
                                    <th class="p-3">Keterlambatan</th>
                                    <th class="p-3">Metode</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                <tr v-if="logs.data.length === 0">
                                    <td colspan="10" class="p-8 text-center text-slate-400">
                                        Tidak ada catatan kehadiran pada filter tanggal dan kelas ini.
                                    </td>
                                </tr>
                                <tr
                                    v-for="(log, idx) in logs.data"
                                    :key="log.id"
                                    class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors"
                                >
                                    <td class="p-3 text-center font-mono text-slate-400">
                                        {{ (logs.current_page - 1) * logs.per_page + idx + 1 }}
                                    </td>
                                    <td class="p-3 font-mono font-medium text-slate-900 dark:text-white">
                                        {{ log.tanggal }}
                                    </td>
                                    <td class="p-3 font-mono text-indigo-600 dark:text-indigo-400 font-bold">
                                        {{ log.waktu.substring(0, 5) }} WIB
                                    </td>
                                    <td class="p-3 font-mono text-slate-500">
                                        {{ log.student?.nisn ?? '-' }}
                                    </td>
                                    <td class="p-3 font-semibold text-slate-900 dark:text-white">
                                        {{ log.student?.nama ?? 'Siswa Terhapus' }}
                                    </td>
                                    <td class="p-3">
                                        <Badge variant="secondary">Kelas {{ log.student?.kelas ?? '-' }}</Badge>
                                    </td>
                                    <td class="p-3 capitalize">
                                        <Badge variant="outline">{{ log.tipe }}</Badge>
                                    </td>
                                    <td class="p-3">
                                        <Badge :variant="log.category?.warna ?? 'emerald'">
                                            {{ log.category?.nama ?? 'Hadir' }}
                                        </Badge>
                                    </td>
                                    <td class="p-3">
                                        <span v-if="log.menit_terlambat > 0" class="font-mono text-amber-600 font-bold">
                                            Terlambat {{ log.menit_terlambat }} m
                                        </span>
                                        <span v-else class="text-emerald-600 font-medium text-[11px]">
                                            Tepat Waktu
                                        </span>
                                    </td>
                                    <td class="p-3 font-mono uppercase text-[10px] text-slate-400">
                                        {{ log.metode }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="logs.links && logs.links.length > 3" class="p-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs text-slate-500 print:hidden">
                        <div>
                            Menampilkan {{ logs.from ?? 0 }} - {{ logs.to ?? 0 }} dari {{ logs.total }} catatan
                        </div>
                        <div class="flex items-center space-x-1">
                            <Link
                                v-for="link in logs.links"
                                :key="link.label"
                                :href="link.url || '#'"
                                :class="[
                                    link.active ? 'bg-indigo-600 text-white font-bold' : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800',
                                    !link.url ? 'opacity-40 pointer-events-none' : '',
                                    'px-2.5 py-1 rounded-md text-xs transition',
                                ]"
                                v-html="link.label"
                            />
                        </div>
                    </div>
                </Card>
            </div>

            <!-- TAB 2: Monthly Matrix View -->
            <div v-if="activeTab === 'matrix'">
                <Card>
                    <div class="p-4 border-b border-slate-100 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div>
                            <h3 class="font-bold text-sm text-slate-900 dark:text-white">
                                Rekap Matriks Kehadiran Siswa Kelas {{ matrix.class }}
                            </h3>
                            <p class="text-xs text-slate-500">
                                Bulan: <strong>{{ matrix.month_name }}</strong> • Kode: [H] Hadir, [T] Terlambat, [S] Sakit, [I] Izin
                            </p>
                        </div>

                        <!-- Month & Class Selector for Matrix -->
                        <div class="flex items-center space-x-2 print:hidden">
                            <select
                                v-model="selectedClass"
                                @change="applyFilters"
                                class="h-9 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 px-3 text-xs focus:border-indigo-500 focus:outline-none"
                            >
                                <option v-for="c in classes" :key="c" :value="c">Kelas {{ c }}</option>
                            </select>

                            <select
                                v-model="selectedMonth"
                                @change="applyFilters"
                                class="h-9 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 px-3 text-xs focus:border-indigo-500 focus:outline-none"
                            >
                                <option :value="1">Januari</option>
                                <option :value="2">Februari</option>
                                <option :value="3">Maret</option>
                                <option :value="4">April</option>
                                <option :value="5">Mei</option>
                                <option :value="6">Juni</option>
                                <option :value="7">Juli</option>
                                <option :value="8">Agustus</option>
                                <option :value="9">September</option>
                                <option :value="10">Oktober</option>
                                <option :value="11">November</option>
                                <option :value="12">Desember</option>
                            </select>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-center text-[11px] text-slate-600 dark:text-slate-300 border-collapse">
                            <thead class="bg-slate-100 dark:bg-slate-800 font-semibold text-slate-700 dark:text-slate-200">
                                <tr>
                                    <th class="p-2 border border-slate-200 dark:border-slate-700 w-10 text-center">No</th>
                                    <th class="p-2 border border-slate-200 dark:border-slate-700 text-left min-w-[150px]">Nama Siswa</th>
                                    <th
                                        v-for="d in matrix.days_count"
                                        :key="d"
                                        class="p-1 border border-slate-200 dark:border-slate-700 w-6 font-mono"
                                    >
                                        {{ d }}
                                    </th>
                                    <th class="p-1 border border-slate-200 dark:border-slate-700 bg-emerald-100/60 dark:bg-emerald-950 font-bold text-emerald-800 dark:text-emerald-300 w-8">H</th>
                                    <th class="p-1 border border-slate-200 dark:border-slate-700 bg-amber-100/60 dark:bg-amber-950 font-bold text-amber-800 dark:text-amber-300 w-8">T</th>
                                    <th class="p-1 border border-slate-200 dark:border-slate-700 bg-purple-100/60 dark:bg-purple-950 font-bold text-purple-800 dark:text-purple-300 w-8">S</th>
                                    <th class="p-1 border border-slate-200 dark:border-slate-700 bg-indigo-100/60 dark:bg-indigo-950 font-bold text-indigo-800 dark:text-indigo-300 w-8">I</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="(st, idx) in matrix.students"
                                    :key="st.id"
                                    class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40"
                                >
                                    <td class="p-1.5 border border-slate-200 dark:border-slate-700 font-mono text-slate-400">
                                        {{ idx + 1 }}
                                    </td>
                                    <td class="p-1.5 border border-slate-200 dark:border-slate-700 text-left font-medium text-slate-900 dark:text-white truncate max-w-[160px]">
                                        {{ st.nama }}
                                    </td>
                                    <td
                                        v-for="d in matrix.days_count"
                                        :key="d"
                                        :class="[
                                            st.daily[d]?.code === 'H' ? 'bg-emerald-50 text-emerald-700 font-bold' :
                                            st.daily[d]?.code === 'T' ? 'bg-amber-50 text-amber-700 font-bold' :
                                            st.daily[d]?.code === 'S' ? 'bg-purple-50 text-purple-700 font-bold' :
                                            st.daily[d]?.code === 'I' ? 'bg-indigo-50 text-indigo-700 font-bold' :
                                            st.daily[d]?.code === '-' ? 'bg-slate-100/50 text-slate-300' : '',
                                            'p-1 border border-slate-200 dark:border-slate-700 font-mono text-[10px]',
                                        ]"
                                        :title="st.daily[d]?.category ? `${st.daily[d].category} (${st.daily[d].waktu || ''})` : ''"
                                    >
                                        {{ st.daily[d]?.code || '' }}
                                    </td>
                                    <td class="p-1 border border-slate-200 dark:border-slate-700 font-bold text-emerald-700 dark:text-emerald-400">
                                        {{ st.totals.hadir }}
                                    </td>
                                    <td class="p-1 border border-slate-200 dark:border-slate-700 font-bold text-amber-700 dark:text-amber-400">
                                        {{ st.totals.terlambat }}
                                    </td>
                                    <td class="p-1 border border-slate-200 dark:border-slate-700 font-bold text-purple-700 dark:text-purple-400">
                                        {{ st.totals.sakit }}
                                    </td>
                                    <td class="p-1 border border-slate-200 dark:border-slate-700 font-bold text-indigo-700 dark:text-indigo-400">
                                        {{ st.totals.izin }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </Card>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style>
@media print {
    body {
        background: white !important;
        color: black !important;
    }
    aside, header, .print\:hidden {
        display: none !important;
    }
    main {
        padding: 0 !important;
    }
}
</style>

