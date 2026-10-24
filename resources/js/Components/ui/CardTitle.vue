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
    <h3 :class="cn('text-lg font-semibold leading-none tracking-tight text-slate-900 dark:text-slate-100', props.class)">
        <slot />
    </h3>
</template>

