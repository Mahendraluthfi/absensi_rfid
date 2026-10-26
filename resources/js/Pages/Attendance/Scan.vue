<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import axios from 'axios';
import { sounds } from '@/lib/sound';
import confetti from 'canvas-confetti';
import Badge from '@/Components/ui/Badge.vue';
import Button from '@/Components/ui/Button.vue';
import Input from '@/Components/ui/Input.vue';
import Modal from '@/Components/Modal.vue';
import {
    Radio,
    Clock,
    UserCheck,
    AlertCircle,
    Maximize2,
    Minimize2,
    Volume2,
    VolumeX,
    ArrowLeft,
    CheckCircle2,
    XCircle,
    User,
    Sparkles,
    UserPlus,
    X,
    Layers,
    ChevronDown,
} from 'lucide-vue-next';

const props = defineProps({
    settings: Object,
    categories: Array,
    active_category: Object,
    today_formatted: String,
    current_time: String,
    recent_scans: Array,
    students_with_rfid: Array,
    all_students: Array,
    stats: Object,
});

// Sound toggle
const soundEnabled = ref(true);

// Fullscreen toggle
const isFullscreen = ref(false);
const toggleFullscreen = () => {
    if (!document.fullscreenElement) {
        document.documentElement.requestFullscreen().then(() => {
            isFullscreen.value = true;
        }).catch(() => {});
    } else {
        document.exitFullscreen().then(() => {
            isFullscreen.value = false;
        }).catch(() => {});
    }
};

// Live Clock
const currentTime = ref('');
const currentDate = ref('');
const updateClock = () => {
    const now = new Date();
    currentTime.value = now.toLocaleTimeString('id-ID', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: false,
    });
    currentDate.value = now.toLocaleDateString('id-ID', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
};

// Selected Session: 'auto' or Category ID
const selectedCategoryId = ref('auto');

// Currently resolved session label
const currentActiveSessionLabel = computed(() => {
    if (selectedCategoryId.value !== 'auto') {
        const cat = props.categories.find((c) => c.id === selectedCategoryId.value);
        return cat ? cat.nama : 'Sesi Dipilih';
    }
    return props.active_category ? `${props.active_category.nama} (Otomatis)` : 'Sesi Otomatis';
});

// Scanner State
const rfidInputRef = ref(null);
const currentScanInput = ref('');
const isScanning = ref(false);
const scanResult = ref(null); // { status: 'success' | 'duplicate' | 'unregistered' | 'error', student, category, message, time, late }
const recentScansList = ref([...props.recent_scans]);
const localStats = ref({ ...props.stats });

// Hidden input auto-focus
const focusScanner = () => {
    if (rfidInputRef.value) {
        rfidInputRef.value.focus();
    }
};

// Global Keystroke Buffer for USB RFID Readers (Keyboard Emulation)
let buffer = '';
let lastKeyTime = Date.now();

const handleGlobalKeyDown = (e) => {
    if (e.target.tagName === 'INPUT' && e.target !== rfidInputRef.value) {
        return;
    }

    const now = Date.now();
    if (now - lastKeyTime > 300) {
        buffer = '';
    }
    lastKeyTime = now;

    if (e.key === 'Enter') {
        const code = (buffer || currentScanInput.value).trim();
        if (code) {
            processScan(code);
            buffer = '';
            currentScanInput.value = '';
        }
    } else if (e.key.length === 1) {
        buffer += e.key;
    }
};

