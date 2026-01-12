import './bootstrap';
import './cart-animation';

const initCustomSortSelect = () => {
    const wrapper = document.querySelector('[data-custom-select]');
    if (!wrapper) return;

    const nativeSelect = wrapper.querySelector('select[name="sort"]');
    const trigger = wrapper.querySelector('[data-select-trigger]');
    const dropdown = wrapper.querySelector('[data-select-dropdown]');
    const label = wrapper.querySelector('[data-select-label]');
    const options = wrapper.querySelectorAll('[data-select-option]');
    if (!nativeSelect || !trigger || !dropdown || !label || !options.length) return;

    wrapper.classList.add('is-enhanced');

    const closeDropdown = () => {
        trigger.setAttribute('aria-expanded', 'false');
        wrapper.classList.remove('is-open');
    };

    const setActiveOption = (value) => {
        options.forEach((option) => {
            const isActive = option.dataset.value === value;
            option.classList.toggle('is-active', isActive);
            option.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });
    };

    trigger.addEventListener('click', (event) => {
        event.preventDefault();
        const expanded = trigger.getAttribute('aria-expanded') === 'true';
        trigger.setAttribute('aria-expanded', expanded ? 'false' : 'true');
        wrapper.classList.toggle('is-open', !expanded);
    });

    options.forEach((option) => {
        option.addEventListener('click', (event) => {
            event.preventDefault();
            const value = option.dataset.value;
            if (!value) {
                closeDropdown();
                return;
            }

            if (nativeSelect.value !== value) {
                nativeSelect.value = value;
                label.textContent = option.textContent?.trim() ?? '';
                setActiveOption(value);
                nativeSelect.dispatchEvent(new Event('change', { bubbles: true }));
            }

            closeDropdown();
        });
    });

    document.addEventListener('click', (event) => {
        if (!wrapper.contains(event.target)) {
            closeDropdown();
        }
    });
};

