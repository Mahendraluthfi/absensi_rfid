<script setup>
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import Card from '@/Components/ui/Card.vue';
import CardHeader from '@/Components/ui/CardHeader.vue';
import CardTitle from '@/Components/ui/CardTitle.vue';
import CardContent from '@/Components/ui/CardContent.vue';
import Badge from '@/Components/ui/Badge.vue';
import Button from '@/Components/ui/Button.vue';
import Input from '@/Components/ui/Input.vue';
import Modal from '@/Components/Modal.vue';
import {
    Tags,
    Plus,
    Clock,
    School,
    Edit2,
    Trash2,
    CheckCircle,
    Info,
    X,
    Save,
} from 'lucide-vue-next';

const props = defineProps({
    categories: Array,
    settings: Object,
});

const activeTab = ref('categories'); // 'categories' or 'settings'

// Category Add / Edit Modal
const showCategoryModal = ref(false);
const editingCategory = ref(null);

const categoryForm = useForm({
    kode: '',
    nama: '',
    tipe: 'masuk',
    jam_mulai: '',
    jam_selesai: '',
    toleransi_terlambat: '',
    warna: 'emerald',
    is_active: true,
});

const openAddCategory = () => {
    editingCategory.value = null;
    categoryForm.reset();
    categoryForm.clearErrors();
    categoryForm.warna = 'emerald';
    categoryForm.is_active = true;
    showCategoryModal.value = true;
};

const openEditCategory = (cat) => {
    editingCategory.value = cat;
    categoryForm.clearErrors();
    categoryForm.kode = cat.kode;
    categoryForm.nama = cat.nama;
    categoryForm.tipe = cat.tipe;
    categoryForm.jam_mulai = cat.jam_mulai ? cat.jam_mulai.substring(0, 5) : '';
    categoryForm.jam_selesai = cat.jam_selesai ? cat.jam_selesai.substring(0, 5) : '';
    categoryForm.toleransi_terlambat = cat.toleransi_terlambat ? cat.toleransi_terlambat.substring(0, 5) : '';
    categoryForm.warna = cat.warna || 'emerald';
    categoryForm.is_active = cat.is_active;
    showCategoryModal.value = true;
};

const submitCategory = () => {
    if (editingCategory.value) {
        categoryForm.put(route('categories.update', editingCategory.value.id), {
            onSuccess: () => {
                showCategoryModal.value = false;
            },
        });
    } else {
        categoryForm.post(route('categories.store'), {
            onSuccess: () => {
                showCategoryModal.value = false;
            },
        });
    }
};

const deleteCategory = (cat) => {
    if (confirm(`Yakin ingin menghapus kategori "${cat.nama}"?`)) {
        router.delete(route('categories.destroy', cat.id));
    }
};

const toggleCategory = (cat) => {
    router.post(route('categories.toggle', cat.id));
};

// School Settings Form
const settingsForm = useForm({
    nama_sekolah: props.settings.nama_sekolah || '',
    alamat: props.settings.alamat || '',
    jam_masuk_mulai: props.settings.jam_masuk_mulai ? props.settings.jam_masuk_mulai.substring(0, 5) : '06:00',
    jam_masuk_batas: props.settings.jam_masuk_batas ? props.settings.jam_masuk_batas.substring(0, 5) : '07:00',
    toleransi_terlambat_batas: props.settings.toleransi_terlambat_batas ? props.settings.toleransi_terlambat_batas.substring(0, 5) : '08:30',
    jam_pulang_mulai: props.settings.jam_pulang_mulai ? props.settings.jam_pulang_mulai.substring(0, 5) : '14:00',
    jam_pulang_batas: props.settings.jam_pulang_batas ? props.settings.jam_pulang_batas.substring(0, 5) : '17:00',
});

