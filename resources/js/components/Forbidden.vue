<template>
  <div class="forbidden">
    <div class="forbidden__card">
      <div class="forbidden__code">403</div>
      <div class="forbidden__divider" />
      <div class="forbidden__body">
        <h1 class="forbidden__title">Access Denied</h1>
        <p class="forbidden__desc">
          Your account role <strong>{{ role ?? "Unknown" }}</strong> does not have
          permission to view this page.
        </p>
        <button class="forbidden__btn" @click="$router.push('/dashboard')">
          ← Back to Dashboard
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useAuth } from "@/js/composables/useAuth";
const { role } = useAuth();
</script>

<style scoped>
.forbidden {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f4f5f7;
  font-family: "Inter", sans-serif;
}

.forbidden__card {
  display: flex;
  align-items: center;
  gap: 2rem;
  background: #fff;
  border-radius: 12px;
  padding: 3rem 3.5rem;
}

.forbidden__code {
  font-size: 6rem;
  font-weight: 800;
  color: #e53e3e;
  line-height: 1;
  letter-spacing: -4px;
}

.forbidden__divider {
  width: 2px;
  height: 80px;
  background: #e2e8f0;
  border-radius: 99px;
}

.forbidden__title {
  font-size: 1.4rem;
  font-weight: 700;
  margin: 0 0 0.5rem;
  color: #1a202c;
}

.forbidden__desc {
  color: #4a5568;
  margin: 0 0 1.5rem;
  line-height: 1.6;
}

.forbidden__btn {
  background: #3182ce;
  color: #fff;
  border: none;
  border-radius: 6px;
  padding: 0.6rem 1.2rem;
  font-size: 0.9rem;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.15s;
}
.forbidden__btn:hover {
  background: #2b6cb0;
}
</style>