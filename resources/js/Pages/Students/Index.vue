<script setup>
import { ref, watch } from "vue";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link, router, useForm } from "@inertiajs/vue3";
import Card from "@/Components/ui/Card.vue";
import CardContent from "@/Components/ui/CardContent.vue";
import Badge from "@/Components/ui/Badge.vue";
import Button from "@/Components/ui/Button.vue";
import Input from "@/Components/ui/Input.vue";
import Modal from "@/Components/Modal.vue";
import {
    Users,
    Search,
    Plus,
    Upload,
    Download,
    CreditCard,
    Edit2,
    Trash2,
    CheckCircle2,
    AlertCircle,
    X,
    Filter,
    Radio,
} from "lucide-vue-next";
import axios from "axios";

const props = defineProps({
    students: Object,
    filters: Object,
    classes: Array,
    stats: Object,
});

const search = ref(props.filters.search || "");
const selectedClass = ref(props.filters.kelas || "Semua");
const rfidStatus = ref(props.filters.rfid_status || "all");

// Apply search and filter
let searchTimer = null;
const applyFilters = () => {
    router.get(
        route("students.index"),
        {
            search: search.value || undefined,
            kelas: selectedClass.value,
            rfid_status: rfidStatus.value,
        },
        { preserveState: true, replace: true },
    );
};

watch(search, () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(applyFilters, 350);
});

const setClass = (c) => {
    selectedClass.value = c;
    applyFilters();
};

const setRfidStatus = (status) => {
    rfidStatus.value = status;
    applyFilters();
};

// Student Add / Edit Modal
const showStudentModal = ref(false);
const editingStudent = ref(null);
const photoPreview = ref(null);

const studentForm = useForm({
    nisn: "",
    nama: "",
    kelas: props.classes && props.classes.length > 0 ? props.classes[0] : "VII",
    rfid_uid: "",
    jenis_kelamin: "L",
    status: "aktif",
    no_telepon: "",
    foto: null,
});

const handlePhotoChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        studentForm.foto = file;
        const reader = new FileReader();
        reader.onload = (e) => {
            photoPreview.value = e.target.result;
        };
        reader.readAsDataURL(file);
    }
};

const openAddStudent = () => {
    editingStudent.value = null;
    studentForm.reset();
    studentForm.clearErrors();
    studentForm.foto = null;
    photoPreview.value = null;
    studentForm.kelas =
        selectedClass.value !== "Semua" ? selectedClass.value : "VII";
    showStudentModal.value = true;
};

const openEditStudent = (student) => {
    editingStudent.value = student;
    studentForm.clearErrors();
    studentForm.nisn = student.nisn;
    studentForm.nama = student.nama;
    studentForm.kelas = student.kelas;
    studentForm.rfid_uid = student.rfid_uid || "";
    studentForm.jenis_kelamin = student.jenis_kelamin || "L";
    studentForm.status = student.status;
    studentForm.no_telepon = student.no_telepon || "";
    studentForm.foto = null;
    photoPreview.value = student.foto || null;
    showStudentModal.value = true;
};

const submitStudent = () => {
    if (editingStudent.value) {
        studentForm
            .transform((data) => ({
                ...data,
                _method: "PUT",
            }))
            .post(route("students.update", editingStudent.value.id), {
                forceFormData: true,
                onSuccess: () => {
                    showStudentModal.value = false;
                    photoPreview.value = null;
                },
            });
    } else {
        studentForm.post(route("students.store"), {
            forceFormData: true,
            onSuccess: () => {
                showStudentModal.value = false;
                photoPreview.value = null;
            },
        });
    }
};

const deleteStudent = (student) => {
    if (
        confirm(
            `Yakin ingin menghapus data siswa "${student.nama}"? Seluruh riwayat absensi siswa ini juga akan terhapus.`,
        )
    ) {
        router.delete(route("students.destroy", student.id));
    }
};

