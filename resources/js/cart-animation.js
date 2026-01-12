/**
 * Animation fluide d'ajout au panier - RIPAIR
 * Crée une expérience shopping premium avec animations synchronisées
 */

class CartAnimationManager {
  constructor() {
    this.cartFab = null;
    this.cartToast = null;
    this.toastTimer = null;
    this.fabTimer = null;
    this.flyAnimationTimer = null;
    this.activeFlyingItem = null;
    this.currentAnimationResolve = null;
    this.miniFreeShipping = null;
    this.bannerFreeShipping = null;
    this.lastCartTotal = 0;
    this.requestSeq = 0;
    this.init();
  }

  init() {
    // Créer les éléments si nécessaire
    this.ensureElements();
    
    // Attacher les événements aux formulaires
    this.attachFormListeners();
    
    // Animer légèrement le FAB au chargement sans le masquer
    this.animateCartFabOnLoad();
  }

  ensureElements() {
    if (document.body.dataset.hideCartFab === 'true') {
      return;
    }

    // Trouver ou créer le FAB du panier
    this.cartFab = document.querySelector('.cart-fab');
    
    if (!this.cartFab) {
      this.cartFab = this.createCartFab();
      document.body.appendChild(this.cartFab);
    } else {
      // Si le FAB existe, s'assurer qu'il a un event listener
      const existingHref = this.cartFab.getAttribute('data-href');
      if (existingHref) {
        this.cartFab.addEventListener('click', () => {
          window.location.href = existingHref;
        });
      }
    }

    // Trouver ou créer le toast
    this.cartToast = document.querySelector('.cart-toast');
    
    if (!this.cartToast) {
      this.cartToast = this.createCartToast();
      document.body.appendChild(this.cartToast);
    }

    // Mini barre de livraison sous le FAB
    this.miniFreeShipping = document.getElementById('cartFreeMini');
    // Barre de livraison dans le catalogue
    this.bannerFreeShipping = document.getElementById('freeShippingBanner');

    // Mémoriser le total initial connu (si présent dans le DOM)
    this.lastCartTotal = this.getKnownCartTotal();

    // Ajouter le badge de compteur
    this.ensureCartBadge();
    const initialCount = parseInt(this.cartFab.dataset.cartCount || '0', 10);
    if (!Number.isNaN(initialCount) && initialCount > 0) {
      this.updateCartBadge(initialCount);
    }

    // Mise à jour initiale des jauges livraison (sans pulser)
    this.updateFreeShippingMini(null, false);
    this.updateFreeShippingBanner(null, false);
  }

  createCartFab() {
    const fab = document.createElement('button');
    fab.className = 'cart-fab';
    fab.setAttribute('aria-label', 'Voir le panier');
    fab.dataset.href = '/panier';
    fab.innerHTML = `
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="9" cy="21" r="1"></circle>
        <circle cx="20" cy="21" r="1"></circle>
        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
      </svg>
    `;
    
    // Redirection vers le panier au clic
    fab.addEventListener('click', () => {
      const target = fab.dataset.href || '/panier';
      window.location.href = target;
    });
    
    return fab;
  }

  createCartToast() {
    const toast = document.createElement('div');
    toast.className = 'cart-toast';
    toast.setAttribute('role', 'status');
    toast.setAttribute('aria-live', 'polite');
    return toast;
  }

  ensureCartBadge() {
    if (!this.cartFab.querySelector('.cart-badge')) {
      const badge = document.createElement('span');
      badge.className = 'cart-badge';
      badge.textContent = '0';
      this.cartFab.appendChild(badge);
    }
  }

  attachFormListeners() {
    const forms = document.querySelectorAll('.js-add-to-cart');

    forms.forEach(form => {
      if (form.dataset.bound === '1') return;
      form.dataset.bound = '1';
      form.addEventListener('submit', (e) => {
        e.preventDefault();
        this.handleAddToCart(form);
      });
    });
  }

