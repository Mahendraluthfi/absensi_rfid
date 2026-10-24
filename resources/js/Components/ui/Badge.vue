<script setup>
import { computed } from 'vue';
import { cn } from '@/lib/utils';

const props = defineProps({
    variant: {
        type: String,
        default: 'default', // default, secondary, destructive, outline, emerald, amber, blue, rose, purple, indigo
    },
    class: {
        type: String,
        default: '',
    },
});

const variantClasses = computed(() => {
    switch (props.variant) {
        case 'secondary':
            return 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-100';
        case 'destructive':
            return 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300';
        case 'outline':
            return 'border border-slate-300 text-slate-700 dark:border-slate-700 dark:text-slate-300';
        case 'emerald':
        case 'success':
            return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800';
        case 'amber':
        case 'warning':
            return 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300 border border-amber-200 dark:border-amber-800';
        case 'blue':
        case 'info':
            return 'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300 border border-blue-200 dark:border-blue-800';
        case 'rose':
            return 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300 border border-rose-200 dark:border-rose-800';
        case 'purple':
            return 'bg-purple-100 text-purple-800 dark:bg-purple-950 dark:text-purple-300 border border-purple-200 dark:border-purple-800';
        case 'indigo':
            return 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800';
        default:
            return 'bg-slate-900 text-white dark:bg-slate-100 dark:text-slate-900';
    }
});
</script>

<template>
    <span
        :class="
            cn(
                'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold tracking-wide transition-colors',
                variantClasses,
                props.class
            )
        "
    >
        <slot />
    </span>
</template>

