<!-- This file is now deprecated. Please use /views/landing/HomePage.vue instead -->
<template>
  <div></div>
</template>

<script setup lang="ts">
// Redirect to new landing page
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()

onMounted(() => {
  router.replace('/')
})
</script>