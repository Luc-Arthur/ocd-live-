<script setup>
// Ce que montre un moniteur : la vraie photo du vendeur, sinon un « signal caméra »
// dans la couleur de sa catégorie (grande icône + lignes de balayage)
import { computed } from 'vue';
import CategoryIcon from '@/Components/CategoryIcon.vue';
import { CAT_COLORS } from '@/utils';

const props = defineProps({
    seller: Object,
    icon: { type: Number, default: 72 },
    eager: Boolean,
});
const c = computed(() => CAT_COLORS[props.seller.category] ?? CAT_COLORS.autre);
const bg = computed(() => ({
    background: `radial-gradient(120% 85% at 50% 32%, color-mix(in oklab, ${c.value[1]} 88%, white) 0%, color-mix(in oklab, ${c.value[1]} 62%, black) 52%, #0d0907 100%)`,
}));
</script>

<template>
    <div class="absolute inset-0 overflow-hidden bg-regie-panel">
        <img v-if="seller.photo_url" :src="seller.photo_url" :alt="`Produit de ${seller.business_name || seller.name}`"
            class="absolute inset-0 h-full w-full object-cover" :loading="eager ? 'eager' : 'lazy'" decoding="async" />
        <template v-else>
            <div class="absolute inset-0" :style="bg"></div>
            <div class="absolute inset-0 grid place-items-center pb-[18%]" :style="{ color: c[0], opacity: 0.55 }">
                <CategoryIcon :slug="seller.category" :size="icon" />
            </div>
            <div class="scanlines absolute inset-0"></div>
        </template>
    </div>
</template>
