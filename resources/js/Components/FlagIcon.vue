<script setup>
// Drapeaux simplifiés dessinés en SVG (les emojis drapeaux ne s'affichent pas sous Windows)
defineProps({ code: String, size: { type: Number, default: 28 } });

const star = (cx, cy, r) => {
    const pts = [];
    for (let i = 0; i < 10; i++) {
        const a = -Math.PI / 2 + (i * Math.PI) / 5;
        const rr = i % 2 ? r * 0.42 : r;
        pts.push(`${(cx + rr * Math.cos(a)).toFixed(2)},${(cy + rr * Math.sin(a)).toFixed(2)}`);
    }
    return pts.join(' ');
};
</script>

<template>
    <svg :width="size" :height="(size * 2) / 3" viewBox="0 0 30 20" class="shrink-0 overflow-hidden rounded-[3px] ring-1 ring-black/10" aria-hidden="true">
        <template v-if="code === 'TG'">
            <rect width="30" height="20" fill="#006a4e" />
            <rect y="4" width="30" height="4" fill="#ffce00" /><rect y="12" width="30" height="4" fill="#ffce00" />
            <rect width="12" height="12" fill="#d21034" />
            <polygon :points="star(6, 6, 3.6)" fill="#fff" />
        </template>
        <template v-else-if="code === 'BJ'">
            <rect width="30" height="10" fill="#fcd116" /><rect y="10" width="30" height="10" fill="#e8112d" /><rect width="12" height="20" fill="#008751" />
        </template>
        <template v-else-if="code === 'CI'">
            <rect width="10" height="20" fill="#f77f00" /><rect x="10" width="10" height="20" fill="#fff" /><rect x="20" width="10" height="20" fill="#009e60" />
        </template>
        <template v-else-if="code === 'CM'">
            <rect width="10" height="20" fill="#007a5e" /><rect x="10" width="10" height="20" fill="#ce1126" /><rect x="20" width="10" height="20" fill="#fcd116" />
            <polygon :points="star(15, 10, 3.6)" fill="#fcd116" />
        </template>
        <template v-else-if="code === 'SN'">
            <rect width="10" height="20" fill="#00853f" /><rect x="10" width="10" height="20" fill="#fdef42" /><rect x="20" width="10" height="20" fill="#e31b23" />
            <polygon :points="star(15, 10, 3.6)" fill="#00853f" />
        </template>
        <template v-else-if="code === 'BF'">
            <rect width="30" height="10" fill="#ef2b2d" /><rect y="10" width="30" height="10" fill="#009e49" />
            <polygon :points="star(15, 10, 3.8)" fill="#fcd116" />
        </template>
        <template v-else-if="code === 'ML'">
            <rect width="10" height="20" fill="#14b53a" /><rect x="10" width="10" height="20" fill="#fcd116" /><rect x="20" width="10" height="20" fill="#ce1126" />
        </template>
        <template v-else-if="code === 'NE'">
            <rect width="30" height="6.67" fill="#e05206" /><rect y="6.67" width="30" height="6.67" fill="#fff" /><rect y="13.33" width="30" height="6.67" fill="#0db02b" />
            <circle cx="15" cy="10" r="2.3" fill="#e05206" />
        </template>
        <template v-else-if="code === 'GN'">
            <rect width="10" height="20" fill="#ce1126" /><rect x="10" width="10" height="20" fill="#fcd116" /><rect x="20" width="10" height="20" fill="#009460" />
        </template>
        <template v-else-if="code === 'GA'">
            <rect width="30" height="6.67" fill="#009e60" /><rect y="6.67" width="30" height="6.67" fill="#fcd116" /><rect y="13.33" width="30" height="6.67" fill="#3a75c4" />
        </template>
        <template v-else-if="code === 'CG'">
            <rect width="30" height="20" fill="#fbde4a" />
            <polygon points="0,0 20,0 0,20" fill="#009543" /><polygon points="30,0 30,20 10,20" fill="#dc241f" />
        </template>
        <template v-else-if="code === 'CD'">
            <rect width="30" height="20" fill="#007fff" />
            <polygon points="0,20 0,15 24,0 30,0 30,5 6,20" fill="#f7d618" />
            <polygon points="0,20 0,17.2 26.6,0 30,0 30,2.8 3.4,20" fill="#ce1021" />
            <polygon :points="star(5.5, 4.6, 3.4)" fill="#f7d618" />
        </template>
        <template v-else>
            <rect width="30" height="20" fill="#e6d6c2" />
        </template>
    </svg>
</template>