  async handleAddToCart(form) {
    const productName = form.dataset.productName || 'Produit';
    const productImage = form.dataset.productImage;
    const formData = new FormData(form);
    const optimisticQty = parseInt(formData.get('quantity') || '1', 10) || 1;
    const unitPrice = parseFloat(form.dataset.productPrice || '0') || 0;
    const requestId = ++this.requestSeq;
    const isPhone =
      typeof window !== 'undefined' &&
      typeof window.matchMedia === 'function' &&
      window.matchMedia('(max-width: 540px)').matches;

    let optimisticTarget = null;

    try {
      this.finishActiveAnimation();
      this.cleanupTransientAnimations();
      const button = form.querySelector('button[type="submit"]');
      void this.animateProductToCart(button, productImage);
      // Afficher le toast le plus tôt possible (sans attendre la requête)
      this.showToast(isPhone ? 'Ajouté au panier' : `✨ ${productName} ajouté !`);
      // Mettre à jour le badge immédiatement (optimiste)
      optimisticTarget = this.incrementCartBadge(optimisticQty);
      // Jouer la célébration immédiatement pour rester synchronisé avec le badge
      this.celebrateAddition(productName);
      // Mise à jour optimiste des jauges livraison en ajoutant le panier courant + prix estimé
      if (unitPrice > 0) {
        const optimisticTotal = this.getKnownCartTotal() + unitPrice * optimisticQty;
        this.updateFreeShippingMini(optimisticTotal);
        this.updateFreeShippingBanner(optimisticTotal);
      }

      // Envoyer la requête au serveur immédiatement (en parallèle de l'animation)
      const response = await fetch(form.action, {
        method: 'POST',
        body: formData,
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
      });

      if (response.ok) {
        const data = await response.json();
        if (requestId === this.requestSeq) {
          this.syncBadgeWithServer(data.cartCount, optimisticTarget);
          const cartTotal = data.cartTotal !== undefined ? data.cartTotal : null;
          if (cartTotal !== null && !Number.isNaN(Number(cartTotal))) {
            this.lastCartTotal = Number(cartTotal);
          }
          this.updateFreeShippingMini(cartTotal);
          this.updateFreeShippingBanner(cartTotal);
        }
      } else {
        throw new Error('Erreur lors de l\'ajout au panier');
      }
    } catch (error) {
      console.error('Erreur:', error);
      this.showToast('❌ Erreur lors de l\'ajout', 'error');
      // Revenir au compteur précédent si l'ajout échoue
      if (optimisticTarget !== null) {
        const previousCount = Math.max(0, optimisticTarget - optimisticQty);
        this.updateCartBadge(previousCount, { animate: false });
      }
    }
  }

  async animateProductToCart(button, imageSrc) {
    // Terminer immédiatement une animation en cours pour lancer la nouvelle
    this.finishActiveAnimation();

    return new Promise(resolve => {
      // Créer l'élément volant
      const flyingItem = document.createElement('div');
      flyingItem.className = 'cart-fly';
      this.activeFlyingItem = flyingItem;
      let done = false;

      const complete = () => {
        if (done) return;
        done = true;
        if (this.flyAnimationTimer) {
          clearTimeout(this.flyAnimationTimer);
          this.flyAnimationTimer = null;
        }
        if (this.activeFlyingItem?.parentNode) {
          this.activeFlyingItem.remove();
        }
        this.activeFlyingItem = null;
        this.currentAnimationResolve = null;
        resolve();
      };

      this.currentAnimationResolve = complete;
      
      if (imageSrc) {
        flyingItem.style.backgroundImage = `url(${imageSrc})`;
        flyingItem.style.backgroundSize = 'contain';
        flyingItem.style.backgroundRepeat = 'no-repeat';
        flyingItem.style.backgroundPosition = 'center';
        flyingItem.style.border = '3px solid rgba(255, 255, 255, 0.95)';
      }

      // Position de départ
      const buttonRect = button.getBoundingClientRect();
      const cartRect = this.cartFab.getBoundingClientRect();

      flyingItem.style.left = `${buttonRect.left + buttonRect.width / 2}px`;
      flyingItem.style.top = `${buttonRect.top + buttonRect.height / 2}px`;

      document.body.appendChild(flyingItem);
      
      // Créer des particules explosives
      this.createParticles(buttonRect);

      // Petit délai pour que le DOM se mette à jour
      requestAnimationFrame(() => {
        if (done) return;

        flyingItem.classList.add('is-active');
        
        // Animation vers le panier avec courbe
        const deltaX = cartRect.left + cartRect.width / 2 - (buttonRect.left + buttonRect.width / 2);
        const deltaY = cartRect.top + cartRect.height / 2 - (buttonRect.top + buttonRect.height / 2);
        
        flyingItem.style.transform = `translate(${deltaX}px, ${deltaY}px) scale(0.2) rotate(360deg)`;
        flyingItem.style.opacity = '0';

        // Nettoyer après l'animation
        this.flyAnimationTimer = setTimeout(() => {
          complete();
        }, 950);
      });
    });
  }

  async celebrateAddition(productName, cartCount = null) {
    // Animation courte sur le FAB uniquement à l'ajout
    if (this.cartFab) {
      if (this.fabTimer) {
        clearTimeout(this.fabTimer);
        this.fabTimer = null;
      }
      // Réinitialiser l'animation pour qu'elle redémarre instantanément même si déjà en cours
      this.cartFab.style.animation = 'none';
      // Trigger reflow
      void this.cartFab.offsetWidth;
      this.cartFab.style.animation = 'cart-fab-pulse 0.8s cubic-bezier(0.34, 1.56, 0.64, 1)';
      this.fabTimer = setTimeout(() => {
        this.cartFab.style.animation = '';
        this.fabTimer = null;
      }, 850);
    }

    // Mettre à jour le compteur si fourni
    if (cartCount !== null) {
      this.updateCartBadge(cartCount);
    }
  }

