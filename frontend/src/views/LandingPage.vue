<template>
  <div class="landing-page">
    <!-- Navigation -->
    <nav class="landing-nav" :class="{ scrolled: isScrolled }">
      <div class="container">
        <div class="nav-content">
          <div class="logo">
            <span class="logo-icon">✓</span>
            <span class="logo-text">TaskFlow</span>
          </div>
          <div class="nav-links" :class="{ active: mobileMenuOpen }">
            <a href="#features" @click="scrollToSection">{{ t('landing.nav.features') }}</a>
            <a href="#how-it-works" @click="scrollToSection">{{ t('landing.nav.how_it_works') }}</a>
            <a href="#testimonials" @click="scrollToSection">{{ t('landing.nav.testimonials') }}</a>
            <a href="#pricing" @click="scrollToSection">{{ t('landing.nav.pricing') }}</a>
          </div>
          <div class="nav-actions">
            <button class="btn-login" @click="goToLogin">{{ t('landing.nav.login') }}</button>
            <button class="btn-signup" @click="goToRegister">{{ t('landing.nav.signup') }}</button>
          </div>
          <button class="mobile-menu-toggle" @click="mobileMenuOpen = !mobileMenuOpen">
            <span></span>
            <span></span>
            <span></span>
          </button>
        </div>
      </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
      <div class="container">
        <div class="hero-content">
          <h1 class="hero-title">
            <span class="title-line-1">{{ t('landing.hero.title_1') }}</span>
            <span class="title-line-2 gradient-text">{{ t('landing.hero.title_2') }}</span>
          </h1>
          <p class="hero-description">
            {{ t('landing.hero.description') }}
          </p>
          <div class="hero-actions">
            <button class="btn-primary" @click="goToRegister">
              {{ t('landing.hero.cta_start') }}
              <i class="pi pi-arrow-right"></i>
            </button>
            <button class="btn-secondary" @click="watchDemo">
              <i class="pi pi-play"></i>
              {{ t('landing.hero.cta_demo') }}
            </button>
          </div>
          <div class="hero-stats">
            <div class="stat">
              <span class="stat-number">10K+</span>
              <span class="stat-label">{{ t('landing.hero.stat_users') }}</span>
            </div>
            <div class="stat">
              <span class="stat-number">500K+</span>
              <span class="stat-label">{{ t('landing.hero.stat_tasks') }}</span>
            </div>
            <div class="stat">
              <span class="stat-number">99%</span>
              <span class="stat-label">{{ t('landing.hero.stat_satisfaction') }}</span>
            </div>
          </div>
        </div>
        <div class="hero-visual">
          <div class="floating-cards">
            <div class="card card-1" data-aos="fade-up" data-aos-delay="100">
              <div class="card-header">
                <span class="card-icon">📋</span>
                <span class="card-badge">{{ t('landing.hero.card_today') }}</span>
              </div>
              <div class="card-task">
                <input type="checkbox" checked disabled>
                <span>{{ t('landing.hero.task_1') }}</span>
              </div>
              <div class="card-task">
                <input type="checkbox" disabled>
                <span>{{ t('landing.hero.task_2') }}</span>
              </div>
            </div>
            <div class="card card-2" data-aos="fade-up" data-aos-delay="200">
              <div class="card-progress">
                <span class="progress-label">{{ t('landing.hero.progress') }}</span>
                <div class="progress-bar">
                  <div class="progress-fill" style="width: 75%"></div>
                </div>
                <span class="progress-percent">75%</span>
              </div>
            </div>
            <div class="card card-3" data-aos="fade-up" data-aos-delay="300">
              <div class="card-calendar">
                <div class="calendar-header">{{ t('landing.hero.calendar') }}</div>
                <div class="calendar-dots">
                  <span class="dot completed"></span>
                  <span class="dot completed"></span>
                  <span class="dot active"></span>
                  <span class="dot"></span>
                  <span class="dot"></span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="hero-bg-gradient"></div>
    </section>

    <!-- Features Section -->
    <section class="features-section" id="features">
      <div class="container">
        <div class="section-header">
          <h2 class="section-title">{{ t('landing.features.title') }}</h2>
          <p class="section-subtitle">{{ t('landing.features.subtitle') }}</p>
        </div>
        <div class="features-grid">
          <div v-for="feature in features" :key="feature.id" class="feature-card" data-aos="fade-up">
            <div class="feature-icon-wrapper">
              <i :class="feature.icon" class="feature-icon"></i>
            </div>
            <h3 class="feature-title">{{ feature.title }}</h3>
            <p class="feature-description">{{ feature.description }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- How It Works Section -->
    <section class="how-it-works-section" id="how-it-works">
      <div class="container">
        <div class="section-header">
          <h2 class="section-title">{{ t('landing.how_it_works.title') }}</h2>
          <p class="section-subtitle">{{ t('landing.how_it_works.subtitle') }}</p>
        </div>
        <div class="steps-container">
          <div v-for="(step, index) in steps" :key="step.id" class="step" data-aos="fade-right" :data-aos-delay="index * 100">
            <div class="step-number">{{ index + 1 }}</div>
            <div class="step-content">
              <h3 class="step-title">{{ step.title }}</h3>
              <p class="step-description">{{ step.description }}</p>
            </div>
            <div class="step-visual">
              <img :src="step.image" :alt="step.title">
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Testimonials Section -->
    <section class="testimonials-section" id="testimonials">
      <div class="container">
        <div class="section-header">
          <h2 class="section-title">{{ t('landing.testimonials.title') }}</h2>
          <p class="section-subtitle">{{ t('landing.testimonials.subtitle') }}</p>
        </div>
        <div class="testimonials-slider">
          <div class="testimonial-card" v-for="testimonial in testimonials" :key="testimonial.id" data-aos="zoom-in">
            <div class="testimonial-rating">
              <i class="pi pi-star-fill" v-for="n in 5" :key="n"></i>
            </div>
            <p class="testimonial-text">{{ testimonial.text }}</p>
            <div class="testimonial-author">
              <img :src="testimonial.avatar" :alt="testimonial.name">
              <div>
                <h4>{{ testimonial.name }}</h4>
                <span>{{ testimonial.role }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Pricing Section -->
    <section class="pricing-section" id="pricing">
      <div class="container">
        <div class="section-header">
          <h2 class="section-title">{{ t('landing.pricing.title') }}</h2>
          <p class="section-subtitle">{{ t('landing.pricing.subtitle') }}</p>
        </div>
        <div class="pricing-toggle">
          <span :class="{ active: !isYearly }">{{ t('landing.pricing.monthly') }}</span>
          <button class="toggle-switch" @click="isYearly = !isYearly">
            <span class="toggle-slider" :class="{ yearly: isYearly }"></span>
          </button>
          <span :class="{ active: isYearly }">{{ t('landing.pricing.yearly') }}</span>
          <span class="save-badge">{{ t('landing.pricing.save') }}</span>
        </div>
        <div class="pricing-cards">
          <div v-for="plan in pricingPlans" :key="plan.id" class="pricing-card" :class="{ popular: plan.popular }" data-aos="flip-left">
            <div v-if="plan.popular" class="popular-badge">{{ t('landing.pricing.popular') }}</div>
            <h3 class="plan-name">{{ plan.name }}</h3>
            <div class="plan-price">
              <span class="currency">$</span>
              <span class="amount">{{ isYearly ? plan.yearlyPrice : plan.monthlyPrice }}</span>
              <span class="period">/{{ isYearly ? t('landing.pricing.year') : t('landing.pricing.month') }}</span>
            </div>
            <p class="plan-description">{{ plan.description }}</p>
            <ul class="plan-features">
              <li v-for="feature in plan.features" :key="feature">
                <i class="pi pi-check-circle"></i>
                {{ feature }}
              </li>
            </ul>
            <button class="plan-button" :class="{ primary: plan.popular }">
              {{ t('landing.pricing.choose_plan') }}
            </button>
          </div>
        </div>
      </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
      <div class="container">
        <div class="cta-content">
          <h2 class="cta-title">{{ t('landing.cta.title') }}</h2>
          <p class="cta-subtitle">{{ t('landing.cta.subtitle') }}</p>
          <button class="btn-cta" @click="goToRegister">
            {{ t('landing.cta.button') }}
            <i class="pi pi-arrow-right"></i>
          </button>
        </div>
        <div class="cta-decoration">
          <div class="decoration-circle circle-1"></div>
          <div class="decoration-circle circle-2"></div>
          <div class="decoration-circle circle-3"></div>
        </div>
      </div>
    </section>

    <!-- Footer -->
    <footer class="landing-footer">
      <div class="container">
        <div class="footer-content">
          <div class="footer-brand">
            <div class="logo">
              <span class="logo-icon">✓</span>
              <span class="logo-text">TaskFlow</span>
            </div>
            <p>{{ t('landing.footer.tagline') }}</p>
            <div class="social-links">
              <a href="#"><i class="pi pi-twitter"></i></a>
              <a href="#"><i class="pi pi-facebook"></i></a>
              <a href="#"><i class="pi pi-linkedin"></i></a>
              <a href="#"><i class="pi pi-github"></i></a>
            </div>
          </div>
          <div class="footer-links">
            <div class="link-group">
              <h4>{{ t('landing.footer.product') }}</h4>
              <a href="#">{{ t('landing.footer.features') }}</a>
              <a href="#">{{ t('landing.footer.pricing') }}</a>
              <a href="#">{{ t('landing.footer.integrations') }}</a>
            </div>
            <div class="link-group">
              <h4>{{ t('landing.footer.company') }}</h4>
              <a href="#">{{ t('landing.footer.about') }}</a>
              <a href="#">{{ t('landing.footer.careers') }}</a>
              <a href="#">{{ t('landing.footer.blog') }}</a>
            </div>
            <div class="link-group">
              <h4>{{ t('landing.footer.support') }}</h4>
              <a href="#">{{ t('landing.footer.help') }}</a>
              <a href="#">{{ t('landing.footer.contact') }}</a>
              <a href="#">{{ t('landing.footer.status') }}</a>
            </div>
          </div>
        </div>
        <div class="footer-bottom">
          <p>© 2025 TaskFlow. {{ t('landing.footer.rights') }}</p>
          <div class="legal-links">
            <a href="#">{{ t('landing.footer.privacy') }}</a>
            <a href="#">{{ t('landing.footer.terms') }}</a>
          </div>
        </div>
      </div>
    </footer>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()
const router = useRouter()

// State
const isScrolled = ref(false)
const mobileMenuOpen = ref(false)
const isYearly = ref(false)

// Features data
const features = computed(() => [
  {
    id: 1,
    icon: 'pi pi-check-square',
    title: t('landing.features.feature_1_title'),
    description: t('landing.features.feature_1_desc')
  },
  {
    id: 2,
    icon: 'pi pi-calendar',
    title: t('landing.features.feature_2_title'),
    description: t('landing.features.feature_2_desc')
  },
  {
    id: 3,
    icon: 'pi pi-chart-line',
    title: t('landing.features.feature_3_title'),
    description: t('landing.features.feature_3_desc')
  },
  {
    id: 4,
    icon: 'pi pi-users',
    title: t('landing.features.feature_4_title'),
    description: t('landing.features.feature_4_desc')
  },
  {
    id: 5,
    icon: 'pi pi-mobile',
    title: t('landing.features.feature_5_title'),
    description: t('landing.features.feature_5_desc')
  },
  {
    id: 6,
    icon: 'pi pi-shield',
    title: t('landing.features.feature_6_title'),
    description: t('landing.features.feature_6_desc')
  }
])

// Steps data
const steps = computed(() => [
  {
    id: 1,
    title: t('landing.how_it_works.step_1_title'),
    description: t('landing.how_it_works.step_1_desc'),
    image: '/images/step-1.svg'
  },
  {
    id: 2,
    title: t('landing.how_it_works.step_2_title'),
    description: t('landing.how_it_works.step_2_desc'),
    image: '/images/step-2.svg'
  },
  {
    id: 3,
    title: t('landing.how_it_works.step_3_title'),
    description: t('landing.how_it_works.step_3_desc'),
    image: '/images/step-3.svg'
  }
])

// Testimonials data
const testimonials = computed(() => [
  {
    id: 1,
    text: t('landing.testimonials.testimonial_1'),
    name: 'Sarah Johnson',
    role: 'Product Manager',
    avatar: 'https://i.pravatar.cc/150?img=1'
  },
  {
    id: 2,
    text: t('landing.testimonials.testimonial_2'),
    name: 'Mike Chen',
    role: 'Startup Founder',
    avatar: 'https://i.pravatar.cc/150?img=2'
  },
  {
    id: 3,
    text: t('landing.testimonials.testimonial_3'),
    name: 'Emily Davis',
    role: 'Freelancer',
    avatar: 'https://i.pravatar.cc/150?img=3'
  }
])

// Pricing plans
const pricingPlans = computed(() => [
  {
    id: 1,
    name: t('landing.pricing.free_plan'),
    monthlyPrice: 0,
    yearlyPrice: 0,
    description: t('landing.pricing.free_desc'),
    features: [
      t('landing.pricing.free_feature_1'),
      t('landing.pricing.free_feature_2'),
      t('landing.pricing.free_feature_3')
    ]
  },
  {
    id: 2,
    name: t('landing.pricing.pro_plan'),
    monthlyPrice: 9,
    yearlyPrice: 90,
    description: t('landing.pricing.pro_desc'),
    popular: true,
    features: [
      t('landing.pricing.pro_feature_1'),
      t('landing.pricing.pro_feature_2'),
      t('landing.pricing.pro_feature_3'),
      t('landing.pricing.pro_feature_4')
    ]
  },
  {
    id: 3,
    name: t('landing.pricing.team_plan'),
    monthlyPrice: 29,
    yearlyPrice: 290,
    description: t('landing.pricing.team_desc'),
    features: [
      t('landing.pricing.team_feature_1'),
      t('landing.pricing.team_feature_2'),
      t('landing.pricing.team_feature_3'),
      t('landing.pricing.team_feature_4'),
      t('landing.pricing.team_feature_5')
    ]
  }
])

// Methods
const handleScroll = () => {
  isScrolled.value = window.scrollY > 50
}

const scrollToSection = (event: Event) => {
  event.preventDefault()
  const target = (event.target as HTMLAnchorElement).getAttribute('href')
  if (target) {
    const element = document.querySelector(target)
    element?.scrollIntoView({ behavior: 'smooth' })
  }
  mobileMenuOpen.value = false
}

const goToLogin = () => {
  router.push('/login')
}

const goToRegister = () => {
  router.push('/register')
}

const watchDemo = () => {
  // Implement demo video modal
  console.log('Watch demo')
}

// Lifecycle
onMounted(() => {
  window.addEventListener('scroll', handleScroll)
  
  // Initialize AOS animations
  if (typeof window !== 'undefined') {
    import('aos').then(AOS => {
      AOS.default.init({
        duration: 1000,
        once: true,
        offset: 100
      })
    })
  }
})

onUnmounted(() => {
  window.removeEventListener('scroll', handleScroll)
})
</script>

<style scoped>
/* Global Landing Styles */
.landing-page {
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
  color: #1a1a1a;
  overflow-x: hidden;
}

.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 20px;
}

/* Navigation */
.landing-nav {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 1000;
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(10px);
  transition: all 0.3s ease;
  padding: 20px 0;
}

.landing-nav.scrolled {
  padding: 15px 0;
  box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
}

.nav-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.logo {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 24px;
  font-weight: bold;
  cursor: pointer;
}

.logo-icon {
  width: 40px;
  height: 40px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 20px;
}

.nav-links {
  display: flex;
  gap: 40px;
}

.nav-links a {
  color: #666;
  text-decoration: none;
  font-weight: 500;
  transition: color 0.3s ease;
}

.nav-links a:hover {
  color: #667eea;
}

.nav-actions {
  display: flex;
  gap: 15px;
}

.btn-login {
  padding: 10px 20px;
  background: transparent;
  border: none;
  color: #667eea;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
}

.btn-signup {
  padding: 10px 25px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border: none;
  color: white;
  border-radius: 25px;
  font-weight: 600;
  cursor: pointer;
  transition: transform 0.3s ease;
}

.btn-signup:hover {
  transform: translateY(-2px);
}

.mobile-menu-toggle {
  display: none;
  flex-direction: column;
  gap: 4px;
  background: none;
  border: none;
  cursor: pointer;
  padding: 5px;
}

.mobile-menu-toggle span {
  width: 25px;
  height: 2px;
  background: #333;
  transition: all 0.3s ease;
}

/* Hero Section */
.hero-section {
  padding: 150px 0 100px;
  background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
  position: relative;
  overflow: hidden;
}

.hero-content {
  max-width: 600px;
}

.hero-title {
  font-size: clamp(2.5rem, 5vw, 4rem);
  font-weight: 800;
  line-height: 1.2;
  margin-bottom: 30px;
}

.title-line-1 {
  display: block;
  color: #1a1a1a;
}

.title-line-2 {
  display: block;
}

.gradient-text {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.hero-description {
  font-size: 1.25rem;
  color: #666;
  margin-bottom: 40px;
  line-height: 1.6;
}

.hero-actions {
  display: flex;
  gap: 20px;
  margin-bottom: 60px;
}

.btn-primary {
  padding: 15px 35px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border: none;
  color: white;
  border-radius: 30px;
  font-size: 18px;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 10px;
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.btn-primary:hover {
  transform: translateY(-3px);
  box-shadow: 0 15px 30px rgba(102, 126, 234, 0.4);
}

.btn-secondary {
  padding: 15px 35px;
  background: white;
  border: 2px solid #e0e0e0;
  color: #333;
  border-radius: 30px;
  font-size: 18px;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 10px;
  transition: all 0.3s ease;
}

.btn-secondary:hover {
  border-color: #667eea;
  color: #667eea;
}

.hero-stats {
  display: flex;
  gap: 50px;
}

.stat {
  display: flex;
  flex-direction: column;
}

.stat-number {
  font-size: 2rem;
  font-weight: 800;
  color: #667eea;
}

.stat-label {
  color: #666;
  font-size: 0.9rem;
}

.hero-visual {
  position: absolute;
  right: 50px;
  top: 50%;
  transform: translateY(-50%);
  width: 500px;
  height: 500px;
}

.floating-cards {
  position: relative;
  width: 100%;
  height: 100%;
}

.card {
  position: absolute;
  background: white;
  border-radius: 20px;
  padding: 20px;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
  transition: transform 0.3s ease;
}

.card:hover {
  transform: translateY(-5px);
}

.card-1 {
  top: 50px;
  left: 50px;
  width: 280px;
}

.card-2 {
  top: 150px;
  right: 50px;
  width: 250px;
}

.card-3 {
  bottom: 50px;
  left: 100px;
  width: 220px;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 15px;
}

.card-icon {
  font-size: 24px;
}

.card-badge {
  background: #e8f5e9;
  color: #4caf50;
  padding: 5px 10px;
  border-radius: 15px;
  font-size: 12px;
  font-weight: 600;
}

.card-task {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 10px;
}

.card-progress {
  text-align: center;
}

.progress-label {
  display: block;
  color: #666;
  margin-bottom: 10px;
}

.progress-bar {
  height: 8px;
  background: #f0f0f0;
  border-radius: 4px;
  overflow: hidden;
  margin-bottom: 10px;
}

.progress-fill {
  height: 100%;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  transition: width 1s ease;
}

.progress-percent {
  font-size: 24px;
  font-weight: bold;
  color: #667eea;
}

.calendar-dots {
  display: flex;
  gap: 10px;
  justify-content: center;
  margin-top: 15px;
}

.dot {
  width: 12px;
  height: 12px;
  border-radius: 50%;
  background: #e0e0e0;
}

.dot.completed {
  background: #4caf50;
}

.dot.active {
  background: #667eea;
}

/* Features Section */
.features-section {
  padding: 100px 0;
  background: white;
}

.section-header {
  text-align: center;
  margin-bottom: 60px;
}

.section-title {
  font-size: 3rem;
  font-weight: 800;
  margin-bottom: 20px;
  color: #1a1a1a;
}

.section-subtitle {
  font-size: 1.25rem;
  color: #666;
}

.features-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
  gap: 40px;
}

.feature-card {
  padding: 40px;
  background: #f8f9fa;
  border-radius: 20px;
  transition: all 0.3s ease;
  cursor: pointer;
}

.feature-card:hover {
  transform: translateY(-10px);
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
  background: white;
}

.feature-icon-wrapper {
  width: 70px;
  height: 70px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 25px;
}

.feature-icon {
  font-size: 30px;
  color: white;
}

.feature-title {
  font-size: 1.5rem;
  font-weight: 700;
  margin-bottom: 15px;
  color: #1a1a1a;
}

.feature-description {
  color: #666;
  line-height: 1.6;
}

/* How It Works Section */
.how-it-works-section {
  padding: 100px 0;
  background: linear-gradient(135deg, #fafafa 0%, #f0f0f0 100%);
}

.steps-container {
  display: flex;
  flex-direction: column;
  gap: 60px;
}

.step {
  display: grid;
  grid-template-columns: 80px 1fr 300px;
  gap: 40px;
  align-items: center;
}

.step:nth-child(even) {
  direction: rtl;
}

.step:nth-child(even) > * {
  direction: ltr;
}

.step-number {
  width: 80px;
  height: 80px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 2rem;
  font-weight: bold;
}

.step-title {
  font-size: 1.75rem;
  font-weight: 700;
  margin-bottom: 15px;
  color: #1a1a1a;
}

.step-description {
  color: #666;
  line-height: 1.6;
}

.step-visual img {
  width: 100%;
  height: auto;
}

/* Testimonials Section */
.testimonials-section {
  padding: 100px 0;
  background: white;
}

.testimonials-slider {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
  gap: 40px;
}

.testimonial-card {
  padding: 40px;
  background: #f8f9fa;
  border-radius: 20px;
  transition: all 0.3s ease;
}

.testimonial-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
}

.testimonial-rating {
  display: flex;
  gap: 5px;
  margin-bottom: 20px;
  color: #ffc107;
}

.testimonial-text {
  font-size: 1.125rem;
  line-height: 1.6;
  color: #333;
  margin-bottom: 30px;
  font-style: italic;
}

.testimonial-author {
  display: flex;
  align-items: center;
  gap: 15px;
}

.testimonial-author img {
  width: 50px;
  height: 50px;
  border-radius: 50%;
}

.testimonial-author h4 {
  margin: 0;
  color: #1a1a1a;
  font-weight: 600;
}

.testimonial-author span {
  color: #666;
  font-size: 0.9rem;
}

/* Pricing Section */
.pricing-section {
  padding: 100px 0;
  background: linear-gradient(135deg, #fafafa 0%, #f0f0f0 100%);
}

.pricing-toggle {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 20px;
  margin-bottom: 60px;
}

.toggle-switch {
  width: 60px;
  height: 30px;
  background: #e0e0e0;
  border: none;
  border-radius: 15px;
  position: relative;
  cursor: pointer;
  transition: background 0.3s ease;
}

.toggle-slider {
  position: absolute;
  top: 3px;
  left: 3px;
  width: 24px;
  height: 24px;
  background: white;
  border-radius: 50%;
  transition: transform 0.3s ease;
}

.toggle-slider.yearly {
  transform: translateX(30px);
}

.pricing-toggle span {
  color: #666;
  font-weight: 500;
}

.pricing-toggle span.active {
  color: #1a1a1a;
  font-weight: 600;
}

.save-badge {
  background: #4caf50;
  color: white;
  padding: 5px 10px;
  border-radius: 15px;
  font-size: 12px;
  font-weight: 600;
}

.pricing-cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
  gap: 40px;
}

.pricing-card {
  padding: 40px;
  background: white;
  border-radius: 20px;
  position: relative;
  transition: all 0.3s ease;
}

.pricing-card:hover {
  transform: translateY(-10px);
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
}

.pricing-card.popular {
  border: 2px solid #667eea;
  transform: scale(1.05);
}

.popular-badge {
  position: absolute;
  top: -15px;
  left: 50%;
  transform: translateX(-50%);
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 5px 20px;
  border-radius: 15px;
  font-size: 12px;
  font-weight: 600;
}

.plan-name {
  font-size: 1.5rem;
  font-weight: 700;
  margin-bottom: 20px;
  color: #1a1a1a;
}

.plan-price {
  display: flex;
  align-items: baseline;
  margin-bottom: 20px;
}

.currency {
  font-size: 1.5rem;
  color: #666;
}

.amount {
  font-size: 3rem;
  font-weight: 800;
  color: #1a1a1a;
  margin: 0 5px;
}

.period {
  color: #666;
}

.plan-description {
  color: #666;
  margin-bottom: 30px;
}

.plan-features {
  list-style: none;
  padding: 0;
  margin-bottom: 30px;
}

.plan-features li {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 15px;
  color: #333;
}

.plan-features i {
  color: #4caf50;
}

.plan-button {
  width: 100%;
  padding: 15px;
  background: white;
  border: 2px solid #e0e0e0;
  border-radius: 25px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
}

.plan-button:hover {
  border-color: #667eea;
  color: #667eea;
}

.plan-button.primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border: none;
  color: white;
}

.plan-button.primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 20px rgba(102, 126, 234, 0.4);
}

