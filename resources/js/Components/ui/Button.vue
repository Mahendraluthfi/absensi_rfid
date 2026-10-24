<script setup>
import { computed } from 'vue';
import { cn } from '@/lib/utils';

const props = defineProps({
    variant: {
        type: String,
        default: 'default', // default, secondary, destructive, outline, ghost, link
    },
    size: {
        type: String,
        default: 'default', // default, sm, lg, icon
    },
    type: {
        type: String,
        default: 'button',
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    class: {
        type: String,
        default: '',
    },
});

const variantClasses = computed(() => {
    switch (props.variant) {
        case 'secondary':
            return 'bg-slate-100 text-slate-900 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-100 dark:hover:bg-slate-700';
        case 'destructive':
            return 'bg-rose-600 text-white hover:bg-rose-700 dark:bg-rose-700 dark:hover:bg-rose-800';
        case 'outline':
            return 'border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800';
        case 'ghost':
            return 'hover:bg-slate-100 hover:text-slate-900 dark:hover:bg-slate-800 dark:hover:text-slate-100';
        case 'link':
            return 'text-indigo-600 underline-offset-4 hover:underline dark:text-indigo-400 p-0 h-auto';
        default:
            return 'bg-slate-900 text-white hover:bg-slate-800 dark:bg-slate-100 dark:text-slate-900 dark:hover:bg-slate-200 shadow-sm';
    }
});

const sizeClasses = computed(() => {
    switch (props.size) {
        case 'sm':
            return 'h-8 rounded-md px-3 text-xs';
        case 'lg':
            return 'h-11 rounded-lg px-8 text-base';
        case 'icon':
            return 'h-9 w-9 rounded-md p-0 flex items-center justify-center';
        default:
            return 'h-9 px-4 py-2 text-sm rounded-md';
    }
});
</script>

<template>
    <button
        :type="type"
        :disabled="disabled"
        :class="
            cn(
                'inline-flex items-center justify-center font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 disabled:pointer-events-none disabled:opacity-50 cursor-pointer select-none',
                variantClasses,
                sizeClasses,
                props.class
            )
        "
    >
        <slot />
    </button>
</template>