  incrementCartBadge(by = 1) {
    const current = this.getCurrentBadgeCount();
    const next = current + by;
    this.updateCartBadge(next);
    return next;
  }

  finishActiveAnimation() {
    if (typeof this.currentAnimationResolve === 'function') {
      this.currentAnimationResolve();
    }
    this.cleanupTransientAnimations();
  }

  cleanupTransientAnimations() {
    document.querySelectorAll('.cart-fly, .cart-particle').forEach((el) => el.remove());
    if (this.flyAnimationTimer) {
      clearTimeout(this.flyAnimationTimer);
      this.flyAnimationTimer = null;
    }
    this.activeFlyingItem = null;
    this.currentAnimationResolve = null;
  }

  getCurrentBadgeCount() {
    const badge = this.cartFab?.querySelector?.('.cart-badge');
    if (!badge) return 0;
    const val = parseInt(badge.textContent || '0', 10);
    return Number.isNaN(val) ? 0 : val;
  }

  updateCartBadge(count, options = {}) {
    const { animate = true } = options;
    const badge = this.cartFab.querySelector('.cart-badge');
    if (badge) {
      const oldCount = parseInt(badge.textContent) || 0;
      badge.textContent = count;
      // Animation du badge
      if (animate && count > oldCount) {
        badge.style.animation = 'none';
        requestAnimationFrame(() => {
          badge.style.animation = 'badge-pop 0.6s cubic-bezier(0.34, 1.56, 0.64, 1)';
        });
      } else if (!animate) {
        badge.style.animation = '';
      }
    }
  }

  syncBadgeWithServer(cartCount, optimisticTarget = null) {
    const serverValue = parseInt(cartCount, 10);
    if (Number.isNaN(serverValue)) return;

    const optimistic = optimisticTarget ?? this.getCurrentBadgeCount();
    if (serverValue !== optimistic) {
      this.updateCartBadge(serverValue, { animate: false });
    }
  }

  updateFreeShippingMini(cartTotalOverride = null, animate = true) {
    if (!this.miniFreeShipping) return;

    const min = parseFloat(this.miniFreeShipping.dataset.freeShippingMin || '0');
    if (!min || Number.isNaN(min)) {
      this.miniFreeShipping.style.display = 'none';
      return;
    }

    const overrideNum = cartTotalOverride === null || cartTotalOverride === undefined ? NaN : Number(cartTotalOverride);
    const fallback = Number(this.miniFreeShipping.dataset.cartTotal || 0);
    const base = Number.isFinite(overrideNum) ? overrideNum : fallback;
    const cartTotal = Number.isFinite(base) ? base : this.lastCartTotal || 0;
    this.lastCartTotal = cartTotal;
    this.miniFreeShipping.dataset.cartTotal = cartTotal;

    const progress = Math.min(100, Math.max(0, (cartTotal / min) * 100));
    const remaining = Math.max(0, min - cartTotal);

    const bar = this.miniFreeShipping.querySelector('.cart-free-mini__bar span');
    const text = this.miniFreeShipping.querySelector('.cart-free-mini__text');
    if (bar) {
      bar.style.width = `${progress}%`;
      if (animate) {
        bar.classList.remove('is-pulse');
        // Trigger a mini animation on update
        void bar.offsetWidth;
        bar.classList.add('is-pulse');
      }
    }
    if (text) {
      text.textContent =
        remaining <= 0
          ? 'Livraison offerte 🎉'
          : `Encore ${remaining.toFixed(2).replace('.', ',')} € pour la livraison offerte`;
      if (remaining <= 0 && animate) {
        text.classList.remove('is-celebrate');
        void text.offsetWidth;
        text.classList.add('is-celebrate');
      }
    }
  }