// Quick RFID Pairing Modal
const showPairModal = ref(false);
const targetStudent = ref(null);
const pairRfidInput = ref("");
const pairError = ref("");
const pairSuccess = ref("");
const pairLoading = ref(false);

const openPairModal = (student) => {
    targetStudent.value = student;
    pairRfidInput.value = student.rfid_uid || "";
    pairError.value = "";
    pairSuccess.value = "";
    showPairModal.value = true;
};

const submitPairRfid = async () => {
    if (!pairRfidInput.value.trim()) {
        pairError.value = "Silakan ketik atau tempelkan kartu RFID!";
        return;
    }

    pairLoading.value = true;
    pairError.value = "";
    pairSuccess.value = "";

    try {
        const res = await axios.post(route("students.pair-rfid"), {
            student_id: targetStudent.value.id,
            rfid_uid: pairRfidInput.value.trim(),
        });

        if (res.data.success) {
            pairSuccess.value = res.data.message;
            targetStudent.value.rfid_uid = pairRfidInput.value.trim();
            setTimeout(() => {
                showPairModal.value = false;
                router.reload({ only: ["students", "stats"] });
            }, 800);
        }
    } catch (err) {
        pairError.value =
            err.response?.data?.message || "Gagal memasangkan kartu RFID.";
    } finally {
        pairLoading.value = false;
    }
};

const unpairRfid = (student) => {
    if (confirm(`Lepaskan kartu RFID dari siswa "${student.nama}"?`)) {
        router.post(route("students.unpair-rfid", student.id));
    }
};

// Excel Import Modal
const showImportModal = ref(false);
const importForm = useForm({
    file: null,
});

const submitImport = () => {
    if (!importForm.file) return;
    importForm.post(route("students.import"), {
        onSuccess: () => {
            showImportModal.value = false;
            importForm.reset();
        },
    });
};
</script>

