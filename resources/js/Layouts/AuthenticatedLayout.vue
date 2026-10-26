<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import {
    LayoutDashboard,
    Users,
    Tags,
    Radio,
    FileSpreadsheet,
    LogOut,
    User as UserIcon,
    Menu,
    X,
    Clock,
    School,
    ExternalLink,
    CheckCircle,
    AlertCircle,
    Sun,
    Moon,
} from 'lucide-vue-next';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';

const page = usePage();
const sidebarOpen = ref(false);
const currentTime = ref('');
const currentDate = ref('');
const isDark = ref(false);

const toggleTheme = () => {
    isDark.value = !isDark.value;
    if (isDark.value) {
        document.documentElement.classList.add('dark');
        localStorage.setItem('theme', 'dark');
    } else {
        document.documentElement.classList.remove('dark');
        localStorage.setItem('theme', 'light');
    }
};

const updateClock = () => {
    const now = new Date();
    currentTime.value = now.toLocaleTimeString('id-ID', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: false,
    });
    currentDate.value = now.toLocaleDateString('id-ID', {
        weekday: 'short',
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
};

let timer = null;
onMounted(() => {
    updateClock();
    timer = setInterval(updateClock, 1000);

    const savedTheme = localStorage.getItem('theme');
    if (savedTheme === 'dark') {
        isDark.value = true;
        document.documentElement.classList.add('dark');
    } else {
        isDark.value = false;
        document.documentElement.classList.remove('dark');
    }
});

onUnmounted(() => {
    if (timer) clearInterval(timer);
});

const navigation = [
    { name: 'Dashboard', href: route('dashboard'), icon: LayoutDashboard, active: route().current('dashboard') },
    { name: 'Data Siswa', href: route('students.index'), icon: Users, active: route().current('students.*') },
    { name: 'Data Kelas', href: route('classes.index'), icon: School, active: route().current('classes.*') },
    { name: 'Kategori Absen', href: route('categories.index'), icon: Tags, active: route().current('categories.*') },
    { name: 'Laporan Kehadiran', href: route('reports.index'), icon: FileSpreadsheet, active: route().current('reports.*') },
];
</script>

<template>
    <div class="min-h-screen bg-slate-100 dark:bg-slate-950 font-sans text-slate-900 dark:text-slate-100 antialiased flex flex-col md:flex-row">
        <!-- Mobile Sidebar Overlay -->
        <div
            v-if="sidebarOpen"
            @click="sidebarOpen = false"
            class="fixed inset-0 z-40 bg-slate-900/60 backdrop-blur-sm md:hidden"
        ></div>

        <!-- Sidebar Navigation -->
        <aside
            :class="[
                sidebarOpen ? 'translate-x-0' : '-translate-x-full',
                'fixed inset-y-0 left-0 z-50 w-64 bg-slate-900 text-white flex flex-col transition-transform duration-200 ease-in-out md:static md:translate-x-0 border-r border-slate-800 shadow-xl',
            ]"
        >
            <!-- Sidebar Header / Branding -->
            <div class="h-16 flex items-center justify-between px-5 border-b border-slate-800 bg-slate-950/40">
                <div class="flex items-center space-x-3">
                    <div class="w-9 h-9 rounded-lg bg-indigo-600 flex items-center justify-center text-white shadow-md shadow-indigo-600/30">
                        <Radio class="w-5 h-5 animate-pulse" />
                    </div>
                    <div>
                        <h1 class="font-bold text-sm leading-tight tracking-wide text-white">ABSENSI RFID</h1>
                        <p class="text-[11px] text-slate-400 font-medium">Sistem Presensi Siswa</p>
                    </div>
                </div>
                <button
                    @click="sidebarOpen = false"
                    class="md:hidden text-slate-400 hover:text-white p-1 rounded-md"
                >
                    <X class="w-5 h-5" />
                </button>
            </div>

            <!-- Main Navigation Links -->
            <nav class="flex-1 px-3 py-4 space-y-1.5 overflow-y-auto">
                <div class="px-3 pb-2 text-[10px] font-semibold text-slate-400 uppercase tracking-wider">
                    Menu Utama
                </div>

                <Link
                    v-for="item in navigation"
                    :key="item.name"
                    :href="item.href"
                    :class="[
                        item.active
                            ? 'bg-indigo-600 text-white font-medium shadow-md shadow-indigo-600/30'
                            : 'text-slate-300 hover:bg-slate-800/80 hover:text-white',
                        'group flex items-center px-3 py-2.5 text-sm rounded-lg transition-all duration-150',
                    ]"
                >
                    <component
                        :is="item.icon"
                        :class="[
                            item.active ? 'text-white' : 'text-slate-400 group-hover:text-slate-200',
                            'mr-3 h-4 w-4 flex-shrink-0 transition-colors',
                        ]"
                    />
                    <span>{{ item.name }}</span>
                </Link>

                <div class="pt-4 px-3 pb-2 text-[10px] font-semibold text-slate-400 uppercase tracking-wider">
                    Operasional
                </div>

                <!-- Highlighted RFID Terminal Button -->
                <a
                    :href="route('attendance.scan')"
                    target="_blank"
                    class="group flex items-center justify-between px-3 py-2.5 text-sm rounded-lg bg-emerald-600/20 text-emerald-300 border border-emerald-500/30 hover:bg-emerald-600/30 transition-all font-medium"
                >
                    <div class="flex items-center">
                        <Radio class="mr-3 h-4 w-4 text-emerald-400 animate-pulse flex-shrink-0" />
                        <span>Terminal Scan RFID</span>
                    </div>
                    <ExternalLink class="w-3.5 h-3.5 text-emerald-400 opacity-70 group-hover:opacity-100" />
                </a>
            </nav>

            <!-- Sidebar Footer / Clock & Admin Status -->
            <div class="p-3 border-t border-slate-800/80 bg-slate-950/50">
                <div class="rounded-lg bg-slate-800/60 p-2.5 mb-2 border border-slate-700/50 flex items-center justify-between">
                    <div class="flex items-center space-x-2 text-slate-300">
                        <Clock class="w-4 h-4 text-indigo-400" />
                        <span class="text-xs font-mono font-medium">{{ currentTime || '--:--:--' }} WIB</span>
                    </div>
                    <span class="text-[10px] px-1.5 py-0.5 rounded bg-emerald-500/20 text-emerald-400 font-semibold border border-emerald-500/30">
                        ONLINE
                    </span>
                </div>

                <div class="flex items-center justify-between px-2 pt-1 text-xs text-slate-400">
                    <span class="truncate">Role: <strong class="text-slate-200">Admin</strong></span>
                    <Link
                        :href="route('logout')"
                        method="post"
                        as="button"
                        class="text-rose-400 hover:text-rose-300 flex items-center space-x-1 cursor-pointer"
                    >
                        <LogOut class="w-3.5 h-3.5" />
                        <span>Keluar</span>
                    </Link>
                </div>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
            <!-- Top Navbar Header -->
            <header class="h-16 bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between px-4 sm:px-6 z-10 sticky top-0 shadow-sm">
                <div class="flex items-center space-x-3">
                    <button
                        @click="sidebarOpen = true"
                        class="md:hidden p-2 rounded-lg text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800"
                    >
                        <Menu class="w-5 h-5" />
                    </button>
                    <div>
                        <slot name="header" />
                    </div>
                </div>

                <!-- Topbar Actions -->
                <div class="flex items-center space-x-3">
                    <!-- Light / Dark Mode Toggle Button -->
                    <button
                        @click="toggleTheme"
                        class="p-2 rounded-lg text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer"
                        :title="isDark ? 'Beralih ke Mode Terang' : 'Beralih ke Mode Gelap'"
                    >
                        <Sun v-if="isDark" class="w-4 h-4 text-amber-400" />
                        <Moon v-else class="w-4 h-4 text-slate-600" />
                    </button>

                    <!-- Quick Scan Button -->
                    <a
                        :href="route('attendance.scan')"
                        class="hidden sm:inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-full bg-indigo-50 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800 hover:bg-indigo-100 transition-colors shadow-sm"
                    >
                        <Radio class="w-3.5 h-3.5 mr-1.5 text-indigo-600 dark:text-indigo-400" />
                        Buka Scanner
                    </a>

                    <!-- User Profile Dropdown -->
                    <Dropdown align="right" width="48">
                        <template #trigger>
                            <button
                                class="flex items-center space-x-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:text-slate-900 p-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer"
                            >
                                <div class="w-8 h-8 rounded-full bg-indigo-600 text-white flex items-center justify-center font-bold text-xs shadow-sm">
                                    {{ page.props.auth.user.name.charAt(0) }}
                                </div>
                                <span class="hidden sm:inline-block max-w-[120px] truncate">{{ page.props.auth.user.name }}</span>
                            </button>
                        </template>

                        <template #content>
                            <div class="px-4 py-2 border-b border-slate-100 dark:border-slate-800 text-xs">
                                <p class="font-medium text-slate-900 dark:text-slate-100">{{ page.props.auth.user.name }}</p>
                                <p class="text-slate-500 truncate">{{ page.props.auth.user.email }}</p>
                            </div>
                            <DropdownLink :href="route('profile.edit')">
                                Profil Akun
                            </DropdownLink>
                            <DropdownLink :href="route('logout')" method="post" as="button" class="text-rose-600 dark:text-rose-400">
                                Keluar Aplikasi
                            </DropdownLink>
                        </template>
                    </Dropdown>
                </div>
            </header>

            <!-- Flash Alerts -->
            <div v-if="page.props.flash?.success" class="px-4 sm:px-6 pt-4">
                <div class="p-3 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 dark:bg-emerald-950 dark:border-emerald-800 dark:text-emerald-300 flex items-center space-x-2 text-sm shadow-sm">
                    <CheckCircle class="w-4 h-4 text-emerald-600 flex-shrink-0" />
                    <span>{{ page.props.flash.success }}</span>
                </div>
            </div>
            <div v-if="page.props.flash?.error" class="px-4 sm:px-6 pt-4">
                <div class="p-3 rounded-lg bg-rose-50 border border-rose-200 text-rose-800 dark:bg-rose-950 dark:border-rose-800 dark:text-rose-300 flex items-center space-x-2 text-sm shadow-sm">
                    <AlertCircle class="w-4 h-4 text-rose-600 flex-shrink-0" />
                    <span>{{ page.props.flash.error }}</span>
                </div>
            </div>

            <!-- Page Main Content Slot -->
            <main class="flex-1 p-4 sm:p-6 overflow-y-auto">
                <slot />
            </main>
        </div>
    </div>
</template>
