<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Radio, ShieldCheck, ArrowRight } from 'lucide-vue-next';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: 'admin@absensi.local',
    password: 'admin123',
    remember: true,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Login Administrator" />

        <div class="mb-6 text-center">
            <div class="w-12 h-12 rounded-2xl bg-indigo-600 mx-auto flex items-center justify-center text-white shadow-lg shadow-indigo-600/30 mb-3">
                <Radio class="w-6 h-6 animate-pulse" />
            </div>
            <h2 class="text-xl font-black text-slate-900 dark:text-white tracking-tight">
                ABSENSI SISWA RFID
            </h2>
            <p class="text-xs text-slate-500 mt-1">
                Silakan login untuk mengelola sistem presensi sekolah
            </p>
        </div>

        <div v-if="status" class="mb-4 text-xs font-medium text-emerald-600 bg-emerald-50 dark:bg-emerald-950/60 p-3 rounded-lg border border-emerald-200">
            {{ status }}
        </div>

        <!-- Demo Credentials Hint Box -->
        <div class="mb-5 p-3 rounded-xl bg-indigo-50/70 border border-indigo-200 dark:bg-indigo-950/40 dark:border-indigo-800 text-xs text-indigo-900 dark:text-indigo-200">
            <div class="flex items-center space-x-1.5 font-bold mb-1">
                <ShieldCheck class="w-4 h-4 text-indigo-600" />
                <span>Akun Administrator Default:</span>
            </div>
            <p class="font-mono text-[11px] text-slate-600 dark:text-slate-300">
                Email: <strong>admin@absensi.local</strong> • Pass: <strong>admin123</strong>
            </p>
        </div>

        <form @submit.prevent="submit" class="space-y-4">
            <div>
                <InputLabel for="email" value="Email Administrator" class="text-xs font-semibold" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full text-xs"
                    v-model="form.email"
                    required
                    autofocus
                    autocomplete="username"
                />

                <InputError class="mt-1" :message="form.errors.email" />
            </div>

            <div>
                <InputLabel for="password" value="Password" class="text-xs font-semibold" />

                <TextInput
                    id="password"
                    type="password"
                    class="mt-1 block w-full text-xs"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                />

                <InputError class="mt-1" :message="form.errors.password" />
            </div>

            <div class="flex items-center justify-between text-xs">
                <label class="flex items-center cursor-pointer">
                    <Checkbox name="remember" v-model:checked="form.remember" />
                    <span class="ms-2 text-slate-600 dark:text-slate-400">Ingat Saya</span>
                </label>
            </div>

            <div class="pt-2">
                <PrimaryButton
                    class="w-full justify-center py-2.5 bg-indigo-600 hover:bg-indigo-700 text-xs font-bold"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Masuk ke Dashboard
                </PrimaryButton>
            </div>

            <div class="pt-4 border-t border-slate-100 dark:border-slate-800 text-center">
                <a
                    :href="route('attendance.scan')"
                    class="inline-flex items-center text-xs font-semibold text-emerald-600 dark:text-emerald-400 hover:underline"
                >
                    <span>Buka Terminal Scan RFID Siswa</span>
                    <ArrowRight class="w-3.5 h-3.5 ml-1" />
                </a>
            </div>
        </form>
    </GuestLayout>
</template>
