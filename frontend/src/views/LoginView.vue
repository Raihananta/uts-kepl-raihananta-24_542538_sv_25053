<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '../api'

const router = useRouter()
const isRegister = ref(false)
const form = ref({ name: '', email: '', password: '' })
const error = ref('')

async function submit() {
  error.value = ''
  try {
    const url = isRegister.value ? '/register' : '/login'
    const { data } = await api.post(url, form.value)
    localStorage.setItem('token', data.token) // simpan token setelah login
    router.push('/notes')
  } catch (e) {
    const res = e.response?.data
    error.value = res?.errors
      ? Object.values(res.errors).flat().join(' ')
      : res?.message || 'Terjadi kesalahan'
  }
}
</script>

<template>
  <div class="box">
    <h2>{{ isRegister ? 'Daftar' : 'Masuk' }}</h2>
    <form @submit.prevent="submit">
      <input v-if="isRegister" v-model="form.name" placeholder="Nama" />
      <input v-model="form.email" type="email" placeholder="Email" />
      <input v-model="form.password" type="password" placeholder="Password" />
      <button>{{ isRegister ? 'Daftar' : 'Masuk' }}</button>
    </form>
    <p v-if="error" class="error">{{ error }}</p>
    <a href="#" @click.prevent="isRegister = !isRegister">
      {{ isRegister ? 'Sudah punya akun? Masuk' : 'Belum punya akun? Daftar' }}
    </a>
  </div>
</template>

<style scoped>
.box { max-width: 360px; margin: 40px auto; font-family: sans-serif; }
form { display: flex; flex-direction: column; gap: 8px; margin-bottom: 12px; }
input, button { padding: 8px; }
.error { color: #c00; }
</style>