<template>
    <Head title="Data Siswa" />

    <AuthenticatedLayout>
        <template #header>
            <div
                class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4"
            >
                <div>
                    <h2
                        class="text-xl font-bold leading-tight text-slate-800 dark:text-slate-100 flex items-center space-x-2"
                    >
                        <Users class="w-5 h-5 text-indigo-600" />
                        <span>Data Master Siswa</span>
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Kelola data murid, kelas, dan pemasangan tag RFID
                    </p>
                </div>
                <div class="flex items-center space-x-2">
                    <Button
                        @click="showImportModal = true"
                        variant="outline"
                        size="sm"
                    >
                        <Upload class="w-4 h-4 mr-1.5 text-slate-500" />
                        <span>Import Excel</span>
                    </Button>
                    <a
                        :href="route('students.export')"
                        target="_blank"
                        class="inline-flex items-center h-8 rounded-md px-3 text-xs border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200"
                    >
                        <Download class="w-4 h-4 mr-1.5 text-slate-500" />
                        <span>Export Excel</span>
                    </a>
                    <Button
                        @click="openAddStudent"
                        size="sm"
                        class="bg-indigo-600 hover:bg-indigo-700 text-white"
                    >
                        <Plus class="w-4 h-4 mr-1.5" />
                        <span>Tambah Siswa</span>
                    </Button>
                </div>
            </div>
        </template>

        <div class="space-y-4">
            <!-- Filter Bar: Classes & Search -->
            <Card>
                <CardContent class="p-4 space-y-3">
                    <!-- Class Filter Pills -->
                    <div
                        class="flex flex-wrap items-center gap-1.5 border-b border-slate-100 dark:border-slate-800 pb-3"
                    >
                        <span
                            class="text-xs font-semibold text-slate-400 mr-2 flex items-center"
                        >
                            <Filter class="w-3.5 h-3.5 mr-1" />
                            Kelas:
                        </span>
                        <button
                            @click="setClass('Semua')"
                            :class="[
                                selectedClass === 'Semua'
                                    ? 'bg-indigo-600 text-white font-semibold'
                                    : 'bg-slate-100 text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300',
                                'px-3 py-1 rounded-md text-xs transition cursor-pointer',
                            ]"
                        >
                            Semua Kelas ({{ stats.total }})
                        </button>
                        <button
                            v-for="c in classes"
                            :key="c"
                            @click="setClass(c)"
                            :class="[
                                selectedClass === c
                                    ? 'bg-indigo-600 text-white font-semibold'
                                    : 'bg-slate-100 text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300',
                                'px-3 py-1 rounded-md text-xs transition cursor-pointer',
                            ]"
                        >
                            {{ c }}
                        </button>
                    </div>

                    <!-- Search and Status Pills -->
                    <div
                        class="flex flex-col sm:flex-row sm:items-center justify-between gap-3"
                    >
                        <div class="relative flex-1 max-w-md">
                            <Search
                                class="w-4 h-4 text-slate-400 absolute left-3 top-2.5"
                            />
                            <Input
                                v-model="search"
                                placeholder="Cari berdasarkan nama siswa, NISN, atau UID RFID..."
                                class="pl-9 text-xs"
                            />
                        </div>

                        <div class="flex items-center space-x-2 text-xs">
                            <span class="text-slate-400">Filter RFID:</span>
                            <button
                                @click="setRfidStatus('all')"
                                :class="
                                    rfidStatus === 'all'
                                        ? 'font-bold text-indigo-600 underline'
                                        : 'text-slate-600 dark:text-slate-400 hover:text-slate-900'
                                "
                            >
                                Semua
                            </button>
                            <span>•</span>
                            <button
                                @click="setRfidStatus('yes')"
                                :class="
                                    rfidStatus === 'yes'
                                        ? 'font-bold text-emerald-600 underline'
                                        : 'text-slate-600 dark:text-slate-400 hover:text-slate-900'
                                "
                            >
                                Punya RFID ({{ stats.with_rfid }})
                            </button>
                            <span>•</span>
                            <button
                                @click="setRfidStatus('no')"
                                :class="
                                    rfidStatus === 'no'
                                        ? 'font-bold text-amber-600 underline'
                                        : 'text-slate-600 dark:text-slate-400 hover:text-slate-900'
                                "
                            >
                                Belum Terpasang ({{ stats.without_rfid }})
                            </button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Students DataTable -->
            <Card>
                <div class="overflow-x-auto">
                    <table
                        class="w-full text-left text-xs text-slate-600 dark:text-slate-300"
                    >
                        <thead
                            class="bg-slate-100/75 dark:bg-slate-800/60 uppercase font-semibold text-slate-700 dark:text-slate-200 border-b border-slate-200 dark:border-slate-800"
                        >
                            <tr>
                                <th class="p-3.5 w-12 text-center">No</th>
                                <th class="p-3.5">NISN</th>
                                <th class="p-3.5">Nama Siswa</th>
                                <th class="p-3.5">Kelas</th>
                                <th class="p-3.5">Kartu RFID UID</th>
                                <th class="p-3.5">Status</th>
                                <th class="p-3.5 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody
                            class="divide-y divide-slate-100 dark:divide-slate-800"
                        >
                            <tr v-if="students.data.length === 0">
                                <td
                                    colspan="7"
                                    class="p-8 text-center text-slate-400"
                                >
                                    Tidak ada data siswa ditemukan untuk
                                    kriteria pencarian ini.
                                </td>
                            </tr>
                            <tr
                                v-for="(student, index) in students.data"
                                :key="student.id"
                                class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors"
                            >
                                <td
                                    class="p-3.5 text-center font-mono text-slate-400"
                                >
                                    {{
                                        (students.current_page - 1) *
                                            students.per_page +
                                        index +
                                        1
                                    }}
                                </td>
                                <td
                                    class="p-3.5 font-mono font-medium text-slate-900 dark:text-white"
                                >
                                    {{ student.nisn }}
                                </td>
                                <td
                                    class="p-3.5 font-semibold text-slate-900 dark:text-white"
                                >
                                    <div class="flex items-center space-x-2.5">
                                        <img
                                            v-if="student.foto"
                                            :src="student.foto"
                                            :alt="student.nama"
                                            class="w-8 h-8 rounded-lg object-cover border border-slate-200 dark:border-slate-700 flex-shrink-0"
                                        />
                                        <div
                                            v-else
                                            class="w-8 h-8 rounded-lg bg-indigo-600 text-white flex items-center justify-center font-bold text-xs flex-shrink-0"
                                        >
                                            {{ student.nama.charAt(0) }}
                                        </div>
                                        <span>{{ student.nama }}</span>
                                    </div>
                                </td>
                                <td class="p-3.5">
                                    <Badge variant="secondary"
                                        >Kelas {{ student.kelas }}</Badge
                                    >
                                </td>
                                <td class="p-3.5">
                                    <div
                                        v-if="student.rfid_uid"
                                        class="flex items-center space-x-2"
                                    >
                                        <Badge
                                            variant="emerald"
                                            class="font-mono"
                                        >
                                            <Radio
                                                class="w-3 h-3 mr-1 text-emerald-600"
                                            />
                                            {{ student.rfid_uid }}
                                        </Badge>
                                        <button
                                            @click="unpairRfid(student)"
                                            title="Lepas Kartu"
                                            class="text-slate-400 hover:text-rose-500 p-0.5 rounded cursor-pointer"
                                        >
                                            <X class="w-3 h-3" />
                                        </button>
                                    </div>
                                    <button
                                        v-else
                                        @click="openPairModal(student)"
                                        class="inline-flex items-center text-[11px] font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 hover:underline cursor-pointer"
                                    >
                                        <CreditCard class="w-3.5 h-3.5 mr-1" />
                                        + Pasangkan RFID
                                    </button>
                                </td>
                                <td class="p-3.5">
                                    <Badge
                                        :variant="
                                            student.status === 'aktif'
                                                ? 'emerald'
                                                : 'secondary'
                                        "
                                    >
                                        {{ student.status }}
                                    </Badge>
                                </td>
                                <td class="p-3.5 text-right space-x-1">
                                    <button
                                        @click="openPairModal(student)"
                                        title="Ubah Kartu RFID"
                                        class="p-1.5 rounded-md hover:bg-slate-100 dark:hover:bg-slate-800 text-indigo-600 cursor-pointer"
                                    >
                                        <CreditCard class="w-4 h-4" />
                                    </button>
                                    <button
                                        @click="openEditStudent(student)"
                                        title="Edit Siswa"
                                        class="p-1.5 rounded-md hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-300 cursor-pointer"
                                    >
                                        <Edit2 class="w-4 h-4" />
                                    </button>
                                    <button
                                        @click="deleteStudent(student)"
                                        title="Hapus Siswa"
                                        class="p-1.5 rounded-md hover:bg-slate-100 dark:hover:bg-slate-800 text-rose-600 cursor-pointer"
                                    >
                                        <Trash2 class="w-4 h-4" />
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Footer -->
                <div
                    v-if="students.links && students.links.length > 3"
                    class="p-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs text-slate-500"
                >
                    <div>
                        Menampilkan {{ students.from ?? 0 }} -
                        {{ students.to ?? 0 }} dari total
                        {{ students.total }} siswa
                    </div>
                    <div class="flex items-center space-x-1">
                        <Link
                            v-for="link in students.links"
                            :key="link.label"
                            :href="link.url || '#'"
                            :class="[
                                link.active
                                    ? 'bg-indigo-600 text-white font-bold'
                                    : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800',
                                !link.url
                                    ? 'opacity-40 pointer-events-none'
                                    : '',
                                'px-2.5 py-1 rounded-md text-xs transition',
                            ]"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </Card>
        </div>

        <!-- MODAL: Quick Pair RFID Tag -->
        <Modal
            :show="showPairModal"
            @close="showPairModal = false"
            max-width="md"
        >
            <div class="p-6">
                <div
                    class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3 mb-4"
                >
                    <h3
                        class="text-base font-bold text-slate-900 dark:text-white flex items-center space-x-2"
                    >
                        <Radio class="w-5 h-5 text-indigo-600 animate-pulse" />
                        <span>Pasangkan Tag RFID Siswa</span>
                    </h3>
                    <button
                        @click="showPairModal = false"
                        class="text-slate-400 hover:text-slate-600"
                    >
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <div
                    v-if="targetStudent"
                    class="mb-4 p-3 rounded-lg bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 flex items-center space-x-3"
                >
                    <img
                        v-if="targetStudent.foto"
                        :src="targetStudent.foto"
                        class="w-12 h-12 rounded-xl object-cover border border-slate-300 dark:border-slate-600 flex-shrink-0"
                    />
                    <div
                        v-else
                        class="w-12 h-12 rounded-xl bg-indigo-600 text-white flex items-center justify-center font-bold text-base flex-shrink-0"
                    >
                        {{ targetStudent.nama.charAt(0) }}
                    </div>
                    <div class="min-w-0">
                        <h4
                            class="font-bold text-sm text-slate-900 dark:text-white truncate"
                        >
                            {{ targetStudent.nama }}
                        </h4>
                        <p class="text-xs text-slate-500">
                            NISN: {{ targetStudent.nisn }} • Kelas
                            {{ targetStudent.kelas }}
                        </p>
                    </div>
                </div>

                <div class="space-y-4">
                    <div>
                        <label
                            class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1"
                        >
                            Tempelkan Kartu RFID pada Reader atau Ketik UID:
                        </label>
                        <Input
                            v-model="pairRfidInput"
                            placeholder="Contoh: 1000000001 atau tap kartu sekarang..."
                            autofocus
                            @keyup.enter="submitPairRfid"
                            class="font-mono text-sm py-2"
                        />
                        <p class="text-[11px] text-slate-500 mt-1">
                            Alat RFID reader USB otomatis mengetikkan nomor
                            kartu dan menekan tombol Enter.
                        </p>
                    </div>

                    <div
                        v-if="pairError"
                        class="p-2.5 rounded-md bg-rose-50 border border-rose-200 text-rose-700 text-xs flex items-center space-x-2"
                    >
                        <AlertCircle class="w-4 h-4 flex-shrink-0" />
                        <span>{{ pairError }}</span>
                    </div>

                    <div
                        v-if="pairSuccess"
                        class="p-2.5 rounded-md bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs flex items-center space-x-2"
                    >
                        <CheckCircle2 class="w-4 h-4 flex-shrink-0" />
                        <span>{{ pairSuccess }}</span>
                    </div>

                    <div class="flex justify-end space-x-2 pt-2">
                        <Button
                            @click="showPairModal = false"
                            variant="outline"
                            size="sm"
                            >Batal</Button
                        >
                        <Button
                            @click="submitPairRfid"
                            :disabled="pairLoading"
                            size="sm"
                            class="bg-indigo-600 hover:bg-indigo-700 text-white"
                        >
                            {{ pairLoading ? "Menyimpan..." : "Simpan Kartu" }}
                        </Button>
                    </div>
                </div>
            </div>
        </Modal>

        <!-- MODAL: Tambah / Edit Siswa -->
        <Modal
            :show="showStudentModal"
            @close="showStudentModal = false"
            max-width="lg"
        >
            <div class="p-6">
                <div
                    class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3 mb-4"
                >
                    <h3
                        class="text-base font-bold text-slate-900 dark:text-white"
                    >
                        {{
                            editingStudent
                                ? "Edit Data Siswa"
                                : "Tambah Siswa Baru"
                        }}
                    </h3>
                    <button
                        @click="showStudentModal = false"
                        class="text-slate-400 hover:text-slate-600"
                    >
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <form @submit.prevent="submitStudent" class="space-y-4">
                    <!-- Upload & Preview Foto Siswa -->
                    <div>
                        <label
                            class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5"
                        >
                            Foto Siswa (Tampil di Kiosk Scan Absen)
                        </label>
                        <div
                            class="flex items-center space-x-4 p-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50"
                        >
                            <div
                                class="relative w-16 h-16 rounded-xl overflow-hidden border-2 border-slate-300 dark:border-slate-600 flex-shrink-0 bg-white dark:bg-slate-800 flex items-center justify-center shadow-xs"
                            >
                                <img
                                    v-if="photoPreview"
                                    :src="photoPreview"
                                    alt="Foto Siswa"
                                    class="w-full h-full object-cover"
                                />
                                <Users v-else class="w-8 h-8 text-slate-400" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <input
                                    type="file"
                                    id="student_foto_upload"
                                    accept="image/*"
                                    @change="handlePhotoChange"
                                    class="hidden"
                                />
                                <label
                                    for="student_foto_upload"
                                    class="inline-flex items-center px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700 cursor-pointer shadow-xs transition"
                                >
                                    <Upload class="w-3.5 h-3.5 mr-1.5 text-indigo-600" />
                                    <span>{{ photoPreview ? "Ganti Foto Siswa" : "Pilih File Foto..." }}</span>
                                </label>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 truncate">
                                    Format: JPG, PNG, WEBP, atau SVG (Maksimal 4 MB)
                                </p>
                            </div>
                        </div>
                        <p v-if="studentForm.errors.foto" class="text-xs text-rose-600 mt-1">
                            {{ studentForm.errors.foto }}
                        </p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label
                                class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1"
                            >
                                NISN <span class="text-rose-500">*</span>
                            </label>
                            <Input
                                v-model="studentForm.nisn"
                                placeholder="10 digit NISN..."
                                required
                            />
                            <p
                                v-if="studentForm.errors.nisn"
                                class="text-xs text-rose-600 mt-1"
                            >
                                {{ studentForm.errors.nisn }}
                            </p>
                        </div>
                        <div>
                            <label
                                class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1"
                            >
                                Kelas <span class="text-rose-500">*</span>
                            </label>
                            <select
                                v-model="studentForm.kelas"
                                class="w-full h-10 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 px-3 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                                required
                            >
                                <option v-for="c in classes" :key="c" :value="c">
                                    {{ c }}
                                </option>
                            </select>
                            <p
                                v-if="studentForm.errors.kelas"
                                class="text-xs text-rose-600 mt-1"
                            >
                                {{ studentForm.errors.kelas }}
                            </p>
                        </div>
                    </div>

                    <div>
                        <label
                            class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1"
                        >
                            Nama Lengkap Siswa
                            <span class="text-rose-500">*</span>
                        </label>
                        <Input
                            v-model="studentForm.nama"
                            placeholder="Nama lengkap siswa..."
                            required
                        />
                        <p
                            v-if="studentForm.errors.nama"
                            class="text-xs text-rose-600 mt-1"
                        >
                            {{ studentForm.errors.nama }}
                        </p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label
                                class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1"
                            >
                                UID Kartu RFID (Opsional)
                            </label>
                            <Input
                                v-model="studentForm.rfid_uid"
                                placeholder="Tempel kartu atau ketik UID..."
                                class="font-mono text-xs"
                            />
                            <p
                                v-if="studentForm.errors.rfid_uid"
                                class="text-xs text-rose-600 mt-1"
                            >
                                {{ studentForm.errors.rfid_uid }}
                            </p>
                        </div>
                        <div>
                            <label
                                class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1"
                            >
                                Jenis Kelamin
                            </label>
                            <select
                                v-model="studentForm.jenis_kelamin"
                                class="w-full h-10 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 px-3 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                            >
                                <option value="L">Laki-laki (L)</option>
                                <option value="P">Perempuan (P)</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label
                                class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1"
                            >
                                Nomor Telepon / WA Ortu
                            </label>
                            <Input
                                v-model="studentForm.no_telepon"
                                placeholder="08xxxxxxxxxx"
                            />
                        </div>
                        <div>
                            <label
                                class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1"
                            >
                                Status Siswa
                            </label>
                            <select
                                v-model="studentForm.status"
                                class="w-full h-10 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 px-3 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                            >
                                <option value="aktif">Aktif</option>
                                <option value="nonaktif">Non-Aktif</option>
                                <option value="lulus">Lulus / Alumni</option>
                            </select>
                        </div>
                    </div>

                    <div
                        class="flex justify-end space-x-2 pt-3 border-t border-slate-100 dark:border-slate-800"
                    >
                        <Button
                            @click="showStudentModal = false"
                            type="button"
                            variant="outline"
                            size="sm"
                            >Batal</Button
                        >
                        <Button
                            type="submit"
                            :disabled="studentForm.processing"
                            size="sm"
                            class="bg-indigo-600 hover:bg-indigo-700 text-white"
                        >
                            {{
                                studentForm.processing
                                    ? "Menyimpan..."
                                    : "Simpan Data"
                            }}
                        </Button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- MODAL: Import Excel Siswa -->
        <Modal
            :show="showImportModal"
            @close="showImportModal = false"
            max-width="md"
        >
            <div class="p-6">
                <div
                    class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3 mb-4"
                >
                    <h3
                        class="text-base font-bold text-slate-900 dark:text-white flex items-center space-x-2"
                    >
                        <Upload class="w-5 h-5 text-indigo-600" />
                        <span>Import Siswa dari Excel (.xlsx)</span>
                    </h3>
                    <button
                        @click="showImportModal = false"
                        class="text-slate-400 hover:text-slate-600"
                    >
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <form @submit.prevent="submitImport" class="space-y-4">
                    <div
                        class="p-4 border-2 border-dashed border-slate-200 dark:border-slate-700 rounded-xl text-center hover:border-indigo-500 transition-colors"
                    >
                        <Upload class="w-8 h-8 mx-auto text-slate-400 mb-2" />
                        <label
                            class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1 cursor-pointer"
                        >
                            <span>Pilih file Excel (.xlsx / .csv)</span>
                            <input
                                type="file"
                                @change="
                                    importForm.file = $event.target.files[0]
                                "
                                accept=".xlsx, .xls, .csv"
                                class="hidden"
                                required
                            />
                        </label>
                        <p
                            v-if="importForm.file"
                            class="text-xs font-medium text-indigo-600 mt-2"
                        >
                            File terpilih: {{ importForm.file.name }}
                        </p>
                        <p v-else class="text-[11px] text-slate-400 mt-1">
                            Format kolom: NO, NISN, NAMA, KELAS (sama persis
                            dengan file Data Semua Siswa Absensi).
                        </p>
                    </div>

                    <div
                        v-if="importForm.errors.file"
                        class="text-xs text-rose-600"
                    >
                        {{ importForm.errors.file }}
                    </div>

                    <div class="flex justify-end space-x-2 pt-2">
                        <Button
                            @click="showImportModal = false"
                            type="button"
                            variant="outline"
                            size="sm"
                            >Batal</Button
                        >
                        <Button
                            type="submit"
                            :disabled="
                                !importForm.file || importForm.processing
                            "
                            size="sm"
                            class="bg-indigo-600 hover:bg-indigo-700 text-white"
                        >
                            {{
                                importForm.processing
                                    ? "Mengunggah..."
                                    : "Import Sekarang"
                            }}
                        </Button>
                    </div>
                </form>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