/* CTA Section */
.cta-section {
  padding: 100px 0;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  position: relative;
  overflow: hidden;
}

.cta-content {
  text-align: center;
  position: relative;
  z-index: 1;
}

.cta-title {
  font-size: 3rem;
  font-weight: 800;
  color: white;
  margin-bottom: 20px;
}

.cta-subtitle {
  font-size: 1.25rem;
  color: rgba(255, 255, 255, 0.9);
  margin-bottom: 40px;
}

.btn-cta {
  padding: 20px 50px;
  background: white;
  border: none;
  color: #667eea;
  border-radius: 30px;
  font-size: 18px;
  font-weight: 600;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 10px;
  transition: all 0.3s ease;
}

.btn-cta:hover {
  transform: translateY(-3px);
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
}

.cta-decoration {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  pointer-events: none;
}

.decoration-circle {
  position: absolute;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.1);
}

.circle-1 {
  width: 300px;
  height: 300px;
  top: -150px;
  left: -150px;
}

.circle-2 {
  width: 200px;
  height: 200px;
  bottom: -100px;
  right: -100px;
}

.circle-3 {
  width: 150px;
  height: 150px;
  top: 50%;
  right: 10%;
}

/* Footer */
.landing-footer {
  padding: 60px 0 20px;
  background: #1a1a1a;
  color: white;
}