const saveSettings = () => {
    settingsForm.post(route('settings.school'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Kategori Absensi & Pengaturan" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="text-xl font-bold leading-tight text-slate-800 dark:text-slate-100 flex items-center space-x-2">
                        <Tags class="w-5 h-5 text-indigo-600" />
                        <span>Kategori Absensi & Aturan Waktu</span>
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">Kelola status kehadiran dan rentang jam operasional sekolah</p>
                </div>

                <div v-if="activeTab === 'categories'">
                    <Button @click="openAddCategory" size="sm" class="bg-indigo-600 hover:bg-indigo-700 text-white">
                        <Plus class="w-4 h-4 mr-1.5" />
                        <span>Tambah Kategori</span>
                    </Button>
                </div>
            </div>
        </template>

        <div class="space-y-4">
            <!-- Tabs Navigation -->
            <div class="flex items-center space-x-2 border-b border-slate-200 dark:border-slate-800">
                <button
                    @click="activeTab = 'categories'"
                    :class="[
                        activeTab === 'categories'
                            ? 'border-indigo-600 text-indigo-600 font-bold'
                            : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400',
                        'px-4 py-2.5 border-b-2 text-sm flex items-center space-x-2 transition cursor-pointer',
                    ]"
                >
                    <Tags class="w-4 h-4" />
                    <span>Daftar Kategori Absensi ({{ categories.length }})</span>
                </button>
                <button
                    @click="activeTab = 'settings'"
                    :class="[
                        activeTab === 'settings'
                            ? 'border-indigo-600 text-indigo-600 font-bold'
                            : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400',
                        'px-4 py-2.5 border-b-2 text-sm flex items-center space-x-2 transition cursor-pointer',
                    ]"
                >
                    <Clock class="w-4 h-4" />
                    <span>Jam Operasional & Toleransi Sekolah</span>
                </button>
            </div>

            <!-- TAB 1: Kategori Absensi -->
            <div v-if="activeTab === 'categories'" class="space-y-4">
                <!-- Info Alert: Late Explanation -->
                <div class="p-3.5 rounded-xl bg-indigo-50 border border-indigo-200 dark:bg-indigo-950/40 dark:border-indigo-800 text-indigo-900 dark:text-indigo-200 text-xs flex items-start space-x-3">
                    <Info class="w-4 h-4 text-indigo-600 dark:text-indigo-400 mt-0.5 flex-shrink-0" />
                    <div class="leading-relaxed">
                        <strong>Aturan Jam Selesai & Status Keterlambatan:</strong>
                        Pada kategori tipe Masuk, jika siswa tap kartu melebihi <strong>jam_selesai</strong> (contoh: melewati batas 07:00), sistem otomatis mencatat siswa tersebut berstatus <strong>Terlambat</strong> dan menghitung selisih menit keterlambatannya secara otomatis ke dalam laporan.
                    </div>
                </div>

                <Card>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs text-slate-600 dark:text-slate-300">
                            <thead class="bg-slate-100/75 dark:bg-slate-800/60 uppercase font-semibold text-slate-700 dark:text-slate-200 border-b border-slate-200 dark:border-slate-800">
                                <tr>
                                    <th class="p-3.5">Kode</th>
                                    <th class="p-3.5">Nama Kategori</th>
                                    <th class="p-3.5">Tipe Sesi</th>
                                    <th class="p-3.5">Rentang Waktu (Mulai - Selesai)</th>
                                    <th class="p-3.5">Badge Warna</th>
                                    <th class="p-3.5">Status</th>
                                    <th class="p-3.5 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                <tr
                                    v-for="cat in categories"
                                    :key="cat.id"
                                    class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors"
                                >
                                    <td class="p-3.5 font-mono font-bold text-slate-900 dark:text-white">
                                        {{ cat.kode }}
                                    </td>
                                    <td class="p-3.5 font-semibold text-slate-900 dark:text-white">
                                        {{ cat.nama }}
                                    </td>
                                    <td class="p-3.5 capitalize">
                                        <Badge variant="outline">{{ cat.tipe }}</Badge>
                                    </td>
                                    <td class="p-3.5 font-mono">
                                        <div v-if="cat.jam_mulai && cat.jam_selesai" class="space-y-0.5">
                                            <span class="text-slate-900 dark:text-slate-100 font-bold block">
                                                {{ cat.jam_mulai.substring(0, 5) }} - {{ cat.jam_selesai.substring(0, 5) }} WIB
                                            </span>
                                            <span v-if="cat.toleransi_terlambat" class="text-[10px] text-amber-600 block">
                                                Toleransi s/d {{ cat.toleransi_terlambat.substring(0, 5) }} WIB
                                            </span>
                                        </div>
                                        <span v-else class="text-slate-400 italic">Fleksibel / Manual</span>
                                    </td>
                                    <td class="p-3.5">
                                        <Badge :variant="cat.warna">{{ cat.warna }}</Badge>
                                    </td>
                                    <td class="p-3.5">
                                        <button
                                            @click="toggleCategory(cat)"
                                            :class="[
                                                cat.is_active ? 'bg-emerald-500' : 'bg-slate-300 dark:bg-slate-700',
                                                'relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full transition-colors duration-200 ease-in-out focus:outline-none',
                                            ]"
                                        >
                                            <span
                                                :class="[
                                                    cat.is_active ? 'translate-x-4' : 'translate-x-0.5',
                                                    'pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out mt-0.5',
                                                ]"
                                            />
                                        </button>
                                    </td>
                                    <td class="p-3.5 text-right space-x-1">
                                        <button
                                            @click="openEditCategory(cat)"
                                            title="Edit Kategori"
                                            class="p-1.5 rounded-md hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-300 cursor-pointer"
                                        >
                                            <Edit2 class="w-4 h-4" />
                                        </button>
                                        <button
                                            @click="deleteCategory(cat)"
                                            title="Hapus Kategori"
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

            <!-- TAB 2: Pengaturan Jam Operasional Sekolah -->
            <div v-if="activeTab === 'settings'" class="max-w-2xl">
                <Card>
                    <CardHeader>
                        <CardTitle class="text-base flex items-center space-x-2">
                            <School class="w-5 h-5 text-indigo-600" />
                            <span>Pengaturan Jam Operasional Sekolah</span>
                        </CardTitle>
                        <p class="text-xs text-slate-500">
                            Konfigurasi jam buka gerbang masuk, batas toleransi keterlambatan, dan jam pulang.
                        </p>
                    </CardHeader>
                    <CardContent>
                        <form @submit.prevent="saveSettings" class="space-y-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                    Nama Sekolah / Instansi
                                </label>
                                <Input v-model="settingsForm.nama_sekolah" required />
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                    Alamat Sekolah
                                </label>
                                <Input v-model="settingsForm.alamat" placeholder="Jl. Raya Sekolah No. ..." />
                            </div>

                            <div class="border-t border-slate-100 dark:border-slate-800 pt-4">
                                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3">
                                    Aturan Sesi Masuk (Check-In)
                                </h4>
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                            Gerbang Buka
                                        </label>
                                        <Input type="time" v-model="settingsForm.jam_masuk_mulai" required />
                                        <span class="text-[10px] text-slate-400">Mulai terima tap</span>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                            Batas Tepat Waktu
                                        </label>
                                        <Input type="time" v-model="settingsForm.jam_masuk_batas" required />
                                        <span class="text-[10px] text-emerald-600 font-medium">Batas masuk aman</span>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                            Batas Akhir Terlambat
                                        </label>
                                        <Input type="time" v-model="settingsForm.toleransi_terlambat_batas" required />
                                        <span class="text-[10px] text-amber-600 font-medium">Lewat ini = Alpa</span>
                                    </div>
                                </div>
                            </div>

                            <div class="border-t border-slate-100 dark:border-slate-800 pt-4">
                                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3">
                                    Aturan Sesi Pulang (Check-Out)
                                </h4>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                            Jam Pulang Mulai
                                        </label>
                                        <Input type="time" v-model="settingsForm.jam_pulang_mulai" required />
                                        <span class="text-[10px] text-slate-400">Waktu bel pulang</span>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                            Jam Pulang Selesai
                                        </label>
                                        <Input type="time" v-model="settingsForm.jam_pulang_batas" required />
                                        <span class="text-[10px] text-slate-400">Batas akhir tap sore</span>
                                    </div>
                                </div>
                            </div>

                            <div class="flex justify-end pt-3">
                                <Button type="submit" :disabled="settingsForm.processing" class="bg-indigo-600 hover:bg-indigo-700 text-white">
                                    <Save class="w-4 h-4 mr-1.5" />
                                    <span>{{ settingsForm.processing ? 'Menyimpan...' : 'Simpan Pengaturan' }}</span>
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </div>

        <!-- MODAL: Tambah / Edit Kategori -->
        <Modal :show="showCategoryModal" @close="showCategoryModal = false" max-width="md">
            <div class="p-6">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3 mb-4">
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">
                        {{ editingCategory ? 'Edit Kategori Absensi' : 'Tambah Kategori Absensi' }}
                    </h3>
                    <button @click="showCategoryModal = false" class="text-slate-400 hover:text-slate-600">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <form @submit.prevent="submitCategory" class="space-y-4">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                Kode Kategori <span class="text-rose-500">*</span>
                            </label>
                            <Input v-model="categoryForm.kode" placeholder="HADIR / SAKIT..." required class="uppercase font-mono" />
                            <p v-if="categoryForm.errors.kode" class="text-xs text-rose-600 mt-1">{{ categoryForm.errors.kode }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                Tipe Sesi <span class="text-rose-500">*</span>
                            </label>
                            <select
                                v-model="categoryForm.tipe"
                                class="w-full h-10 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 px-3 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                                required
                            >
                                <option value="masuk">Masuk (Check-In)</option>
                                <option value="ibadah">Ibadah / Sholat</option>
                                <option value="pulang">Pulang (Check-Out)</option>
                                <option value="kegiatan">Ekstrakurikuler / Kegiatan</option>
                                <option value="izin_sakit">Izin / Sakit</option>
                                <option value="alpa">Alpa</option>
                                <option value="lainnya">Lainnya</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Nama Kategori / Sesi Absen <span class="text-rose-500">*</span>
                        </label>
                        <Input v-model="categoryForm.nama" placeholder="Contoh: Absen Sholat Dhuhur / Absen Pagi" required />
                        <p v-if="categoryForm.errors.nama" class="text-xs text-rose-600 mt-1">{{ categoryForm.errors.nama }}</p>
                    </div>

                    <div class="grid grid-cols-3 gap-2">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                Jam Mulai Sesi
                            </label>
                            <Input type="time" v-model="categoryForm.jam_mulai" class="font-mono text-xs" />
                            <span class="text-[10px] text-slate-400 mt-0.5 block">Buka tap</span>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                Batas Tepat Waktu
                            </label>
                            <Input type="time" v-model="categoryForm.jam_selesai" class="font-mono text-xs" />
                            <span class="text-[10px] text-emerald-600 mt-0.5 block">Lewat = Telat</span>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                Batas Akhir Sesi
                            </label>
                            <Input type="time" v-model="categoryForm.toleransi_terlambat" class="font-mono text-xs" />
                            <span class="text-[10px] text-amber-600 mt-0.5 block">Sesi ditutup</span>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Warna Badge Status
                        </label>
                        <div class="grid grid-cols-5 gap-2">
                            <label
                                v-for="color in ['emerald', 'amber', 'blue', 'rose', 'purple']"
                                :key="color"
                                :class="[
                                    categoryForm.warna === color ? 'ring-2 ring-indigo-500' : '',
                                    'p-2 rounded-lg border border-slate-200 dark:border-slate-700 text-center cursor-pointer capitalize text-xs',
                                ]"
                            >
                                <input type="radio" v-model="categoryForm.warna" :value="color" class="hidden" />
                                <Badge :variant="color">{{ color }}</Badge>
                            </label>
                        </div>
                    </div>

                    <div class="flex items-center space-x-2 pt-2">
                        <input
                            type="checkbox"
                            id="cat_active"
                            v-model="categoryForm.is_active"
                            class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                        />
                        <label for="cat_active" class="text-xs text-slate-700 dark:text-slate-300 font-medium">
                            Kategori Aktif (Bisa digunakan dalam presensi)
                        </label>
                    </div>

                    <div class="flex justify-end space-x-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                        <Button @click="showCategoryModal = false" type="button" variant="outline" size="sm">Batal</Button>
                        <Button type="submit" :disabled="categoryForm.processing" size="sm" class="bg-indigo-600 hover:bg-indigo-700 text-white">
                            {{ categoryForm.processing ? 'Menyimpan...' : 'Simpan Kategori' }}
                        </Button>
                    </div>
                </form>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>

