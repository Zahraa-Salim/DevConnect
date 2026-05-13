<!-- resources/js/Layouts/AuthenticatedLayout.vue -->
<template>
  <div class="relative min-h-screen flex flex-col bg-dc-page-bg dark:bg-dc-dark-bg">
    <DevBackground :opacity="0.12" />   <!-- ← add this -->
    <NavBar />
    <FlashMessages />
    <main class="relative z-10 flex-grow">
      <slot />
    </main>
  </div>
</template>

<script setup lang="ts">
import NavBar from '@/Components/NavBar.vue'
import FlashMessages from '@/Components/FlashMessages.vue'
import DevBackground from '@/Components/DevBackground.vue' 
</script>