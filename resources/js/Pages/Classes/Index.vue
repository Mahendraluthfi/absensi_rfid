<script setup>
import { ref, computed, watch } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import Card from '@/Components/ui/Card.vue';
import CardContent from '@/Components/ui/CardContent.vue';
import Badge from '@/Components/ui/Badge.vue';
import Button from '@/Components/ui/Button.vue';
import Input from '@/Components/ui/Input.vue';
import Modal from '@/Components/Modal.vue';
import {
    School,
    GraduationCap,
    Plus,
    Edit2,
    Trash2,
    Users,
    ArrowRightLeft,
    CheckCircle2,
    AlertCircle,
    Info,
    X,
    UserCheck,
    Radio as RadioIcon,
    ShieldCheck,
    AlertTriangle,
} from 'lucide-vue-next';
import axios from 'axios';

const props = defineProps({
    classes: Array,
    stats: Object,
});

const activeTab = ref('classes'); // 'classes' or 'promotion'

// Master Class Add / Edit Modal
const showClassModal = ref(false);
const editingClass = ref(null);

const classForm = useForm({
    nama: '',
    tingkat: 7,
    urutan: 1,
    keterangan: '',
    is_active: true,
});

const openAddClass = () => {
    editingClass.value = null;
    classForm.reset();
    classForm.clearErrors();
    classForm.tingkat = 7;
    classForm.urutan = (props.classes?.length || 0) + 1;
    classForm.is_active = true;
    showClassModal.value = true;
};

const openEditClass = (cls) => {
    editingClass.value = cls;
    classForm.clearErrors();
    classForm.nama = cls.nama;
    classForm.tingkat = cls.tingkat;
    classForm.urutan = cls.urutan;
    classForm.keterangan = cls.keterangan || '';
    classForm.is_active = Boolean(cls.is_active);
    showClassModal.value = true;
};

const submitClass = () => {
    if (editingClass.value) {
        classForm.put(route('classes.update', editingClass.value.id), {
            onSuccess: () => {
                showClassModal.value = false;
            },
        });
    } else {
        classForm.post(route('classes.store'), {
            onSuccess: () => {
                showClassModal.value = false;
            },
        });
    }
};

const deleteClass = (cls) => {
    if (confirm(`Yakin ingin menghapus kelas "${cls.nama}"?`)) {
        router.delete(route('classes.destroy', cls.id));
    }
};

// ==========================================
// Kenaikan Kelas & Kelulusan (Promotion Wizard)
// ==========================================
const selectedSourceClassId = ref('');
const promotionAction = ref('promote'); // 'promote' or 'graduate'
const selectedTargetClassId = ref('');
const sourceStudents = ref([]);
const selectedStudentIds = ref([]);
const loadingStudents = ref(false);
const showConfirmModal = ref(false);
const isSubmittingPromotion = ref(false);

// Load students when source class changes
const loadClassStudents = async () => {
    if (!selectedSourceClassId.value) {
        sourceStudents.value = [];
        selectedStudentIds.value = [];
        return;
    }

    loadingStudents.value = true;
    try {
        const res = await axios.get(route('classes.students', selectedSourceClassId.value));
        sourceStudents.value = res.data.students || [];
        // Default: Centang semua siswa
        selectedStudentIds.value = sourceStudents.value.map((s) => s.id);
    } catch (err) {
        alert('Gagal memuat daftar siswa untuk kelas ini.');
    } finally {
        loadingStudents.value = false;
    }
};

watch(selectedSourceClassId, () => {
    loadClassStudents();
});

// Destination classes available (excluding source class)
const availableTargetClasses = computed(() => {
    return props.classes.filter((c) => String(c.id) !== String(selectedSourceClassId.value));
});

// Select All Toggle
const isAllSelected = computed(() => {
    return sourceStudents.value.length > 0 && selectedStudentIds.value.length === sourceStudents.value.length;
});

const toggleSelectAll = () => {
    if (isAllSelected.value) {
        selectedStudentIds.value = [];
    } else {
        selectedStudentIds.value = sourceStudents.value.map((s) => s.id);
    }
};

const toggleStudentSelection = (id) => {
    const idx = selectedStudentIds.value.indexOf(id);
    if (idx > -1) {
        selectedStudentIds.value.splice(idx, 1);
    } else {
        selectedStudentIds.value.push(id);
    }
};