  updateFreeShippingBanner(cartTotalOverride = null, animate = true) {
    if (!this.bannerFreeShipping) return;

    const min = parseFloat(this.bannerFreeShipping.dataset.freeShippingMin || '0');
    if (!min || Number.isNaN(min)) {
      this.bannerFreeShipping.style.display = 'none';
      return;
    }

    const overrideNum = cartTotalOverride === null || cartTotalOverride === undefined ? NaN : Number(cartTotalOverride);
    const fallback = Number(this.bannerFreeShipping.dataset.cartTotal || 0);
    const base = Number.isFinite(overrideNum) ? overrideNum : fallback;
    const cartTotal = Number.isFinite(base) ? base : this.lastCartTotal || 0;
    this.lastCartTotal = cartTotal;
    this.bannerFreeShipping.dataset.cartTotal = cartTotal;

    const progress = Math.min(100, Math.max(0, (cartTotal / min) * 100));
    const remaining = Math.max(0, min - cartTotal);

    const bar = this.bannerFreeShipping.querySelector('[data-free-bar]');
    const text = this.bannerFreeShipping.querySelector('[data-free-text]');
    if (bar) {
      bar.style.width = `${progress}%`;
      if (animate) {
        bar.classList.remove('is-pulse');
        void bar.offsetWidth;
        bar.classList.add('is-pulse');
      }
    }
    if (text) {
      text.textContent =
        remaining <= 0
          ? 'Livraison offerte sur votre panier 🎉'
          : `Plus que ${remaining.toFixed(2).replace('.', ',')} € pour la livraison offerte`;
      if (remaining <= 0 && animate) {
        text.classList.remove('is-celebrate');
        void text.offsetWidth;
        text.classList.add('is-celebrate');
      }
    }
  }

  showToast(message, type = 'success') {
    this.cartToast.textContent = message;
    this.cartToast.classList.add('is-visible');
    if (this.toastTimer) {
      clearTimeout(this.toastTimer);
    }
    
    if (type === 'error') {
      this.cartToast.style.background = 'rgba(255, 240, 240, 0.98)';
      this.cartToast.style.color = '#c53030';
      this.cartToast.style.borderColor = 'rgba(197, 48, 48, 0.3)';
    } else {
      this.cartToast.style.background = '';
      this.cartToast.style.color = '';
      this.cartToast.style.borderColor = '';
    }

    // Masquer après 2.2 secondes pour finir avant toute navigation
    this.toastTimer = setTimeout(() => {
      this.cartToast.classList.remove('is-visible');
    }, 2200);
  }

  getKnownCartTotal() {
    const miniVal = parseFloat(this.miniFreeShipping?.dataset?.cartTotal || '');
    if (!Number.isNaN(miniVal) && miniVal > 0) {
      return miniVal;
    }
    const bannerVal = parseFloat(this.bannerFreeShipping?.dataset?.cartTotal || '');
    if (!Number.isNaN(bannerVal) && bannerVal > 0) {
      return bannerVal;
    }
    return this.lastCartTotal || 0;
  }

  animateCartFabOnLoad() {
    if (!this.cartFab) return;
    this.cartFab.style.transition = 'transform 0.6s cubic-bezier(0.34, 1.56, 0.64, 1)';
    this.cartFab.style.transform = 'scale(1.08)';
    setTimeout(() => {
      this.cartFab.style.transform = '';
    }, 600);
  }

  createParticles(buttonRect) {
    const particleCount = 8;
    const centerX = buttonRect.left + buttonRect.width / 2;
    const centerY = buttonRect.top + buttonRect.height / 2;

    for (let i = 0; i < particleCount; i++) {
      const particle = document.createElement('div');
      particle.className = 'cart-particle';
      particle.style.left = `${centerX}px`;
      particle.style.top = `${centerY}px`;

      const angle = (Math.PI * 2 * i) / particleCount;
      const distance = 80 + Math.random() * 40;
      const tx = Math.cos(angle) * distance;
      const ty = Math.sin(angle) * distance;

      particle.style.setProperty('--tx', `${tx}px`);
      particle.style.setProperty('--ty', `${ty}px`);

      document.body.appendChild(particle);

      setTimeout(() => particle.remove(), 1500);
    }
  }

  createConfetti() {
    const confettiCount = 12;
    const cartRect = this.cartFab.getBoundingClientRect();
    const centerX = cartRect.left + cartRect.width / 2;
    const centerY = cartRect.top + cartRect.height / 2;

    for (let i = 0; i < confettiCount; i++) {
      const confetti = document.createElement('div');
      confetti.className = 'cart-particle';
      confetti.style.left = `${centerX}px`;
      confetti.style.top = `${centerY}px`;

      const angle = (Math.PI * 2 * i) / confettiCount;
      const distance = 60 + Math.random() * 30;
      const tx = Math.cos(angle) * distance;
      const ty = Math.sin(angle) * distance - 20;

      confetti.style.setProperty('--tx', `${tx}px`);
      confetti.style.setProperty('--ty', `${ty}px`);

      const colors = ['#3abaf8', '#60d5fa', '#0ea5e9', '#22d3ee', '#38bdf8'];
      confetti.style.background = `radial-gradient(circle, ${colors[Math.floor(Math.random() * colors.length)]}, ${colors[Math.floor(Math.random() * colors.length)]})`;

      document.body.appendChild(confetti);

      setTimeout(() => confetti.remove(), 1500);
    }
  }
}

// Initialiser au chargement du DOM
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', () => {
    window.cartAnimation = new CartAnimationManager();
  });
} else {
  window.cartAnimation = new CartAnimationManager();
}