document.addEventListener('DOMContentLoaded', () => {
    const filterForm = document.querySelector('.filter-form');

    if (!filterForm) {
        initCustomSortSelect();
        stripImageBackgrounds();
        initAssociatedScroller();
        initCartFab();
        return;
    }

    const brandCheckboxes = filterForm.querySelectorAll('input[name="brand[]"]');
    const modelContainer = filterForm.querySelector('[data-models]');
    const problemContainer = filterForm.querySelector('[data-problems]');
    const categoryContainer = filterForm.querySelector('[data-categories]');
    let productList = document.querySelector('.product-card-list');
    let pagination = document.querySelector('[data-pagination]');
    const resultTitle = document.querySelector('.catalog-meta h2');
    const sortSelect = document.querySelector('select[name="sort"]');

    const syncCardMinWidth = () => {
        if (!productList) return;
        const width = window.innerWidth;
        const isPhone = width <= 540;
        const cardMin = isPhone ? '0px' : '190px';
        productList.style.setProperty('--catalog-card-min', cardMin);
        if (!isPhone) {
            productList.style.removeProperty('grid-template-columns');
        }
    };
    syncCardMinWidth();
    window.addEventListener('resize', syncCardMinWidth);

    const updateFilters = async () => {
        const formData = new FormData(filterForm);
        const brands = formData.getAll('brand[]');
        const currentCategories = formData.getAll('category[]');
        const category = filterForm.dataset.category ?? null;

        try {
            const response = await fetch('/api/filters', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ brands, category, categories: currentCategories }),
            });

            if (!response.ok) {
                return;
            }

            const payload = await response.json();

            const currentModels = formData.getAll('model[]');
            if (modelContainer) {
                modelContainer.innerHTML = payload.models
                    .map((model) => {
                        const checked = currentModels.includes(model) ? 'checked' : '';
                        return `<label class="filter-pill"><input type="checkbox" name="model[]" value="${model}" ${checked}> <span>${model}</span></label>`;
                    })
                    .join('');
            }

            const currentProblems = formData.getAll('problem[]');
            if (problemContainer) {
                problemContainer.innerHTML = payload.problems
                    .map((problem) => {
                        const checked = currentProblems.includes(problem) ? 'checked' : '';
                        return `<label class="filter-pill"><input type="checkbox" name="problem[]" value="${problem}" ${checked}> <span>${problem}</span></label>`;
                    })
                    .join('');
            }

            if (categoryContainer && payload.categories) {
                categoryContainer.innerHTML = payload.categories
                    .map((cat) => {
                        const checked = currentCategories.includes(cat) ? 'checked' : '';
                        return `<label class="filter-pill"><input type="checkbox" name="category[]" value="${cat}" ${checked}> <span>${cat}</span></label>`;
                    })
                    .join('');
            }

            attachModelProblemListeners();
        } catch (error) {
            console.error('Filter update failed', error);
        }
    };

    const updateProducts = async () => {
        if (!productList) return;

        const formData = new FormData(filterForm);
        const params = new URLSearchParams(formData);
        if (sortSelect) {
            params.set('sort', sortSelect.value);
        }

        const url = `${window.location.pathname}?${params.toString()}`;

        try {
            const response = await fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            if (!response.ok) return;

            const html = await response.text();
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const nextList = doc.querySelector('.product-card-list');
            const nextTitle = doc.querySelector('.catalog-meta h2');
            const nextPagination = doc.querySelector('[data-pagination]');

            if (nextList && productList.parentElement) {
                productList.parentElement.replaceChild(nextList, productList);
                productList = nextList;
                syncCardMinWidth();
                stripImageBackgrounds();
                if (window.cartAnimation) {
                    window.cartAnimation.attachFormListeners();
                }
            }
            if (nextTitle && resultTitle) {
                resultTitle.textContent = nextTitle.textContent ?? '';
            }
            if (nextPagination && pagination && pagination.parentElement) {
                pagination.parentElement.replaceChild(nextPagination, pagination);
                pagination = nextPagination;
                // Intercepte les clics pagination pour conserver les filtres
                bindPaginationLinks();
            }

            history.replaceState({}, '', url);
        } catch (error) {
            console.error('Product update failed', error);
            filterForm.submit();
        }
    };

    const attachModelProblemListeners = () => {
        const modelInputs = filterForm.querySelectorAll('input[name="model[]"]');
        const problemInputs = filterForm.querySelectorAll('input[name="problem[]"]');
        const categoryInputs = filterForm.querySelectorAll('input[name="category[]"]');

        modelInputs.forEach((input) => {
            input.addEventListener('change', () => {
                void updateProducts();
            });
        });
        problemInputs.forEach((input) => {
            input.addEventListener('change', () => {
                void updateProducts();
            });
        });
        categoryInputs.forEach((input) => {
            input.addEventListener('change', () => {
                void updateProducts();
            });
        });
    };

    if (brandCheckboxes.length && modelContainer && problemContainer) {
        brandCheckboxes.forEach((checkbox) => {
            checkbox.addEventListener('change', () => {
                updateFilters()
                    .then(() => updateProducts())
                    .catch((error) => console.error(error));
            });
        });

        attachModelProblemListeners();

        // Si des marques sont déjà sélectionnées au chargement, rafraîchir les modèles/problèmes filtrés
        const hasBrandSelected = Array.from(brandCheckboxes).some((cb) => cb.checked);
        if (hasBrandSelected) {
            updateFilters().catch((error) => console.error(error));
        }
    }

    if (sortSelect) {
        sortSelect.removeAttribute('onchange');
        sortSelect.addEventListener('change', (event) => {
            event.preventDefault();
            void updateProducts();
        });
    }

    const bindPaginationLinks = () => {
        document.querySelectorAll('[data-pagination] a').forEach((link) => {
            link.addEventListener('click', (event) => {
                event.preventDefault();
                const url = new URL(link.href);
                const formData = new FormData(filterForm);
                formData.forEach((value, key) => {
                    url.searchParams.delete(key);
                });
                formData.forEach((value, key) => {
                    url.searchParams.append(key, String(value));
                });
                // Conserve le sort courant
                if (sortSelect) {
                    url.searchParams.set('sort', sortSelect.value);
                }
                fetch(url.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then((resp) => resp.text())
                    .then((html) => {
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, 'text/html');
                        const nextList = doc.querySelector('.product-card-list');
                        const nextTitle = doc.querySelector('.catalog-meta h2');
                        const nextPagination = doc.querySelector('[data-pagination]');
                        if (nextList && productList.parentElement) {
                            productList.parentElement.replaceChild(nextList, productList);
                            productList = nextList;
                            syncCardMinWidth();
                            stripImageBackgrounds();
                            if (window.cartAnimation) {
                                window.cartAnimation.attachFormListeners();
                            }
                        }
                        if (nextTitle && resultTitle) {
                            resultTitle.textContent = nextTitle.textContent ?? '';
                        }
                        if (nextPagination && pagination && pagination.parentElement) {
                            pagination.parentElement.replaceChild(nextPagination, pagination);
                            pagination = nextPagination;
                            bindPaginationLinks();
                        }
                        history.replaceState({}, '', url.toString());
                    })
                    .catch(() => {
                        window.location.href = url.toString();
                    });
            });
        });
    };

    filterForm.addEventListener('submit', (event) => {
        event.preventDefault();
        void updateProducts();
    });

    initCustomSortSelect();
    stripImageBackgrounds();
    initAssociatedScroller();
    initCartFab();
    initAddToCartAnimations();
    bindPaginationLinks();
});