// Object of source class and target class for preview
const currentSourceClass = computed(() => {
    return props.classes.find((c) => String(c.id) === String(selectedSourceClassId.value));
});

const currentTargetClass = computed(() => {
    return props.classes.find((c) => String(c.id) === String(selectedTargetClassId.value));
});

// Unselected students (tinggal kelas)
const unselectedStudentsCount = computed(() => {
    return sourceStudents.value.length - selectedStudentIds.value.length;
});

// Validate and open confirmation modal
const openConfirmPromotion = () => {
    if (!selectedSourceClassId.value) {
        alert('Silakan pilih Kelas Asal terlebih dahulu!');
        return;
    }
    if (promotionAction.value === 'promote' && !selectedTargetClassId.value) {
        alert('Silakan pilih Kelas Tujuan untuk kenaikan kelas!');
        return;
    }
    if (selectedStudentIds.value.length === 0) {
        alert('Tidak ada siswa yang dipilih untuk dipindahkan!');
        return;
    }

    showConfirmModal.value = true;
};

const executePromotion = () => {
    isSubmittingPromotion.value = true;

    router.post(
        route('classes.promote'),
        {
            action: promotionAction.value,
            source_class_id: selectedSourceClassId.value,
            target_class_id: promotionAction.value === 'promote' ? selectedTargetClassId.value : null,
            student_ids: selectedStudentIds.value,
        },
        {
            onSuccess: () => {
                showConfirmModal.value = false;
                isSubmittingPromotion.value = false;
                // Reload students of the class
                loadClassStudents();
            },
            onError: () => {
                isSubmittingPromotion.value = false;
            },
        }
    );
};
</script>

