<script setup lang="ts">
withDefaults(defineProps<{ modelValue: boolean; small?: boolean; label?: string }>(), { small: false, label: undefined });
defineEmits<{ 'update:modelValue': [value: boolean] }>();
</script>

<template>
    <button
        type="button"
        role="switch"
        :aria-checked="modelValue"
        :aria-label="label"
        class="relative block flex-none rounded-full transition-colors"
        :class="[small ? 'h-[26px] w-[44px]' : 'h-[30px] w-[50px]', modelValue ? 'bg-cb-green' : 'bg-[rgba(120,80,50,.25)]']"
        @click="$emit('update:modelValue', !modelValue)"
    >
        <span
            class="absolute top-[3px] block rounded-full bg-white shadow-[0_1px_3px_rgba(0,0,0,.25)] transition-[left]"
            :class="small ? 'h-5 w-5' : 'h-6 w-6'"
            :style="{ left: modelValue ? (small ? '21px' : '23px') : '3px' }"
        />
    </button>
</template>
