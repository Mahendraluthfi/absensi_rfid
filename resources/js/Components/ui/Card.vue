<script setup>
import { cn } from '@/lib/utils';

const props = defineProps({
    class: {
        type: String,
        default: '',
    },
});
</script>

<template>
    <div
        :class="
            cn(
                'rounded-xl border border-slate-200/80 bg-white text-slate-950 shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:text-slate-50',
                props.class
            )
        "
    >
        <slot />
    </div>
</template>

