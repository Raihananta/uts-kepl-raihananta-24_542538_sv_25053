<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '../api'

const router = useRouter()
const notes = ref([])
const form = ref({ title: '', content: '' })
const editingId = ref(null)
const error = ref('')

function logoutLocal() {
  localStorage.removeItem('token')
  router.push('/login')
}

async function load() {
  try {
    const { data } = await api.get('/notes')
    notes.value = data.data
  } catch (e) {
    if (e.response?.status === 401) logoutLocal()
  }
}

async function save() {
  error.value = ''
  try {
    if (editingId.value) await api.put(`/notes/${editingId.value}`, form.value)
    else await api.post('/notes', form.value)
    form.value = { title: '', content: '' }
    editingId.value = null
    await load()
  } catch (e) {
    const errs = e.response?.data?.errors
    error.value = errs ? Object.values(errs).flat().join(' ') : 'Gagal menyimpan'
  }
}

function edit(n) {
  editingId.value = n.id
  form.value = { title: n.title, content: n.content }
}

async function remove(id) {
  await api.delete(`/notes/${id}`)
  await load()
}

async function logout() {
  try {
    await api.post('/logout')
  } finally {
    logoutLocal()
  }
}

onMounted(load)
</script>

<template>
  <div class="wrap">
    <header>
      <h2>Catatan Saya</h2>
      <button @click="logout">Keluar</button>
    </header>

    <form @submit.prevent="save">
      <input v-model="form.title" placeholder="Judul" />
      <textarea v-model="form.content" placeholder="Isi catatan"></textarea>
      <button>{{ editingId ? 'Perbarui' : 'Tambah' }}</button>
    </form>
    <p v-if="error" class="error">{{ error }}</p>

    <ul>
      <li v-for="n in notes" :key="n.id">
        <strong>{{ n.title }}</strong>
        <p>{{ n.content }}</p>
        <button @click="edit(n)">Ubah</button>
        <button @click="remove(n.id)">Hapus</button>
      </li>
    </ul>
  </div>
</template>

<style scoped>
.wrap { max-width: 560px; margin: 30px auto; font-family: sans-serif; }
header { display: flex; justify-content: space-between; align-items: center; }
form { display: flex; flex-direction: column; gap: 8px; margin: 16px 0; }
input, textarea, button { padding: 8px; }
li { list-style: none; border: 1px solid #ddd; padding: 10px; margin-bottom: 8px; border-radius: 6px; }
.error { color: #c00; }
</style>