.footer-content {
  display: grid;
  grid-template-columns: 1fr 2fr;
  gap: 60px;
  margin-bottom: 40px;
}

.footer-brand p {
  color: #999;
  margin: 20px 0;
}

.social-links {
  display: flex;
  gap: 15px;
}

.social-links a {
  width: 40px;
  height: 40px;
  background: rgba(255, 255, 255, 0.1);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  transition: all 0.3s ease;
}

.social-links a:hover {
  background: #667eea;
  transform: translateY(-3px);
}

.footer-links {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 40px;
}

.link-group h4 {
  margin-bottom: 20px;
  color: white;
}

.link-group a {
  display: block;
  color: #999;
  text-decoration: none;
  margin-bottom: 10px;
  transition: color 0.3s ease;
}

.link-group a:hover {
  color: #667eea;
}

.footer-bottom {
  padding-top: 20px;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
  display: flex;
  justify-content: space-between;
  align-items: center;
  color: #999;
}

.legal-links {
  display: flex;
  gap: 20px;
}

.legal-links a {
  color: #999;
  text-decoration: none;
  transition: color 0.3s ease;
}

.legal-links a:hover {
  color: #667eea;
}

/* Responsive Design */
@media (max-width: 1024px) {
  .hero-visual {
    display: none;
  }
  
  .step {
    grid-template-columns: 80px 1fr;
  }
  
  .step-visual {
    display: none;
  }
}

@media (max-width: 768px) {
  .nav-links,
  .nav-actions {
    display: none;
  }
  
  .mobile-menu-toggle {
    display: flex;
  }
  
  .nav-links.active {
    display: flex;
    flex-direction: column;
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    padding: 20px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
  }
  
  .hero-title {
    font-size: 2rem;
  }
  
  .hero-actions {
    flex-direction: column;
  }
  
  .hero-stats {
    flex-wrap: wrap;
    gap: 20px;
  }
  
  .features-grid,
  .testimonials-slider,
  .pricing-cards {
    grid-template-columns: 1fr;
  }
  
  .footer-content {
    grid-template-columns: 1fr;
  }
  
  .footer-links {
    grid-template-columns: 1fr;
  }
  
  .footer-bottom {
    flex-direction: column;
    gap: 20px;
    text-align: center;
  }
}

/* Animations */
@keyframes float {
  0%, 100% {
    transform: translateY(0);
  }
  50% {
    transform: translateY(-20px);
  }
}

.floating-cards .card {
  animation: float 6s ease-in-out infinite;
}

.card-1 {
  animation-delay: 0s;
}

.card-2 {
  animation-delay: 2s;
}

.card-3 {
  animation-delay: 4s;
}
</style>