const stripImageBackgrounds = () => {
    document.querySelectorAll('.catalog-card figure img').forEach((img) => {
        if (img.dataset.imageProcessed === 'true') {
            return;
        }

        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');
        if (!ctx) return;
        const image = new Image();
        image.crossOrigin = 'anonymous';
        image.onload = () => {
            canvas.width = image.width;
            canvas.height = image.height;
            ctx.drawImage(image, 0, 0);
            try {
                const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
                const data = imageData.data;
                for (let i = 0; i < data.length; i += 4) {
                    const r = data[i];
                    const g = data[i + 1];
                    const b = data[i + 2];
                    if (r > 240 && g > 240 && b > 240) {
                        data[i + 3] = 0;
                    }
                }
                ctx.putImageData(imageData, 0, 0);
                img.src = canvas.toDataURL('image/png');
                img.dataset.imageProcessed = 'true';
            } catch (error) {
                console.warn('Background strip skipped:', error);
            }
        };
        image.src = img.currentSrc || img.src;
    });
};

const initAssociatedScroller = () => {
    document.querySelectorAll('[data-associated-wrapper]').forEach((wrapper) => {
        if (wrapper.dataset.scrollBound === 'true') return;
        wrapper.dataset.scrollBound = 'true';

        const viewport = wrapper.querySelector('.associated-viewport');
        const slider = viewport?.querySelector('.associated-grid');
        const prev = wrapper.querySelector('[data-associated-prev]');
        const next = wrapper.querySelector('[data-associated-next]');
        if (!viewport || !slider) return;

        let velocity = 0;
        let rafId = null;

        const updateButtons = () => {
            const maxScroll = slider.scrollWidth - slider.clientWidth;
            const x = slider.scrollLeft;
            const canScroll = maxScroll > 4;
            wrapper.classList.toggle('can-scroll', canScroll);
            const isAtStart = x <= 4;
            const isAtEnd = x >= maxScroll - 4;
            if (prev) {
                prev.disabled = !canScroll || isAtStart;
                prev.classList.toggle('is-hidden', !canScroll || isAtStart);
            }
            if (next) {
                next.disabled = !canScroll || isAtEnd;
                next.classList.toggle('is-hidden', !canScroll || isAtEnd);
            }
        };

        const step = () => {
            slider.scrollLeft += velocity;
            velocity *= 0.8;
            if (Math.abs(velocity) < 0.5) {
                rafId = null;
                return;
            }
            updateButtons();
            rafId = requestAnimationFrame(step);
        };

        viewport.addEventListener('wheel', (event) => {
            // Désactiver le scroll molette pour laisser les flèches gérer le mouvement
            event.preventDefault();
        }, { passive: false });

        const scrollByAmount = (direction) => {
            const amount = slider.clientWidth * 0.8 || 280;
            slider.scrollBy({ left: direction * amount, behavior: 'smooth' });
            setTimeout(updateButtons, 320);
        };

        prev?.addEventListener('click', () => scrollByAmount(-1));
        next?.addEventListener('click', () => scrollByAmount(1));
        slider.addEventListener('scroll', updateButtons, { passive: true });
        window.addEventListener('resize', updateButtons);
        updateButtons();

    });
};

const initCartFab = () => {
    const fab = document.getElementById('cartFab');
    if (!fab) return;

    fab.addEventListener('click', () => {
        const href = fab.dataset.href ?? '/panier';
        window.location.href = href;
    });
};

let cartToastTimer;

const showCartToast = () => {
    const toast = document.getElementById('cartToast');
    if (!toast) return;

    toast.textContent = 'Articles ajoutés';
    toast.classList.add('is-visible');
    if (cartToastTimer) {
        clearTimeout(cartToastTimer);
    }
    cartToastTimer = window.setTimeout(() => {
        toast.classList.remove('is-visible');
    }, 1800);
};

const animateToCartFab = (sourceEl) => {
    const fab = document.getElementById('cartFab');
    if (!fab || !sourceEl) return;

    const sourceRect = sourceEl.getBoundingClientRect();
    const fabRect = fab.getBoundingClientRect();
    const size = 18;
    const fly = document.createElement('div');
    fly.className = 'cart-fly';
    fly.style.width = `${size}px`;
    fly.style.height = `${size}px`;
    fly.style.left = `${sourceRect.left + sourceRect.width / 2 - size / 2}px`;
    fly.style.top = `${sourceRect.top + sourceRect.height / 2 - size / 2}px`;
    document.body.appendChild(fly);

    requestAnimationFrame(() => {
        const deltaX = fabRect.left + fabRect.width / 2 - (sourceRect.left + sourceRect.width / 2);
        const deltaY = fabRect.top + fabRect.height / 2 - (sourceRect.top + sourceRect.height / 2);
        fly.style.transform = `translate(${deltaX}px, ${deltaY}px) scale(0.3)`;
        fly.classList.add('is-active');
    });

    window.setTimeout(() => fly.remove(), 900);
};

const initAddToCartAnimations = () => {
    // Animation désormais gérée par resources/js/cart-animation.js
    return;
};