// Process Scan
let isLocked = false;
const processScan = async (uid) => {
    if (isLocked || !uid) return;
    isLocked = true;
    isScanning.value = true;

    try {
        const res = await axios.post(route('attendance.scan.post'), {
            rfid_uid: uid,
            category_id: selectedCategoryId.value,
        });

        const data = res.data;
        if (data.success) {
            scanResult.value = {
                status: 'success',
                student: data.student,
                category: data.category,
                message: data.message,
                time: data.waktu_formatted,
                late: data.menit_terlambat,
                is_late: data.is_late,
            };

            // Audio Cue
            if (soundEnabled.value) {
                if (data.category.tipe === 'pulang') {
                    sounds.playCheckout();
                } else if (data.is_late) {
                    sounds.playWarning();
                } else {
                    sounds.playSuccess();
                }
            }

            // Confetti on on-time arrival
            if (!data.is_late) {
                confetti({
                    particleCount: 45,
                    spread: 60,
                    origin: { y: 0.6 },
                });
            }

            // Prepend to recent list
            recentScansList.value.unshift(data.log);
            if (recentScansList.value.length > 20) {
                recentScansList.value.pop();
            }

            localStats.value.total_tap_today++;
        }
    } catch (err) {
        const res = err.response?.data;
        if (res && res.status === 'duplicate') {
            scanResult.value = {
                status: 'duplicate',
                student: res.student,
                category: res.category,
                message: res.message,
                time: res.log?.waktu?.substring(0, 5) || '--:--',
            };
            if (soundEnabled.value) sounds.playWarning();
        } else if (res && res.status === 'unregistered') {
            scanResult.value = {
                status: 'unregistered',
                uid: res.rfid_uid || uid,
                message: res.message,
            };
            if (soundEnabled.value) sounds.playError();
        } else {
            scanResult.value = {
                status: 'error',
                message: res?.message || 'Terjadi kesalahan sistem saat memproses kartu RFID.',
            };
            if (soundEnabled.value) sounds.playError();
        }
    } finally {
        isScanning.value = false;
        currentScanInput.value = '';
        setTimeout(() => {
            isLocked = false;
            focusScanner();
        }, 500);
    }
};

// Simulation Bar
const testTapCard = (uid) => {
    processScan(uid);
};

// Quick Register Unregistered Card Modal
const showRegisterCardModal = ref(false);
const unregisteredCardUid = ref('');
const selectedStudentId = ref('');
const studentSearchQuery = ref('');
const registerLoading = ref(false);
const registerError = ref('');

const openRegisterModal = (uid) => {
    unregisteredCardUid.value = uid;
    selectedStudentId.value = '';
    studentSearchQuery.value = '';
    registerError.value = '';
    showRegisterCardModal.value = true;
};

const filteredStudentsToRegister = computed(() => {
    const q = studentSearchQuery.value.toLowerCase().trim();
    return props.all_students.filter((s) => {
        if (!q) return true;
        return s.nama.toLowerCase().includes(q) || s.nisn.includes(q) || s.kelas.toLowerCase().includes(q);
    }).slice(0, 8);
});

const submitRegisterCard = async () => {
    if (!selectedStudentId.value) {
        registerError.value = 'Silakan pilih siswa yang akan dipasangkan kartu ini!';
        return;
    }

    registerLoading.value = true;
    registerError.value = '';

    try {
        const res = await axios.post(route('students.pair-rfid'), {
            student_id: selectedStudentId.value,
            rfid_uid: unregisteredCardUid.value,
        });

        if (res.data.success) {
            showRegisterCardModal.value = false;
            processScan(unregisteredCardUid.value);
        }
    } catch (err) {
        registerError.value = err.response?.data?.message || 'Gagal mendaftarkan kartu.';
    } finally {
        registerLoading.value = false;
    }
};

let timer = null;
onMounted(() => {
    updateClock();
    timer = setInterval(updateClock, 1000);
    window.addEventListener('keydown', handleGlobalKeyDown);
    window.addEventListener('click', focusScanner);
    focusScanner();
});

onUnmounted(() => {
    if (timer) clearInterval(timer);
    window.removeEventListener('keydown', handleGlobalKeyDown);
    window.removeEventListener('click', focusScanner);
});
</script>