<template>
    <Head title="Data Master Kelas & Kenaikan Kelas" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="text-xl font-bold leading-tight text-slate-800 dark:text-slate-100 flex items-center space-x-2">
                        <School class="w-5 h-5 text-indigo-600" />
                        <span>Data Master Kelas & Kenaikan Kelas</span>
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Kelola rombongan belajar, wali kelas, serta pemindahan dan kelulusan siswa massal
                    </p>
                </div>

                <div v-if="activeTab === 'classes'">
                    <Button @click="openAddClass" size="sm" class="bg-indigo-600 hover:bg-indigo-700 text-white">
                        <Plus class="w-4 h-4 mr-1.5" />
                        <span>Tambah Kelas Baru</span>
                    </Button>
                </div>
            </div>
        </template>

        <div class="space-y-5">
            <!-- 3 Stat Metrics -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <Card class="hover:border-slate-300 dark:hover:border-slate-700 transition shadow-xs">
                    <CardContent class="p-4 flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 flex items-center justify-center flex-shrink-0">
                            <School class="w-5 h-5" />
                        </div>
                        <div>
                            <span class="text-xs text-slate-500 block">Total Kelas Rombel</span>
                            <span class="text-xl font-black text-slate-900 dark:text-white">{{ stats.total_classes }} Kelas</span>
                        </div>
                    </CardContent>
                </Card>

                <Card class="hover:border-slate-300 dark:hover:border-slate-700 transition shadow-xs">
                    <CardContent class="p-4 flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 flex items-center justify-center flex-shrink-0">
                            <Users class="w-5 h-5" />
                        </div>
                        <div>
                            <span class="text-xs text-slate-500 block">Total Siswa Aktif</span>
                            <span class="text-xl font-black text-slate-900 dark:text-white">{{ stats.total_active_students }} Siswa</span>
                        </div>
                    </CardContent>
                </Card>

                <Card class="hover:border-slate-300 dark:hover:border-slate-700 transition shadow-xs">
                    <CardContent class="p-4 flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-xl bg-amber-50 dark:bg-amber-950/60 text-amber-600 flex items-center justify-center flex-shrink-0">
                            <GraduationCap class="w-5 h-5" />
                        </div>
                        <div>
                            <span class="text-xs text-slate-500 block">Siswa Lulus / Alumni</span>
                            <span class="text-xl font-black text-slate-900 dark:text-white">{{ stats.total_graduated_students }} Siswa</span>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Tab Navigation -->
            <div class="flex items-center space-x-2 border-b border-slate-200 dark:border-slate-800">
                <button
                    @click="activeTab = 'classes'"
                    :class="[
                        activeTab === 'classes'
                            ? 'border-indigo-600 text-indigo-600 font-bold'
                            : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400',
                        'px-4 py-2.5 border-b-2 text-sm flex items-center space-x-2 transition cursor-pointer',
                    ]"
                >
                    <School class="w-4 h-4" />
                    <span>Daftar Rombongan Belajar ({{ classes.length }})</span>
                </button>
                <button
                    @click="activeTab = 'promotion'"
                    :class="[
                        activeTab === 'promotion'
                            ? 'border-indigo-600 text-indigo-600 font-bold'
                            : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400',
                        'px-4 py-2.5 border-b-2 text-sm flex items-center space-x-2 transition cursor-pointer',
                    ]"
                >
                    <ArrowRightLeft class="w-4 h-4" />
                    <span>Kenaikan Kelas & Kelulusan Siswa</span>
                </button>
            </div>

            <!-- TAB 1: Master Classes Table -->
            <div v-if="activeTab === 'classes'" class="space-y-4">
                <Card>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs text-slate-600 dark:text-slate-300">
                            <thead class="bg-slate-100/75 dark:bg-slate-800/60 uppercase font-semibold text-slate-700 dark:text-slate-200 border-b border-slate-200 dark:border-slate-800">
                                <tr>
                                    <th class="p-3.5 w-12 text-center">No</th>
                                    <th class="p-3.5">Nama Kelas</th>
                                    <th class="p-3.5">Tingkat / Grade</th>
                                    <th class="p-3.5">Jumlah Siswa Aktif</th>
                                    <th class="p-3.5">Keterangan / Wali Kelas</th>
                                    <th class="p-3.5">Status</th>
                                    <th class="p-3.5 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                <tr v-if="classes.length === 0">
                                    <td colspan="7" class="p-8 text-center text-slate-400">
                                        Belum ada data kelas. Klik tombol "Tambah Kelas Baru" untuk menambahkan.
                                    </td>
                                </tr>
                                <tr
                                    v-for="(cls, idx) in classes"
                                    :key="cls.id"
                                    class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors"
                                >
                                    <td class="p-3.5 text-center font-mono text-slate-400">
                                        {{ idx + 1 }}
                                    </td>
                                    <td class="p-3.5">
                                        <div class="flex items-center space-x-2">
                                            <span class="font-bold text-sm text-slate-900 dark:text-white">
                                                Kelas {{ cls.nama }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="p-3.5">
                                        <Badge variant="outline" class="font-mono">
                                            Tingkat {{ cls.tingkat }}
                                        </Badge>
                                    </td>
                                    <td class="p-3.5">
                                        <div class="flex items-center space-x-1.5">
                                            <span class="font-bold text-slate-900 dark:text-white text-sm">
                                                {{ cls.active_students_count }}
                                            </span>
                                            <span class="text-[11px] text-slate-400">
                                                siswa (total {{ cls.students_count }})
                                            </span>
                                        </div>
                                    </td>
                                    <td class="p-3.5 text-slate-600 dark:text-slate-400">
                                        {{ cls.keterangan || '-' }}
                                    </td>
                                    <td class="p-3.5">
                                        <Badge :variant="cls.is_active ? 'emerald' : 'secondary'">
                                            {{ cls.is_active ? 'Aktif' : 'Non-Aktif' }}
                                        </Badge>
                                    </td>
                                    <td class="p-3.5 text-right space-x-1">
                                        <button
                                            @click="openEditClass(cls)"
                                            title="Edit Kelas"
                                            class="p-1.5 rounded-md hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-300 cursor-pointer"
                                        >
                                            <Edit2 class="w-4 h-4" />
                                        </button>
                                        <button
                                            @click="deleteClass(cls)"
                                            title="Hapus Kelas"
                                            class="p-1.5 rounded-md hover:bg-slate-100 dark:hover:bg-slate-800 text-rose-600 cursor-pointer"
                                        >
                                            <Trash2 class="w-4 h-4" />
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </Card>
            </div>

            <!-- TAB 2: Promotion & Graduation Wizard -->
            <div v-if="activeTab === 'promotion'" class="space-y-5">
                <!-- Info Alert -->
                <div class="p-4 rounded-xl bg-indigo-50 border border-indigo-200 dark:bg-indigo-950/40 dark:border-indigo-800 text-xs text-indigo-900 dark:text-indigo-200 flex items-start space-x-3">
                    <Info class="w-5 h-5 text-indigo-600 dark:text-indigo-400 mt-0.5 flex-shrink-0" />
                    <div class="leading-relaxed">
                        <strong>Fitur Kenaikan Kelas & Kelulusan Siswa Massal:</strong>
                        Pilih kelas asal dan kelas tujuan untuk memindahkan siswa sekaligus. Seluruh siswa otomatis dicentang secara default.
                        Jika ada siswa yang <strong>tinggal kelas (tidak naik)</strong> atau mutasi, Anda cukup <strong>menghilangkan centang</strong> pada siswa tersebut agar tetap tinggal di kelas asal.
                    </div>
                </div>

                <!-- Wizard Step 1: Configuration Form -->
                <Card>
                    <CardContent class="p-5 space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <!-- Select Source Class -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                    1. Pilih Kelas Asal <span class="text-rose-500">*</span>
                                </label>
                                <select
                                    v-model="selectedSourceClassId"
                                    class="w-full h-10 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 px-3 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                                >
                                    <option value="" disabled>-- Pilih Kelas Asal --</option>
                                    <option v-for="c in classes" :key="c.id" :value="c.id">
                                        Kelas {{ c.nama }} ({{ c.active_students_count }} Siswa Aktif)
                                    </option>
                                </select>
                            </div>

                            <!-- Action Choice: Promote vs Graduate -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                    2. Jenis Aksi <span class="text-rose-500">*</span>
                                </label>
                                <div class="grid grid-cols-2 gap-2">
                                    <label
                                        :class="[
                                            promotionAction === 'promote'
                                                ? 'border-indigo-600 bg-indigo-50/60 dark:bg-indigo-950/50 text-indigo-900 dark:text-indigo-200 font-bold'
                                                : 'border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300',
                                            'p-2.5 rounded-lg border text-xs flex items-center justify-center cursor-pointer transition text-center',
                                        ]"
                                    >
                                        <input type="radio" v-model="promotionAction" value="promote" class="hidden" />
                                        <span>Naik / Pindah Kelas</span>
                                    </label>

                                    <label
                                        :class="[
                                            promotionAction === 'graduate'
                                                ? 'border-amber-600 bg-amber-50/60 dark:bg-amber-950/50 text-amber-900 dark:text-amber-200 font-bold'
                                                : 'border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300',
                                            'p-2.5 rounded-lg border text-xs flex items-center justify-center cursor-pointer transition text-center',
                                        ]"
                                    >
                                        <input type="radio" v-model="promotionAction" value="graduate" class="hidden" />
                                        <span>Kelulusan (Alumni)</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Destination Class (if action == promote) -->
                            <div v-if="promotionAction === 'promote'">
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                    3. Pilih Kelas Tujuan <span class="text-rose-500">*</span>
                                </label>
                                <select
                                    v-model="selectedTargetClassId"
                                    class="w-full h-10 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 px-3 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                                >
                                    <option value="" disabled>-- Pilih Kelas Tujuan --</option>
                                    <option v-for="c in availableTargetClasses" :key="c.id" :value="c.id">
                                        Kelas {{ c.nama }} (Tingkat {{ c.tingkat }})
                                    </option>
                                </select>
                            </div>

                            <!-- Graduation Notice (if action == graduate) -->
                            <div v-else class="p-2.5 rounded-lg bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 text-amber-900 dark:text-amber-300 text-xs flex items-center space-x-2">
                                <GraduationCap class="w-5 h-5 flex-shrink-0" />
                                <span class="text-[11px] leading-tight">
                                    Siswa terpilih akan berstatus <strong>Lulus</strong> dan kartu RFID-nya otomatis dilepas (unpaired).
                                </span>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Wizard Step 2: Student Checklist Table -->
                <div v-if="selectedSourceClassId">
                    <Card>
                        <div class="p-4 border-b border-slate-100 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                            <div class="flex items-center space-x-3">
                                <h3 class="font-bold text-sm text-slate-900 dark:text-white">
                                    Daftar Siswa Kelas {{ currentSourceClass?.nama }}
                                </h3>
                                <Badge variant="secondary" class="font-mono">
                                    {{ selectedStudentIds.length }} dari {{ sourceStudents.length }} siswa terpilih
                                </Badge>
                            </div>

                            <div class="flex items-center space-x-2">
                                <button
                                    @click="toggleSelectAll"
                                    class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 cursor-pointer"
                                >
                                    {{ isAllSelected ? 'Batalkan Semua' : 'Centang Semua Siswa' }}
                                </button>
                            </div>
                        </div>

                        <!-- Loading State -->
                        <div v-if="loadingStudents" class="p-12 text-center text-slate-400 text-xs">
                            Memuat daftar siswa...
                        </div>

                        <!-- Empty State -->
                        <div v-else-if="sourceStudents.length === 0" class="p-12 text-center text-slate-400 text-xs">
                            Tidak ada siswa aktif ditemukan di kelas ini.
                        </div>

                        <!-- Table -->
                        <div v-else class="overflow-x-auto">
                            <table class="w-full text-left text-xs text-slate-600 dark:text-slate-300">
                                <thead class="bg-slate-100/75 dark:bg-slate-800/60 uppercase font-semibold text-slate-700 dark:text-slate-200 border-b border-slate-200 dark:border-slate-800">
                                    <tr>
                                        <th class="p-3.5 w-10 text-center">
                                            <input
                                                type="checkbox"
                                                :checked="isAllSelected"
                                                @change="toggleSelectAll"
                                                class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer"
                                            />
                                        </th>
                                        <th class="p-3.5 w-12 text-center">No</th>
                                        <th class="p-3.5">NISN</th>
                                        <th class="p-3.5">Nama Lengkap Siswa</th>
                                        <th class="p-3.5">Kartu RFID</th>
                                        <th class="p-3.5">Status Saat Ini</th>
                                        <th class="p-3.5 text-center">Status Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                    <tr
                                        v-for="(st, idx) in sourceStudents"
                                        :key="st.id"
                                        @click="toggleStudentSelection(st.id)"
                                        :class="[
                                            selectedStudentIds.includes(st.id)
                                                ? 'bg-indigo-50/40 dark:bg-indigo-950/20'
                                                : 'hover:bg-slate-50 dark:hover:bg-slate-800/30 opacity-60',
                                            'transition-colors cursor-pointer',
                                        ]"
                                    >
                                        <td class="p-3.5 text-center" @click.stop>
                                            <input
                                                type="checkbox"
                                                :value="st.id"
                                                v-model="selectedStudentIds"
                                                class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer"
                                            />
                                        </td>
                                        <td class="p-3.5 text-center font-mono text-slate-400">
                                            {{ idx + 1 }}
                                        </td>
                                        <td class="p-3.5 font-mono font-medium text-slate-900 dark:text-white">
                                            {{ st.nisn }}
                                        </td>
                                        <td class="p-3.5">
                                            <div class="flex items-center space-x-2.5">
                                                <img
                                                    v-if="st.foto"
                                                    :src="st.foto"
                                                    class="w-7 h-7 rounded-lg object-cover border border-slate-200 dark:border-slate-700 flex-shrink-0"
                                                />
                                                <div
                                                    v-else
                                                    class="w-7 h-7 rounded-lg bg-indigo-600 text-white flex items-center justify-center font-bold text-xs flex-shrink-0"
                                                >
                                                    {{ st.nama.charAt(0) }}
                                                </div>
                                                <span class="font-semibold text-slate-900 dark:text-white">
                                                    {{ st.nama }}
                                                </span>
                                            </div>
                                        </td>
                                        <td class="p-3.5 font-mono">
                                            <Badge v-if="st.rfid_uid" variant="emerald" class="text-[10px]">
                                                <RadioIcon class="w-3 h-3 mr-1" />
                                                {{ st.rfid_uid }}
                                            </Badge>
                                            <span v-else class="text-slate-400 italic">Belum Ada</span>
                                        </td>
                                        <td class="p-3.5">
                                            <Badge variant="emerald" class="capitalize text-[10px]">
                                                {{ st.status }}
                                            </Badge>
                                        </td>
                                        <td class="p-3.5 text-center">
                                            <span
                                                v-if="selectedStudentIds.includes(st.id)"
                                                :class="[
                                                    promotionAction === 'promote' ? 'text-indigo-600 dark:text-indigo-400' : 'text-amber-600 dark:text-amber-400',
                                                    'font-bold text-xs inline-flex items-center',
                                                ]"
                                            >
                                                <CheckCircle2 class="w-3.5 h-3.5 mr-1" />
                                                {{ promotionAction === 'promote' ? `Pindah ke ${currentTargetClass?.nama || 'Tujuan'}` : 'Luluskan' }}
                                            </span>
                                            <span v-else class="text-rose-500 dark:text-rose-400 font-medium text-[11px] inline-flex items-center">
                                                <AlertTriangle class="w-3.5 h-3.5 mr-1" />
                                                Tinggal di Kelas {{ currentSourceClass?.nama }}
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Sticky Execution Bottom Bar -->
                        <div class="p-4 bg-slate-50 dark:bg-slate-900/90 border-t border-slate-100 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                            <div class="text-xs text-slate-600 dark:text-slate-400">
                                <div v-if="promotionAction === 'promote'">
                                    Akan memindahkan <strong>{{ selectedStudentIds.length }}</strong> siswa ke
                                    <strong class="text-indigo-600 dark:text-indigo-400">
                                        {{ currentTargetClass ? `Kelas ${currentTargetClass.nama}` : '[Pilih Kelas Tujuan]' }}
                                    </strong>
                                    <span v-if="unselectedStudentsCount > 0" class="text-rose-600 ml-2">
                                        ({{ unselectedStudentsCount }} siswa tidak dicentang tetap tinggal di Kelas {{ currentSourceClass?.nama }})
                                    </span>
                                </div>
                                <div v-else>
                                    Akan meluluskan <strong>{{ selectedStudentIds.length }}</strong> siswa dari
                                    <strong>Kelas {{ currentSourceClass?.nama }}</strong> dan melepaskan kartu RFID mereka.
                                </div>
                            </div>

                            <div>
                                <Button
                                    @click="openConfirmPromotion"
                                    :disabled="
                                        selectedStudentIds.length === 0 ||
                                        (promotionAction === 'promote' && !selectedTargetClassId)
                                    "
                                    size="sm"
                                    :class="[
                                        promotionAction === 'promote'
                                            ? 'bg-indigo-600 hover:bg-indigo-700 text-white'
                                            : 'bg-amber-600 hover:bg-amber-700 text-white',
                                        'font-bold shadow-sm',
                                    ]"
                                >
                                    <ArrowRightLeft class="w-4 h-4 mr-1.5" />
                                    <span>
                                        {{ promotionAction === 'promote' ? 'Proses Kenaikan Kelas' : 'Proses Kelulusan Siswa' }}
                                    </span>
                                </Button>
                            </div>
                        </div>
                    </Card>
                </div>
            </div>
        </div>

        <!-- MODAL: Tambah / Edit Kelas -->
        <Modal :show="showClassModal" @close="showClassModal = false" max-width="md">
            <div class="p-6">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3 mb-4">
                    <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center space-x-2">
                        <School class="w-5 h-5 text-indigo-600" />
                        <span>{{ editingClass ? 'Edit Data Kelas' : 'Tambah Kelas Baru' }}</span>
                    </h3>
                    <button @click="showClassModal = false" class="text-slate-400 hover:text-slate-600">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <form @submit.prevent="submitClass" class="space-y-4">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                Nama Kelas <span class="text-rose-500">*</span>
                            </label>
                            <Input
                                v-model="classForm.nama"
                                placeholder="Contoh: VII, VIII A, IX B"
                                required
                            />
                            <p v-if="classForm.errors.nama" class="text-xs text-rose-600 mt-1">
                                {{ classForm.errors.nama }}
                            </p>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                Tingkat / Grade <span class="text-rose-500">*</span>
                            </label>
                            <Input
                                type="number"
                                min="1"
                                max="13"
                                v-model="classForm.tingkat"
                                required
                            />
                            <p v-if="classForm.errors.tingkat" class="text-xs text-rose-600 mt-1">
                                {{ classForm.errors.tingkat }}
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                Urutan Tampil
                            </label>
                            <Input
                                type="number"
                                min="0"
                                v-model="classForm.urutan"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                Status Kelas
                            </label>
                            <div class="pt-2 flex items-center space-x-2">
                                <input
                                    type="checkbox"
                                    id="cls_active"
                                    v-model="classForm.is_active"
                                    class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer"
                                />
                                <label for="cls_active" class="text-xs font-medium text-slate-700 dark:text-slate-300 cursor-pointer">
                                    Kelas Aktif
                                </label>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Keterangan / Wali Kelas (Opsional)
                        </label>
                        <Input
                            v-model="classForm.keterangan"
                            placeholder="Contoh: Wali Kelas: Ibu Siti, Ruang 204"
                        />
                    </div>

                    <div class="flex justify-end space-x-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                        <Button @click="showClassModal = false" type="button" variant="outline" size="sm">Batal</Button>
                        <Button
                            type="submit"
                            :disabled="classForm.processing"
                            size="sm"
                            class="bg-indigo-600 hover:bg-indigo-700 text-white"
                        >
                            {{ classForm.processing ? 'Menyimpan...' : 'Simpan Kelas' }}
                        </Button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- MODAL: Konfirmasi Kenaikan / Kelulusan Siswa -->
        <Modal :show="showConfirmModal" @close="showConfirmModal = false" max-width="md">
            <div class="p-6">
                <div class="flex items-center space-x-3 text-amber-600 mb-3">
                    <div class="w-10 h-10 rounded-full bg-amber-100 dark:bg-amber-950/60 flex items-center justify-center flex-shrink-0">
                        <AlertTriangle class="w-5 h-5 text-amber-600" />
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">
                            Konfirmasi {{ promotionAction === 'promote' ? 'Kenaikan Kelas' : 'Kelulusan Siswa' }}
                        </h3>
                        <p class="text-xs text-slate-500">Periksa rincian sebelum memproses perubahan massal</p>
                    </div>
                </div>

                <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 space-y-2 text-xs mb-4">
                    <div class="flex justify-between">
                        <span class="text-slate-500">Kelas Asal:</span>
                        <strong class="text-slate-900 dark:text-white">Kelas {{ currentSourceClass?.nama }}</strong>
                    </div>
                    <div class="flex justify-between" v-if="promotionAction === 'promote'">
                        <span class="text-slate-500">Kelas Tujuan:</span>
                        <strong class="text-indigo-600 dark:text-indigo-400">Kelas {{ currentTargetClass?.nama }}</strong>
                    </div>
                    <div class="flex justify-between" v-else>
                        <span class="text-slate-500">Aksi:</span>
                        <strong class="text-amber-600">Luluskan Siswa (Status Lulus)</strong>
                    </div>
                    <div class="flex justify-between border-t border-slate-200 dark:border-slate-700 pt-2">
                        <span class="text-slate-500">Jumlah Siswa Dipindahkan:</span>
                        <strong class="text-slate-900 dark:text-white font-mono">{{ selectedStudentIds.length }} Siswa</strong>
                    </div>
                    <div class="flex justify-between" v-if="unselectedStudentsCount > 0">
                        <span class="text-slate-500">Siswa Tinggal di Kelas Asal:</span>
                        <strong class="text-rose-600 font-mono">{{ unselectedStudentsCount }} Siswa</strong>
                    </div>
                    <div class="flex justify-between" v-if="promotionAction === 'graduate'">
                        <span class="text-slate-500">Status Kartu RFID:</span>
                        <strong class="text-rose-600">Otomatis Dilepas (Unpaired)</strong>
                    </div>
                </div>

                <div class="flex justify-end space-x-2 pt-2">
                    <Button @click="showConfirmModal = false" type="button" variant="outline" size="sm">Batal</Button>
                    <Button
                        @click="executePromotion"
                        :disabled="isSubmittingPromotion"
                        size="sm"
                        :class="[
                            promotionAction === 'promote' ? 'bg-indigo-600 hover:bg-indigo-700' : 'bg-amber-600 hover:bg-amber-700',
                            'text-white font-bold',
                        ]"
                    >
                        {{ isSubmittingPromotion ? 'Memproses...' : 'Ya, Eksekusi Sekarang' }}
                    </Button>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
