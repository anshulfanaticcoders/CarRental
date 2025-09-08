<template>
  <div class="bubbles-container">
    <div class="bubble" v-for="i in 5" :key="i"></div>
  </div>
</template>

<style scoped>
.bubbles-container {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  overflow: hidden;
  z-index: 1;
}

.bubble {
  position: absolute;
  background-color: rgba(255, 255, 255, 0.15);
  border-radius: 50%;
  animation: float 6s infinite alternate ease-in-out;
}

.bubble:nth-child(1) {
  width: 80px;
  height: 80px;
  top: 20%;
  left: 15%;
  animation-duration: 5s;
}

.bubble:nth-child(2) {
  width: 40px;
  height: 40px;
  top: 40%;
  left: 40%;
  animation-duration: 7s;
  animation-delay: 1s;
}

.bubble:nth-child(3) {
  width: 60px;
  height: 60px;
  top: 60%;
  left: 70%;
  animation-duration: 6s;
  animation-delay: 2s;
}

.bubble:nth-child(4) {
  width: 30px;
  height: 30px;
  top: 80%;
  left: 10%;
  animation-duration: 8s;
  animation-delay: 0.5s;
}

.bubble:nth-child(5) {
  width: 50px;
  height: 50px;
  top: 10%;
  left: 85%;
  animation-duration: 4s;
  animation-delay: 1.5s;
}

@keyframes float {
  from {
    transform: translate(0, 0);
  }
  to {
    transform: translate(15px, 20px);
  }
}
</style>