<template>
    <Head title="Terminal Scan RFID - Presensi Siswa" />

    <div class="min-h-screen bg-slate-950 text-slate-100 flex flex-col font-sans select-none overflow-x-hidden">
        <!-- Top Kiosk Header -->
        <header class="h-16 px-4 sm:px-6 bg-slate-900/90 border-b border-slate-800 backdrop-blur-md flex items-center justify-between sticky top-0 z-30">
            <!-- School Brand -->
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-indigo-600 to-indigo-400 flex items-center justify-center text-white shadow-lg shadow-indigo-600/30">
                    <Radio class="w-5 h-5 animate-pulse text-white" />
                </div>
                <div>
                    <h1 class="font-extrabold text-base tracking-wide text-white leading-tight">
                        {{ settings.nama_sekolah }}
                    </h1>
                    <p class="text-xs text-indigo-400 font-semibold tracking-wider uppercase flex items-center space-x-1.5">
                        <span>Terminal RFID</span>
                        <span>•</span>
                        <span class="text-emerald-400">{{ currentActiveSessionLabel }}</span>
                    </p>
                </div>
            </div>

            <!-- Dynamic Session Selector (Pills) -->
            <div class="hidden md:flex items-center bg-slate-950 p-1 rounded-xl border border-slate-800 max-w-xl overflow-x-auto">
                <button
                    @click="selectedCategoryId = 'auto'"
                    :class="[
                        selectedCategoryId === 'auto' ? 'bg-indigo-600 text-white font-bold shadow' : 'text-slate-400 hover:text-white',
                        'px-3 py-1.5 rounded-lg text-xs transition cursor-pointer flex-shrink-0 flex items-center space-x-1',
                    ]"
                >
                    <Sparkles class="w-3 h-3" />
                    <span>Mode Otomatis</span>
                </button>

                <button
                    v-for="cat in categories"
                    :key="cat.id"
                    @click="selectedCategoryId = cat.id"
                    :class="[
                        selectedCategoryId === cat.id ? 'bg-indigo-600 text-white font-bold shadow' : 'text-slate-400 hover:text-white',
                        'px-2.5 py-1.5 rounded-lg text-xs transition cursor-pointer flex-shrink-0',
                    ]"
                >
                    {{ cat.nama }}
                </button>
            </div>

            <!-- Header Action Controls -->
            <div class="flex items-center space-x-2">
                <!-- Sound Toggle -->
                <button
                    @click="soundEnabled = !soundEnabled"
                    class="p-2 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 transition cursor-pointer"
                    :title="soundEnabled ? 'Matikan Suara Chime' : 'Nyalakan Suara Chime'"
                >
                    <Volume2 v-if="soundEnabled" class="w-4 h-4 text-emerald-400" />
                    <VolumeX v-else class="w-4 h-4 text-slate-500" />
                </button>

                <!-- Fullscreen Toggle -->
                <button
                    @click="toggleFullscreen"
                    class="p-2 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 transition cursor-pointer"
                    title="Fullscreen Mode"
                >
                    <Minimize2 v-if="isFullscreen" class="w-4 h-4" />
                    <Maximize2 v-else class="w-4 h-4" />
                </button>

                <!-- Back to Admin Dashboard -->
                <Link
                    :href="route('dashboard')"
                    class="inline-flex items-center px-3 py-1.5 text-xs font-semibold rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 transition border border-slate-700"
                >
                    <ArrowLeft class="w-3.5 h-3.5 mr-1.5" />
                    <span>Dashboard</span>
                </Link>
            </div>
        </header>

        <!-- Hidden input for USB reader auto-focus -->
        <input
            ref="rfidInputRef"
            v-model="currentScanInput"
            type="text"
            autocomplete="off"
            class="opacity-0 absolute -left-9999px"
        />

        <!-- Main Body Grid -->
        <main class="flex-1 p-4 sm:p-6 lg:p-8 flex flex-col lg:flex-row gap-6 max-w-7xl mx-auto w-full">
            <!-- Center Column: Display Card & Real-time Clock (2/3 width) -->
            <div class="flex-1 flex flex-col justify-between space-y-6">
                <!-- Digital Clock & Active Session Banner -->
                <div class="flex items-center justify-between p-4 sm:p-5 rounded-2xl bg-slate-900/70 border border-slate-800/80 shadow-md backdrop-blur-sm">
                    <div>
                        <span class="text-xs uppercase tracking-wider font-semibold text-slate-400 flex items-center space-x-1.5">
                            <Clock class="w-4 h-4 text-indigo-400" />
                            <span>{{ currentDate || 'Memuat tanggal...' }}</span>
                        </span>
                        <div class="text-3xl sm:text-4xl font-black font-mono tracking-tight text-white mt-1">
                            {{ currentTime || '--:--:--' }} <span class="text-xs text-indigo-400 font-sans font-bold">WIB</span>
                        </div>
                    </div>

                    <div class="text-right">
                        <span class="text-[11px] text-slate-400 block uppercase font-bold tracking-wider">
                            Sesi Aktif Saat Ini
                        </span>
                        <div class="flex items-center justify-end space-x-2 mt-1">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-ping"></span>
                            <span class="text-sm font-bold text-white bg-slate-800 px-3 py-1 rounded-lg border border-slate-700">
                                {{ currentActiveSessionLabel }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- MAIN DISPLAY CARD (STUDENT PHOTO & SCAN STATUS) -->
                <div
                    class="flex-1 min-h-[420px] rounded-3xl p-6 sm:p-8 flex flex-col items-center justify-center text-center transition-all duration-300 relative overflow-hidden"
                    :class="[
                        !scanResult
                            ? 'bg-slate-900/60 border-2 border-dashed border-indigo-500/30'
                            : scanResult.status === 'success' && scanResult.is_late
                            ? 'bg-amber-950/40 border-2 border-amber-500/80 shadow-2xl shadow-amber-500/25 ring-4 ring-amber-500/10'
                            : scanResult.status === 'success'
                            ? 'bg-emerald-950/40 border-2 border-emerald-500/80 shadow-2xl shadow-emerald-500/25 ring-4 ring-emerald-500/10'
                            : scanResult.status === 'duplicate'
                            ? 'bg-amber-950/30 border-2 border-amber-500/60'
                            : 'bg-rose-950/40 border-2 border-rose-500/80 shadow-2xl shadow-rose-500/20',
                    ]"
                >
                    <!-- STATE 1: IDLE / STANDBY -->
                    <div v-if="!scanResult" class="space-y-4 max-w-md">
                        <div class="relative w-28 h-28 mx-auto flex items-center justify-center">
                            <div class="absolute inset-0 rounded-full bg-indigo-500/10 animate-ping"></div>
                            <div class="w-24 h-24 rounded-3xl bg-gradient-to-tr from-indigo-600 to-indigo-400 flex items-center justify-center text-white shadow-xl shadow-indigo-500/30">
                                <Radio class="w-12 h-12 animate-pulse text-white" />
                            </div>
                        </div>

                        <div>
                            <h2 class="text-2xl font-black text-white tracking-tight">
                                TEMPELKAN KARTU RFID SISWA
                            </h2>
                            <p class="text-sm text-slate-400 mt-1 leading-relaxed">
                                Dekatkan kartu atau gantungan tag RFID ke sensor pembaca untuk mencatat presensi.
                            </p>
                        </div>

                        <div class="inline-flex items-center space-x-2 px-3.5 py-1.5 rounded-full bg-slate-800/80 border border-slate-700 text-xs text-slate-300 font-mono">
                            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                            <span>Sensor Siap Menerima Input</span>
                        </div>
                    </div>

                    <!-- STATE 2: SCAN SUCCESS WITH STUDENT PHOTO (PORTRAIT LEFT, INFO RIGHT) -->
                    <div
                        v-else-if="scanResult.status === 'success'"
                        class="w-full max-w-3xl flex flex-col md:flex-row items-center md:items-center justify-center gap-6 md:gap-8 text-center md:text-left animate-in fade-in zoom-in-95 duration-200"
                    >
                        <!-- Left: Large Portrait Student Photo Card -->
                        <div class="relative flex-shrink-0">
                            <div
                                :class="[
                                    scanResult.is_late
                                        ? 'border-amber-400 shadow-amber-500/30 ring-4 ring-amber-500/10'
                                        : 'border-emerald-400 shadow-emerald-500/30 ring-4 ring-emerald-500/10',
                                    'w-48 h-64 sm:w-56 sm:h-76 lg:w-64 lg:h-84 rounded-3xl overflow-hidden border-4 shadow-2xl bg-slate-900 flex items-center justify-center p-1.5',
                                ]"
                            >
                                <img
                                    v-if="scanResult.student.foto"
                                    :src="scanResult.student.foto"
                                    :alt="scanResult.student.nama"
                                    class="w-full h-full object-cover rounded-2xl"
                                />
                                <div
                                    v-else
                                    class="w-full h-full bg-indigo-600 rounded-2xl flex items-center justify-center font-black text-5xl text-white"
                                >
                                    {{ scanResult.student.nama.charAt(0) }}
                                </div>
                            </div>

                            <!-- Success / Late Check Badge Icon -->
                            <div
                                :class="[
                                    scanResult.is_late
                                        ? 'bg-amber-500 text-slate-950'
                                        : 'bg-emerald-500 text-slate-950',
                                    'absolute -bottom-2 -right-2 w-12 h-12 rounded-full border-4 border-slate-950 flex items-center justify-center shadow-xl',
                                ]"
                            >
                                <CheckCircle2 class="w-7 h-7 stroke-[2.5]" />
                            </div>
                        </div>

                        <!-- Right: Student Information & Attendance Details -->
                        <div class="flex-1 space-y-3.5 min-w-0">
                            <!-- Category & Late Status Badge -->
                            <div class="flex items-center justify-center md:justify-start">
                                <Badge
                                    :variant="scanResult.is_late ? 'amber' : 'emerald'"
                                    class="text-xs sm:text-sm px-4 py-1.5 uppercase tracking-wider font-extrabold shadow-sm"
                                >
                                    {{ scanResult.category.nama }}
                                    <span v-if="scanResult.is_late"> • TERLAMBAT +{{ scanResult.late }} MENIT</span>
                                    <span v-else> • TEPAT WAKTU</span>
                                </Badge>
                            </div>

                            <!-- Student Name & Identity -->
                            <div>
                                <h2 class="text-2xl sm:text-3xl lg:text-4xl font-black text-white tracking-tight leading-snug">
                                    {{ scanResult.student.nama }}
                                </h2>
                                <div class="flex flex-wrap items-center justify-center md:justify-start gap-x-3 gap-y-1 mt-2 text-sm text-slate-300">
                                    <span>NISN: <strong class="font-mono text-white font-bold">{{ scanResult.student.nisn }}</strong></span>
                                    <span class="text-slate-600 hidden sm:inline">•</span>
                                    <span>Kelas: <strong class="text-indigo-400 font-bold">Kelas {{ scanResult.student.kelas }}</strong></span>
                                </div>
                            </div>

                            <!-- Tap Timestamp & Summary Pill -->
                            <div class="p-3 rounded-2xl bg-slate-950/70 border border-slate-800/80 flex items-center justify-between text-xs font-mono max-w-md mx-auto md:mx-0">
                                <span class="text-slate-400 flex items-center space-x-1.5">
                                    <Clock class="w-4 h-4 text-indigo-400" />
                                    <span>Waktu Tap:</span>
                                </span>
                                <span class="text-white font-bold text-sm">{{ scanResult.time }} WIB</span>
                            </div>

                            <!-- Result Feedback Message -->
                            <p
                                :class="[
                                    scanResult.is_late
                                        ? 'text-amber-300 bg-amber-950/40 border-amber-800/60'
                                        : 'text-emerald-300 bg-emerald-950/40 border-emerald-800/60',
                                    'text-xs sm:text-sm font-semibold px-3.5 py-2.5 rounded-xl border flex items-center justify-center md:justify-start space-x-2 max-w-md mx-auto md:mx-0',
                                ]"
                            >
                                <CheckCircle2 class="w-4 h-4 flex-shrink-0" />
                                <span>{{ scanResult.message }}</span>
                            </p>
                        </div>
                    </div>

                    <!-- STATE 3: DUPLICATE TAP (PORTRAIT LEFT, INFO RIGHT) -->
                    <div
                        v-else-if="scanResult.status === 'duplicate'"
                        class="w-full max-w-2xl flex flex-col md:flex-row items-center justify-center gap-6 md:gap-8 text-center md:text-left animate-in fade-in zoom-in-95 duration-200"
                    >
                        <div class="relative flex-shrink-0">
                            <div class="w-44 h-58 sm:w-48 sm:h-64 rounded-3xl overflow-hidden border-4 border-amber-500 bg-slate-900 p-1.5 shadow-2xl">
                                <img
                                    v-if="scanResult.student.foto"
                                    :src="scanResult.student.foto"
                                    class="w-full h-full object-cover rounded-2xl"
                                />
                                <div v-else class="w-full h-full bg-amber-600 rounded-2xl flex items-center justify-center font-bold text-4xl text-white">
                                    {{ scanResult.student.nama.charAt(0) }}
                                </div>
                            </div>
                            <div class="absolute -bottom-2 -right-2 w-10 h-10 rounded-full bg-amber-500 text-slate-950 flex items-center justify-center border-4 border-slate-950 shadow-lg">
                                <AlertCircle class="w-6 h-6" />
                            </div>
                        </div>

                        <div class="flex-1 space-y-3 min-w-0">
                            <div class="flex items-center justify-center md:justify-start">
                                <Badge variant="amber" class="text-xs px-3.5 py-1.5 font-bold uppercase tracking-wider">
                                    SUDAH PRESENSI PADA SESI INI
                                </Badge>
                            </div>
                            <h2 class="text-2xl sm:text-3xl font-black text-white tracking-tight">
                                {{ scanResult.student.nama }}
                            </h2>
                            <p class="text-sm text-slate-300">
                                Kelas: <strong class="text-indigo-400 font-bold">Kelas {{ scanResult.student.kelas }}</strong>
                            </p>
                            <div class="p-2.5 rounded-xl bg-slate-950/70 border border-slate-800 text-xs font-mono max-w-md mx-auto md:mx-0">
                                Sudah tercatat pada: <strong class="text-amber-400 font-bold">{{ scanResult.time }} WIB</strong>
                            </div>
                            <p class="text-xs text-amber-300 font-medium bg-amber-950/40 p-3 rounded-xl border border-amber-800/40 max-w-md mx-auto md:mx-0">
                                {{ scanResult.message }}
                            </p>
                        </div>
                    </div>

                    <!-- STATE 4: UNREGISTERED CARD -->
                    <div v-else-if="scanResult.status === 'unregistered'" class="space-y-4 max-w-lg animate-in fade-in zoom-in-95 duration-200">
                        <div class="w-20 h-20 rounded-full bg-rose-500/20 border-2 border-rose-500 text-rose-400 mx-auto flex items-center justify-center">
                            <XCircle class="w-10 h-10" />
                        </div>
                        <Badge variant="rose" class="text-xs px-3 py-1">KARTU BELUM TERDAFTAR</Badge>
                        <h2 class="text-xl font-bold text-white">
                            Kartu RFID Tidak Dikenali
                        </h2>
                        <p class="text-xs font-mono text-slate-300 bg-slate-950/80 px-3 py-1.5 rounded-lg border border-slate-800">
                            UID: {{ scanResult.uid }}
                        </p>
                        <p class="text-xs text-slate-400">
                            Kartu ini belum dipasangkan ke data siswa mana pun.
                        </p>
                        <Button
                            @click="openRegisterModal(scanResult.uid)"
                            size="sm"
                            class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold"
                        >
                            <UserPlus class="w-4 h-4 mr-1.5" />
                            <span>Daftarkan Kartu Ini ke Siswa</span>
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Right Column: Live Feed of Recent Scans with Photos (1/3 width) -->
            <div class="w-full lg:w-80 flex flex-col space-y-4">
                <!-- Mini Stats Pill Cards -->
                <div class="p-4 rounded-2xl bg-slate-900/80 border border-slate-800 flex items-center justify-between">
                    <div>
                        <span class="text-[10px] uppercase font-bold text-slate-400 block tracking-wider">Total Tap Hari Ini</span>
                        <span class="text-2xl font-black text-white font-mono">{{ localStats.total_tap_today }}</span>
                    </div>
                    <div class="text-right">
                        <span class="text-[10px] uppercase font-bold text-slate-400 block tracking-wider">Siswa Hadir</span>
                        <span class="text-2xl font-black text-emerald-400 font-mono">{{ localStats.unique_students }}</span>
                    </div>
                </div>

                <!-- Recent Live Scans Feed with Photos -->
                <div class="flex-1 bg-slate-900/80 rounded-2xl border border-slate-800 flex flex-col overflow-hidden shadow-lg">
                    <div class="p-3.5 border-b border-slate-800 flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            <h3 class="font-bold text-xs uppercase tracking-wider text-slate-300">
                                Log Tap Terkini
                            </h3>
                        </div>
                        <span class="text-[10px] text-slate-500 font-mono font-medium">Real-time</span>
                    </div>

                    <div class="flex-1 p-3 overflow-y-auto space-y-2 max-h-[500px]">
                        <div v-if="recentScansList.length === 0" class="p-8 text-center text-slate-500 text-xs">
                            <Radio class="w-6 h-6 mx-auto mb-2 text-slate-600" />
                            <p>Belum ada aktivitas tap kartu hari ini.</p>
                        </div>

                        <div
                            v-for="scan in recentScansList"
                            :key="scan.id"
                            class="p-2.5 rounded-xl bg-slate-950/60 border border-slate-800/80 flex items-center justify-between text-xs hover:border-slate-700 transition"
                        >
                            <div class="flex items-center space-x-2.5 min-w-0 pr-2">
                                <img
                                    v-if="scan.student_photo"
                                    :src="scan.student_photo"
                                    class="w-8 h-8 rounded-lg object-cover border border-slate-700 flex-shrink-0"
                                />
                                <div
                                    v-else
                                    class="w-8 h-8 rounded-lg bg-indigo-600 text-white flex items-center justify-center font-bold text-xs flex-shrink-0"
                                >
                                    {{ scan.student_name.charAt(0) }}
                                </div>
                                <div class="min-w-0">
                                    <p class="font-bold text-white truncate">{{ scan.student_name }}</p>
                                    <p class="text-[10px] text-slate-400 font-mono">
                                        Kelas {{ scan.student_class }} • {{ scan.waktu }} WIB
                                    </p>
                                </div>
                            </div>

                            <div class="flex-shrink-0 text-right">
                                <Badge :variant="scan.category_color" class="text-[10px] py-0.5">
                                    {{ scan.category_name }}
                                </Badge>
                                <p v-if="scan.menit_terlambat > 0" class="text-[9px] text-amber-400 font-mono font-bold mt-0.5">
                                    +{{ scan.menit_terlambat }} menit
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- MODAL: Quick Register Unregistered Card -->
        <Modal :show="showRegisterCardModal" @close="showRegisterCardModal = false" max-width="md">
            <div class="p-6 bg-slate-900 text-white rounded-2xl border border-slate-800">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3 mb-4">
                    <h3 class="text-base font-bold flex items-center space-x-2">
                        <UserPlus class="w-5 h-5 text-indigo-400" />
                        <span>Daftarkan Kartu ke Siswa</span>
                    </h3>
                    <button @click="showRegisterCardModal = false" class="text-slate-400 hover:text-white">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <div class="space-y-4">
                    <div class="p-3 rounded-xl bg-slate-950 border border-slate-800">
                        <span class="text-xs text-slate-400 block">UID Kartu yang terdeteksi:</span>
                        <span class="font-mono font-bold text-indigo-400 text-base">{{ unregisteredCardUid }}</span>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">
                            Pilih Siswa:
                        </label>
                        <Input
                            v-model="studentSearchQuery"
                            placeholder="Ketik nama atau NISN siswa..."
                            class="mb-2 bg-slate-950 border-slate-700 text-white text-xs"
                        />

                        <div class="max-h-48 overflow-y-auto space-y-1 rounded-lg border border-slate-800 p-1 bg-slate-950">
                            <label
                                v-for="st in filteredStudentsToRegister"
                                :key="st.id"
                                :class="[
                                    selectedStudentId === st.id ? 'bg-indigo-600 text-white' : 'hover:bg-slate-800 text-slate-300',
                                    'p-2 rounded-md flex items-center justify-between text-xs cursor-pointer',
                                ]"
                            >
                                <div class="flex items-center space-x-2">
                                    <img v-if="st.foto" :src="st.foto" class="w-6 h-6 rounded-md object-cover" />
                                    <input type="radio" v-model="selectedStudentId" :value="st.id" class="hidden" />
                                    <span class="font-semibold">{{ st.nama }}</span>
                                </div>
                                <span class="text-[11px] opacity-75">Kelas {{ st.kelas }}</span>
                            </label>
                        </div>
                    </div>

                    <div v-if="registerError" class="p-2.5 rounded-md bg-rose-950/60 border border-rose-800 text-rose-300 text-xs">
                        {{ registerError }}
                    </div>

                    <div class="flex justify-end space-x-2 pt-2 border-t border-slate-800">
                        <Button @click="showRegisterCardModal = false" variant="outline" size="sm">Batal</Button>
                        <Button
                            @click="submitRegisterCard"
                            :disabled="registerLoading || !selectedStudentId"
                            size="sm"
                            class="bg-indigo-600 hover:bg-indigo-700 text-white"
                        >
                            {{ registerLoading ? 'Menyimpan...' : 'Pasangkan & Catat Absen' }}
                        </Button>
                    </div>
                </div>
            </div>
        </Modal>
    </div>
</template>
