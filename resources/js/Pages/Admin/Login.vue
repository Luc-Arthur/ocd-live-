<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import Logo from '@/Components/Logo.vue';
import { shake } from '@/motion';

defineProps({ demo: Boolean });
const form = useForm({ email: '', password: '', remember: true });
const fill = () => { form.email = 'admin@ocdlive.test'; form.password = 'ocdlive2026'; };
const card = ref(null);
// Identifiants refusés : la carte tremble (le message reste la vraie information)
watch(() => form.errors.email, (e) => e && shake(card.value));
</script>

<template>
    <Head title="Espace équipe" />
    <div class="grid min-h-screen place-items-center bg-night px-4">
        <div class="w-full max-w-sm">
            <Link href="/" class="flex justify-center"><Logo light /></Link>
            <form ref="card" @submit.prevent="form.post('/admin/connexion')" class="mt-8 rounded-2xl bg-cream p-7">
                <h1 class="text-2xl font-bold">Espace équipe</h1>
                <p class="mt-1 text-sm text-ink-soft">Planning des lives et notifications de réservation.</p>
                <div class="mt-6 space-y-4">
                    <div>
                        <label class="label" for="email">E-mail</label>
                        <input id="email" v-model="form.email" type="email" class="field" autocomplete="username" required />
                        <p v-if="form.errors.email" class="msg-in mt-1 text-sm text-live">{{ form.errors.email }}</p>
                    </div>
                    <div>
                        <label class="label" for="password">Mot de passe</label>
                        <input id="password" v-model="form.password" type="password" class="field" autocomplete="current-password" required />
                    </div>
                </div>
                <button type="submit" class="btn-primary mt-6 w-full" :disabled="form.processing">Se connecter</button>
                <button v-if="demo" type="button" @click="fill" class="mt-4 w-full rounded-xl border border-dashed border-ember/40 bg-ember/5 px-3 py-2.5 text-left text-xs text-ink-soft">
                    <strong class="text-ink">Accès démo</strong> · admin@ocdlive.test / ocdlive2026 <span class="text-ember">(cliquer pour remplir)</span>
                </button>
            </form>
        </div>
    </div>
</template>
