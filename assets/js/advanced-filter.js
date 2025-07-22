/**
 * Happy Search & Filter - Enhanced JavaScript
 * Modern functionality with improved UX, AJAX pagination, filter state management, form validation, and lazy loading
 */

(function($) {
    'use strict';

    // Configuration
    const config = {
        autoSubmitDelay: 500,
        searchDebounceDelay: 300, // Reduced for better responsiveness
        minSearchLength: 2, // Minimum characters before search triggers
        maxSearchLength: 100, // Maximum search term length
        loadingClass: 'loading',
        resultsContainer: '#hsf-search-results',
        formSelector: '#hsf-search-form',
        resetSelector: '.hbl-reset-button',
        paginationSelector: '.hbl-pagination .page-numbers',
        loadMoreSelector: '.hbl-load-more',
        infiniteScrollThreshold: 100, // pixels from bottom
        storageKey: 'hsf_filter_state',
        urlParamPrefix: 'hsf_',
        validationClass: 'hbl-validation-error',
        validationMessageClass: 'hbl-validation-message',
        lazyLoadThreshold: 50, // pixels from viewport
        lazyLoadClass: 'hbl-lazy-load',
        lazyLoadPlaceholder: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzAwIiBoZWlnaHQ9IjIwMCIgdmlld0JveD0iMCAwIDMwMCAyMDAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSIzMDAiIGhlaWdodD0iMjAwIiBmaWxsPSIjRjNGNEY2Ii8+CjxwYXRoIGQ9Ik0xNTAgMTAwTDE3MCAxMjBMMTMwIDEyMFoiIGZpbGw9IiM5Q0EzQUYiLz4KPC9zdmc+',
        // Performance settings
        enableSearchSuggestions: true,
        searchSuggestionDelay: 200,
        maxSearchSuggestions: 5,
        enableSearchHistory: true,
        maxSearchHistory: 10,
        // Debouncing settings
        debounceOptions: {
            leading: false,
            trailing: true,
            maxWait: 1000 // Maximum wait time for trailing edge
        },
        // Image optimization settings
        imageOptimization: {
            enableWebP: true,
            enableProgressiveLoading: true,
            enableSkeletonLoading: true,
            enableErrorFallbacks: true,
            retryAttempts: 3,
            retryDelay: 1000,
            preloadThreshold: 200, // pixels before viewport
            qualityThreshold: 0.8, // minimum quality for progressive loading
            enableBlurHash: false, // for future implementation
            enableAVIF: false // for future implementation
        },
        // Advanced features settings
        advancedFeatures: {
            enableMultiSelect: true,
            enableChips: true,
            enableAnalytics: true,
            enableExport: true,
            enablePresets: true,
            enableAdvancedSorting: true,
            maxSelections: 10, // maximum selections per filter
            chipAnimation: true,
            analyticsRefreshInterval: 30000, // 30 seconds
            exportFormats: ['csv', 'pdf'],
            presetConfigurations: {
                popular: {
                    orderby: 'popularity',
                    order: 'DESC',
                    verified: true
                },
                recent: {
                    orderby: 'date',
                    order: 'DESC'
                },
                verified: {
                    verified: true,
                    orderby: 'rating',
                    order: 'DESC'
                }
            }
        }
    };

    // Validation rules
    const validationRules = {
        search: {
            minLength: 2,
            maxLength: 100,
            pattern: /^[a-zA-Z0-9\s\-_.,!?()]+$/,
            messages: {
                minLength: 'Search term must be at least 2 characters',
                maxLength: 'Search term cannot exceed 100 characters',
                pattern: 'Search term contains invalid characters'
            }
        },
        location: {
            minLength: 2,
            maxLength: 50,
            pattern: /^[a-zA-Z0-9\s\-_,.()]+$/,
            messages: {
                minLength: 'Location must be at least 2 characters',
                maxLength: 'Location cannot exceed 50 characters',
                pattern: 'Location contains invalid characters'
            }
        },
        rating_min: {
            min: 1,
            max: 5,
            pattern: /^[1-5]$/,
            messages: {
                min: 'Rating must be at least 1',
                max: 'Rating cannot exceed 5',
                pattern: 'Rating must be a whole number between 1-5'
            }
        },
        results_per_page: {
            min: 1,
            max: 100,
            pattern: /^[1-9][0-9]?$|^100$/,
            messages: {
                min: 'Results per page must be at least 1',
                max: 'Results per page must be at most 100',
                pattern: 'Results per page must be a number between 1-100'
            }
        }
    };

    class HSFFilter {
        constructor() {
            this.form = $(config.formSelector);
            this.resultsContainer = $(config.resultsContainer);
            this.autoSubmitTimer = null;
            this.searchDebounceTimer = null;
            this.searchSuggestionTimer = null;
            this.isLoading = false;
            this.currentPage = 1;
            this.totalPages = 1;
            this.isInfiniteScroll = false;
            this.scrollTimer = null;
            this.filterState = {};
            this.isRestoringState = false;
            this.validationErrors = {};
            this.lazyLoadObserver = null;
            this.lazyLoadElements = new Set();
            
            // Enhanced debouncing properties
            this.lastSearchTerm = '';
            this.searchHistory = this.loadSearchHistory();
            this.searchSuggestions = [];
            this.isSearching = false;
            this.pendingSearch = null;
            
            // Enhanced image optimization properties
            this.imageObserver = null;
            this.imageLoadQueue = new Map();
            this.failedImages = new Set();
            this.loadedImages = new Set();
            this.skeletonElements = new Set();
            this.progressiveImages = new Map();
            this.webpSupport = this.checkWebPSupport();
            this.avifSupport = this.checkAVIFSupport();
            
            // Advanced features properties
            this.selectedChips = new Map();
            this.analyticsData = null;
            this.analyticsTimer = null;
            this.exportResults = [];
            this.presetActive = null;
            this.multiSelectElements = new Set();
            
            this.init();
        }

        init() {
            this.loadFilterState();
            this.bindEvents();
            this.setupAutoSubmit();
            this.setupEnhancedDebouncing(); // Add enhanced debouncing
            this.setupReset();
            this.setupAccessibility();
            this.setupPagination();
            this.setupInfiniteScroll();
            this.setupValidation();
            this.setupLazyLoading();
            this.setupAdvancedImageOptimization(); // Add advanced image optimization
            this.setupAdvancedFeatures(); // Add advanced features
            this.restoreFilterState();
            
            // Load all businesses by default if no filters are applied
            this.loadInitialResults();
        }

        loadInitialResults() {
            // Don't load initial results if we just restored filters
            if (this.isRestoringState) {
                return;
            }
            
            // Check if any filters are currently applied
            const formData = this.getFormData();
            const hasFilters = Object.keys(formData).some(key => {
                const value = formData[key];
                return value && value !== '' && value !== '0' && value !== false;
            });
            
            // If no filters are applied, load all businesses
            if (!hasFilters) {
                this.handleSubmit(1);
            }
        }

        bindEvents() {
            // Form submission with validation
            this.form.on('submit', (e) => {
                e.preventDefault();
                if (this.validateForm()) {
                    this.handleSubmit();
                }
            });

            // Input changes for auto-submit and state saving (excluding search input)
            this.form.find('input:not([name="search"]), select').on('change keyup', (e) => {
                if (e.type === 'keyup' && e.target.type === 'text') {
                    this.handleAutoSubmit();
                } else if (e.type === 'change') {
                    this.saveFilterState();
                    this.handleAutoSubmit();
                }
            });

            // Real-time validation on input
            this.form.find('input, select').on('input blur', (e) => {
                this.validateField(e.target);
            });

            // Checkbox changes
            this.form.find('input[type="checkbox"]').on('change', () => {
                this.saveFilterState();
                this.handleAutoSubmit();
            });

            // Prevent form submission on Enter key for text inputs (except search)
            this.form.find('input[type="text"]:not([name="search"])').on('keypress', (e) => {
                if (e.which === 13) {
                    e.preventDefault();
                    if (this.validateForm()) {
                        this.handleSubmit();
                    }
                }
            });

            // Save state on window unload
            $(window).on('beforeunload', () => {
                this.saveFilterState();
            });

            // Handle window resize for lazy loading
            $(window).on('resize', () => {
                this.debounceResize();
            });
            
            // Handle clicks outside search suggestions
            $(document).on('click', (e) => {
                if (!$(e.target).closest('.hbl-search-suggestions, input[name="search"]').length) {
                    this.hideSearchSuggestions();
                }
            });
            
            // Handle focus on search input to show history
            this.form.find('input[name="search"]').on('focus', (e) => {
                if (config.enableSearchSuggestions && !e.target.value) {
                    this.showSearchSuggestions('');
                }
            });
        }

        setupLazyLoading() {
            // Check if Intersection Observer is supported
            if ('IntersectionObserver' in window) {
                this.setupIntersectionObserver();
            } else {
                // Fallback to scroll-based lazy loading
                this.setupScrollBasedLazyLoading();
            }
        }

        setupIntersectionObserver() {
            const options = {
                root: null, // Use viewport as root
                rootMargin: `${config.lazyLoadThreshold}px`,
                threshold: 0.1
            };

            this.lazyLoadObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        this.loadLazyElement(entry.target);
                        this.lazyLoadObserver.unobserve(entry.target);
                    }
                });
            }, options);
        }

        setupScrollBasedLazyLoading() {
            // Fallback for browsers without Intersection Observer
            $(window).on('scroll', () => {
                if (this.scrollTimer) {
                    clearTimeout(this.scrollTimer);
                }
                
                this.scrollTimer = setTimeout(() => {
                    this.checkLazyLoadElements();
                }, 100);
            });
        }

        checkLazyLoadElements() {
            const windowHeight = $(window).height();
            const scrollTop = $(window).scrollTop();
            const threshold = config.lazyLoadThreshold;

            this.lazyLoadElements.forEach(element => {
                const $element = $(element);
                const elementTop = $element.offset().top;
                const elementHeight = $element.outerHeight();

                if (elementTop <= windowHeight + scrollTop + threshold) {
                    this.loadLazyElement(element);
                    this.lazyLoadElements.delete(element);
                }
            });
        }

        loadLazyElement(element) {
            const $element = $(element);
            
            if ($element.hasClass('hbl-lazy-image')) {
                this.loadLazyImage($element);
            } else if ($element.hasClass('hbl-lazy-content')) {
                this.loadLazyContent($element);
            }
        }

        loadLazyImage($img) {
            const src = $img.attr('data-src');
            const srcset = $img.attr('data-srcset');
            const sizes = $img.attr('data-sizes');
            
            if (src) {
                $img.attr('src', src);
            }
            
            if (srcset) {
                $img.attr('srcset', srcset);
            }
            
            if (sizes) {
                $img.attr('sizes', sizes);
            }
            
            $img.removeClass('hbl-lazy-image')
                .addClass('hbl-lazy-loaded')
                .removeAttr('data-src data-srcset data-sizes');
        }

        loadLazyContent($element) {
            const content = $element.attr('data-content');
            
            if (content) {
                $element.html(content);
                $element.removeClass('hbl-lazy-content')
                    .addClass('hbl-lazy-loaded')
                    .removeAttr('data-content');
            }
        }

        setupLazyElements() {
            // Setup advanced image optimization for new content
            this.resultsContainer.find('img[data-src], img[data-webp-src], img[data-avif-src]').each((index, img) => {
                const $img = $(img);
                const imageId = `img-${Date.now()}-${index}`;
                
                // Set unique ID for tracking
                $img.data('image-id', imageId);
                
                // Set placeholder if no src
                if (!$img.attr('src')) {
                    $img.attr('src', config.lazyLoadPlaceholder);
                }
                
                // Add loading class
                $img.addClass('hbl-lazy-image hbl-loading');
                
                // Observe with Intersection Observer
                if (this.imageObserver) {
                    this.imageObserver.observe(img);
                } else {
                    // Fallback to scroll-based loading
                    this.lazyLoadElements.add(img);
                }
            });

            // Setup lazy loading for content
            this.resultsContainer.find('[data-content]').each((index, element) => {
                const $element = $(element);
                $element.addClass('hbl-lazy-content');
                
                if (this.lazyLoadObserver) {
                    this.lazyLoadObserver.observe(element);
                } else {
                    this.lazyLoadElements.add(element);
                }
            });
            
            // Setup skeleton loading for missing images
            if (config.imageOptimization.enableSkeletonLoading) {
                this.setupSkeletonLoading();
            }
        }

        // Enhanced Image Optimization Methods
        setupAdvancedImageOptimization() {
            // Setup Intersection Observer for images
            this.setupImageObserver();
            
            // Setup progressive loading
            if (config.imageOptimization.enableProgressiveLoading) {
                this.setupProgressiveLoading();
            }
            
            // Setup skeleton loading
            if (config.imageOptimization.enableSkeletonLoading) {
                this.setupSkeletonLoading();
            }
            
            // Setup error handling
            if (config.imageOptimization.enableErrorFallbacks) {
                this.setupImageErrorHandling();
            }
        }

        setupImageObserver() {
            if (!('IntersectionObserver' in window)) {
                this.setupFallbackImageLoading();
                return;
            }

            const options = {
                root: null,
                rootMargin: `${config.imageOptimization.preloadThreshold}px`,
                threshold: [0, 0.1, 0.5, 1.0]
            };

            this.imageObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        this.loadOptimizedImage(entry.target);
                        this.imageObserver.unobserve(entry.target);
                    }
                });
            }, options);
        }

        loadOptimizedImage(imgElement) {
            const $img = $(imgElement);
            const imageId = $img.data('image-id') || Math.random().toString(36);
            
            // Check if already loaded or failed
            if (this.loadedImages.has(imageId) || this.failedImages.has(imageId)) {
                return;
            }
            
            // Add to load queue
            this.imageLoadQueue.set(imageId, {
                element: imgElement,
                attempts: 0,
                timestamp: Date.now()
            });
            
            this.processImageLoadQueue(imageId);
        }

        processImageLoadQueue(imageId) {
            const imageData = this.imageLoadQueue.get(imageId);
            if (!imageData) return;
            
            const $img = $(imageData.element);
            const attempts = imageData.attempts;
            
            if (attempts >= config.imageOptimization.retryAttempts) {
                this.handleImageLoadError($img, imageId);
                return;
            }
            
            // Update attempt count
            imageData.attempts++;
            this.imageLoadQueue.set(imageId, imageData);
            
            // Get optimized image URL
            const optimizedUrl = this.getOptimizedImageUrl($img);
            
            if (!optimizedUrl) {
                this.handleImageLoadError($img, imageId);
                return;
            }
            
            // Load image with timeout
            this.loadImageWithTimeout($img, optimizedUrl, imageId);
        }

        getOptimizedImageUrl($img) {
            // Check for WebP support and availability
            if (config.imageOptimization.enableWebP && this.webpSupport) {
                const webpUrl = $img.data('webp-src') || $img.data('webp-srcset');
                if (webpUrl) {
                    return webpUrl;
                }
            }
            
            // Check for AVIF support
            if (config.imageOptimization.enableAVIF && this.avifSupport) {
                const avifUrl = $img.data('avif-src') || $img.data('avif-srcset');
                if (avifUrl) {
                    return avifUrl;
                }
            }
            
            // Fallback to standard image
            return $img.data('src') || $img.attr('src');
        }

        loadImageWithTimeout($img, imageUrl, imageId) {
            const timeout = 10000; // 10 seconds timeout
            const startTime = Date.now();
            
            // Create temporary image for testing
            const tempImg = new Image();
            
            tempImg.onload = () => {
                const loadTime = Date.now() - startTime;
                this.handleImageLoadSuccess($img, imageUrl, imageId, loadTime);
            };
            
            tempImg.onerror = () => {
                const loadTime = Date.now() - startTime;
                this.handleImageLoadError($img, imageId, loadTime);
            };
            
            // Set timeout
            setTimeout(() => {
                if (tempImg.complete === false) {
                    tempImg.src = '';
                    this.handleImageLoadError($img, imageId, timeout);
                }
            }, timeout);
            
            tempImg.src = imageUrl;
        }

        handleImageLoadSuccess($img, imageUrl, imageId, loadTime) {
            // Remove from queue
            this.imageLoadQueue.delete(imageId);
            
            // Add to loaded images
            this.loadedImages.add(imageId);
            
            // Update image
            $img.attr('src', imageUrl)
                .removeClass('hbl-lazy-image hbl-loading')
                .addClass('hbl-loaded')
                .removeAttr('data-src data-webp-src data-avif-src');
            
            // Remove skeleton if exists
            this.removeSkeleton($img);
            
            // Trigger progressive loading if enabled
            if (config.imageOptimization.enableProgressiveLoading) {
                this.triggerProgressiveLoading($img, imageUrl);
            }
            
            // Log performance
            console.log(`Image loaded successfully: ${imageUrl} (${loadTime}ms)`);
        }

        handleImageLoadError($img, imageId, loadTime = 0) {
            // Remove from queue
            this.imageLoadQueue.delete(imageId);
            
            // Add to failed images
            this.failedImages.add(imageId);
            
            // Show error fallback
            this.showImageErrorFallback($img);
            
            // Log error
            console.error(`Image load failed: ${$img.data('src')} (${loadTime}ms)`);
        }

        setupProgressiveLoading() {
            // Setup progressive image loading
            this.resultsContainer.on('load', 'img.hbl-lazy-image', (e) => {
                const $img = $(e.target);
                const imageUrl = $img.attr('src');
                
                if (imageUrl && !imageUrl.includes('data:image')) {
                    this.progressiveImages.set(imageUrl, {
                        element: e.target,
                        loaded: true,
                        timestamp: Date.now()
                    });
                }
            });
        }

        triggerProgressiveLoading($img, imageUrl) {
            // Check if we have a higher quality version
            const highQualityUrl = $img.data('high-quality-src');
            
            if (highQualityUrl && highQualityUrl !== imageUrl) {
                // Load high quality version in background
                const highQualityImg = new Image();
                highQualityImg.onload = () => {
                    $img.attr('src', highQualityUrl)
                        .addClass('hbl-progressive-loaded');
                };
                highQualityImg.src = highQualityUrl;
            }
        }

        setupSkeletonLoading() {
            // Replace missing images with skeleton
            this.resultsContainer.find('.hbl-business-image:not(:has(img))').each((index, element) => {
                const $element = $(element);
                const skeletonId = `skeleton-${Date.now()}-${index}`;
                
                $element.html(`
                    <div class="hbl-skeleton-image" data-skeleton-id="${skeletonId}">
                        <div class="hbl-skeleton-shimmer"></div>
                    </div>
                `);
                
                this.skeletonElements.add(skeletonId);
            });
        }

        removeSkeleton($img) {
            const $skeleton = $img.closest('.hbl-business-image').find('.hbl-skeleton-image');
            if ($skeleton.length) {
                const skeletonId = $skeleton.data('skeleton-id');
                this.skeletonElements.delete(skeletonId);
                $skeleton.fadeOut(() => $skeleton.remove());
            }
        }

        setupImageErrorHandling() {
            // Handle image errors
            this.resultsContainer.on('error', 'img', (e) => {
                const $img = $(e.target);
                const imageId = $img.data('image-id') || Math.random().toString(36);
                
                // Retry loading
                if (this.imageLoadQueue.has(imageId)) {
                    const imageData = this.imageLoadQueue.get(imageId);
                    if (imageData.attempts < config.imageOptimization.retryAttempts) {
                        setTimeout(() => {
                            this.processImageLoadQueue(imageId);
                        }, config.imageOptimization.retryDelay);
                        return;
                    }
                }
                
                this.showImageErrorFallback($img);
            });
        }

        showImageErrorFallback($img) {
            // Show placeholder image
            $img.attr('src', config.lazyLoadPlaceholder)
                .addClass('hbl-image-error')
                .removeClass('hbl-lazy-image hbl-loading');
            
            // Add error indicator
            $img.after(`
                <div class="hbl-image-error-indicator">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="15" y1="9" x2="9" y2="15"></line>
                        <line x1="9" y1="9" x2="15" y2="15"></line>
                    </svg>
                    <span>Image unavailable</span>
                </div>
            `);
        }

        setupFallbackImageLoading() {
            // Fallback for browsers without Intersection Observer
            $(window).on('scroll', () => {
                if (this.scrollTimer) {
                    clearTimeout(this.scrollTimer);
                }
                
                this.scrollTimer = setTimeout(() => {
                    this.checkFallbackImages();
                }, 100);
            });
        }

        checkFallbackImages() {
            const windowHeight = $(window).height();
            const scrollTop = $(window).scrollTop();
            const threshold = config.imageOptimization.preloadThreshold;

            this.resultsContainer.find('img.hbl-lazy-image').each((index, img) => {
                const $img = $(img);
                const elementTop = $img.offset().top;
                const elementHeight = $img.outerHeight();

                if (elementTop <= windowHeight + scrollTop + threshold) {
                    this.loadOptimizedImage(img);
                }
            });
        }

        checkWebPSupport() {
            const canvas = document.createElement('canvas');
            canvas.width = 1;
            canvas.height = 1;
            return canvas.toDataURL('image/webp').indexOf('data:image/webp') === 0;
        }

        checkAVIFSupport() {
            // Basic AVIF support check
            return 'avif' in new Image();
        }

        // Performance monitoring
        getImageLoadStats() {
            return {
                loaded: this.loadedImages.size,
                failed: this.failedImages.size,
                queued: this.imageLoadQueue.size,
                skeletons: this.skeletonElements.size,
                progressive: this.progressiveImages.size
            };
        }

        // Advanced Features Methods
        setupAdvancedFeatures() {
            if (config.advancedFeatures.enableMultiSelect) {
                this.setupMultiSelectFilters();
            }
            
            if (config.advancedFeatures.enableChips) {
                this.setupChipsDisplay();
            }
            
            if (config.advancedFeatures.enableAnalytics) {
                this.setupSearchAnalytics();
            }
            
            if (config.advancedFeatures.enableExport) {
                this.setupExportFunctionality();
            }
            
            if (config.advancedFeatures.enablePresets) {
                this.setupFilterPresets();
            }
            
            if (config.advancedFeatures.enableAdvancedSorting) {
                this.setupAdvancedSorting();
            }
        }

        // Multi-select filter functionality
        setupMultiSelectFilters() {
            this.form.find('.hbl-multi-select').each((index, select) => {
                const $select = $(select);
                const fieldName = $select.attr('name');
                
                this.multiSelectElements.add(fieldName);
                
                // Handle multi-select changes
                $select.on('change', (e) => {
                    this.handleMultiSelectChange($select);
                });
                
                // Initialize chips for existing selections
                this.updateChipsForField(fieldName);
            });
        }

        handleMultiSelectChange($select) {
            const fieldName = $select.attr('name');
            const selectedValues = $select.val() || [];
            
            // Update chips
            this.updateChipsForField(fieldName, selectedValues);
            
            // Save state and trigger search
            this.saveFilterState();
            this.handleAutoSubmit();
        }

        // Chips display functionality
        setupChipsDisplay() {
            // Handle chip removal
            $(document).on('click', '.hbl-chip-remove', (e) => {
                e.preventDefault();
                e.stopPropagation();
                
                const $chip = $(e.target).closest('.hbl-chip');
                const fieldName = $chip.data('field');
                const value = $chip.data('value');
                
                this.removeChip(fieldName, value);
            });
        }

        updateChipsForField(fieldName, selectedValues = null) {
            const $select = this.form.find(`[name="${fieldName}"]`);
            const $chipsContainer = this.form.find(`#hbl-${fieldName.replace('[]', '')}-chips`);
            
            if (!$select.length || !$chipsContainer.length) {
                return;
            }
            
            const values = selectedValues || $select.val() || [];
            
            // Clear existing chips
            $chipsContainer.empty();
            
            // Add chips for selected values
            values.forEach(value => {
                const $option = $select.find(`option[value="${value}"]`);
                const label = $option.text();
                
                const chip = this.createChip(fieldName, value, label);
                $chipsContainer.append(chip);
            });
            
            // Store selected chips
            this.selectedChips.set(fieldName, values);
        }

        createChip(fieldName, value, label) {
            return $(`
                <div class="hbl-chip" data-field="${fieldName}" data-value="${value}">
                    <span class="hbl-chip-label">${label}</span>
                    <button type="button" class="hbl-chip-remove" aria-label="Remove ${label}">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 6L6 18M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            `);
        }

        removeChip(fieldName, value) {
            const $select = this.form.find(`[name="${fieldName}"]`);
            const $chip = this.form.find(`.hbl-chip[data-field="${fieldName}"][data-value="${value}"]`);
            
            // Remove from select
            $select.find(`option[value="${value}"]`).prop('selected', false);
            
            // Remove chip with animation
            if (config.advancedFeatures.chipAnimation) {
                $chip.fadeOut(() => {
                    $chip.remove();
                    this.handleMultiSelectChange($select);
                });
            } else {
                $chip.remove();
                this.handleMultiSelectChange($select);
            }
        }

        // Search analytics functionality
        setupSearchAnalytics() {
            // Load initial analytics
            this.loadSearchAnalytics();
            
            // Set up periodic refresh
            this.analyticsTimer = setInterval(() => {
                this.loadSearchAnalytics();
            }, config.advancedFeatures.analyticsRefreshInterval);
            
            // Handle analytics toggle
            $('#hbl-analytics-toggle').on('click', () => {
                this.toggleAnalytics();
            });
        }

        loadSearchAnalytics() {
            $.ajax({
                url: hsfAdvancedFilter.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'hsf_get_analytics',
                    nonce: hsfAdvancedFilter.analyticsNonce
                },
                success: (response) => {
                    if (response.success) {
                        this.updateAnalyticsDisplay(response.data);
                    }
                },
                error: (xhr, status, error) => {
                    console.warn('Failed to load analytics:', error);
                }
            });
        }

        updateAnalyticsDisplay(analytics) {
            this.analyticsData = analytics;
            
            // Update stats
            $('#hbl-total-searches').text(analytics.total_searches);
            $('#hbl-avg-results').text(analytics.avg_results);
            
            // Update popular terms
            const popularTerms = Object.keys(analytics.popular_terms).slice(0, 5);
            $('#hbl-popular-terms').text(popularTerms.join(', ') || '-');
            
            // Update popular searches list
            this.updatePopularSearches(analytics.popular_terms);
        }

        updatePopularSearches(popularTerms) {
            const $container = $('#hbl-popular-searches');
            $container.empty();
            
            if (Object.keys(popularTerms).length === 0) {
                $container.html('<p class="hbl-no-popular-searches">No popular searches yet.</p>');
                return;
            }
            
            const searchesHtml = Object.entries(popularTerms).map(([term, count]) => `
                <div class="hbl-popular-search-item">
                    <span class="hbl-search-term">${term}</span>
                    <span class="hbl-search-count">${count}</span>
                </div>
            `).join('');
            
            $container.html(searchesHtml);
        }

        toggleAnalytics() {
            const $analytics = $('#hbl-search-analytics');
            const $content = $('#hbl-analytics-content');
            const $toggle = $('#hbl-analytics-toggle');
            
            if ($analytics.is(':visible')) {
                $content.slideUp();
                $toggle.find('svg').css('transform', 'rotate(0deg)');
            } else {
                $analytics.show();
                $content.slideDown();
                $toggle.find('svg').css('transform', 'rotate(180deg)');
            }
        }

        // Export functionality
        setupExportFunctionality() {
            $('#hbl-export-csv').on('click', () => {
                this.exportResults('csv');
            });
            
            $('#hbl-export-pdf').on('click', () => {
                this.exportResults('pdf');
            });
        }

        exportResults(format) {
            if (this.exportResults.length === 0) {
                this.showNotification('No results to export. Please perform a search first.', 'warning');
                return;
            }
            
            // Show loading state
            this.showNotification(`Preparing ${format.toUpperCase()} export...`, 'info');
            
            // Create form data for export
            const formData = new FormData();
            formData.append('action', `hsf_export_${format}`);
            formData.append('nonce', hsfAdvancedFilter.exportNonce);
            formData.append('results', JSON.stringify(this.exportResults));
            
            // Submit export request
            $.ajax({
                url: hsfAdvancedFilter.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: (response) => {
                    // Handle file download
                    this.downloadFile(response, `business-listings.${format}`);
                    this.showNotification(`${format.toUpperCase()} export completed!`, 'success');
                },
                error: (xhr, status, error) => {
                    this.showNotification(`Export failed: ${error}`, 'error');
                }
            });
        }

        downloadFile(content, filename) {
            const blob = new Blob([content], { type: 'text/plain' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
        }

        // Filter presets functionality
        setupFilterPresets() {
            $('.hbl-preset-button').on('click', (e) => {
                const preset = $(e.target).closest('.hbl-preset-button').data('preset');
                this.applyFilterPreset(preset);
            });
        }

        applyFilterPreset(preset) {
            const presetConfig = config.advancedFeatures.presetConfigurations[preset];
            
            if (!presetConfig) {
                console.warn(`Unknown preset: ${preset}`);
                return;
            }
            
            // Reset form first
            this.form[0].reset();
            
            // Apply preset configuration
            Object.keys(presetConfig).forEach(key => {
                const value = presetConfig[key];
                const $field = this.form.find(`[name="${key}"]`);
                
                if ($field.length) {
                    if ($field.attr('type') === 'checkbox') {
                        $field.prop('checked', value);
                    } else {
                        $field.val(value);
                    }
                }
            });
            
            // Update chips
            this.multiSelectElements.forEach(fieldName => {
                this.updateChipsForField(fieldName);
            });
            
            // Mark preset as active
            this.presetActive = preset;
            $('.hbl-preset-button').removeClass('active');
            $(`.hbl-preset-button[data-preset="${preset}"]`).addClass('active');
            
            // Trigger search
            this.saveFilterState();
            this.handleSubmit(1);
            
            this.showNotification(`Applied "${preset}" filter preset`, 'success');
        }

        // Advanced sorting functionality
        setupAdvancedSorting() {
            // Handle secondary sorting changes
            $('#hsf-filter-secondary-orderby').on('change', () => {
                this.saveFilterState();
                this.handleAutoSubmit();
            });
            
            // Handle order direction changes
            $('#hsf-filter-order').on('change', () => {
                this.saveFilterState();
                this.handleAutoSubmit();
            });
        }

        // Enhanced form data collection for multi-select
        getFormData() {
            const formData = {};
            const formArray = this.form.serializeArray();
            formArray.forEach(item => {
                if (item.value) {
                    if (formData[item.name]) {
                        if (Array.isArray(formData[item.name])) {
                            formData[item.name].push(item.value);
                        } else {
                            formData[item.name] = [formData[item.name], item.value];
                        }
                    } else {
                        formData[item.name] = item.value;
                    }
                }
            });
            
            // Handle multi-select arrays properly
            this.multiSelectElements.forEach(fieldName => {
                const $select = this.form.find(`[name="${fieldName}"]`);
                const selectedValues = $select.val() || [];
                if (selectedValues.length > 0) {
                    formData[fieldName] = selectedValues;
                }
            });
            return formData;
        }

        // Enhanced success handler to collect export data
        handleSuccess(response, isNewSearch) {
            if (response.success) {
                if (isNewSearch) {
                    this.resultsContainer.html(response.data.html);
                    this.collectExportData(response.data); // Collect export data
                } else {
                    this.appendResults(response.data.html);
                }
                
                // Setup lazy loading for new content
                this.setupLazyElements();
                
                // Update pagination info
                this.currentPage = response.data.current_page || 1;
                this.totalPages = response.data.total_pages || 1;
                
                // Update ARIA label
                this.resultsContainer.attr('aria-label', `Found ${response.data.count || 0} results`);
                
                // Update pagination controls
                this.updatePaginationControls();
                
                // Show cache status if available
                if (response.data.cached !== undefined) {
                    this.showCacheStatus(response.data.cached, response.data.cache_timestamp);
                }
                
                // Scroll to top for new searches
                if (isNewSearch) {
                    this.scrollToResults();
                }
                
                // Trigger custom event
                $(document).trigger('hsf:searchComplete', [response.data]);
            } else {
                this.showError(response.data || 'Search failed. Please try again.');
            }
        }

        collectExportData(data) {
            // Extract business data from HTML for export
            const $results = this.resultsContainer.find('.hbl-business-card');
            this.exportResults = [];
            
            $results.each((index, card) => {
                const $card = $(card);
                const $title = $card.find('.hbl-business-title a');
                const $excerpt = $card.find('.hbl-business-excerpt');
                const $location = $card.find('.hbl-business-location');
                const $type = $card.find('.hbl-business-type');
                const $rating = $card.find('.hbl-rating-text');
                const $categories = $card.find('.hbl-business-categories');
                const $verified = $card.find('.hbl-verified-badge');
                
                this.exportResults.push({
                    id: index + 1,
                    title: $title.text().trim(),
                    excerpt: $excerpt.text().trim(),
                    location: $location.text().replace('📍', '').trim(),
                    company_type: $type.text().trim(),
                    rating: $rating.text().trim(),
                    categories: $categories.text().trim(),
                    verified: $verified.length > 0,
                    url: $title.attr('href'),
                    date: new Date().toISOString().split('T')[0]
                });
            });
        }

        // Cleanup method for advanced features
        cleanup() {
            // Clear analytics timer
            if (this.analyticsTimer) {
                clearInterval(this.analyticsTimer);
            }
            
            // Clear other timers
            if (this.imageObserver) {
                this.imageObserver.disconnect();
            }
        }

        debounceResize() {
            if (this.resizeTimer) {
                clearTimeout(this.resizeTimer);
            }
            
            this.resizeTimer = setTimeout(() => {
                this.checkLazyLoadElements();
            }, 250);
        }

        setupValidation() {
            // Add validation attributes to form fields
            this.form.find('input[name="search"]').attr({
                'minlength': validationRules.search.minLength,
                'maxlength': validationRules.search.maxLength,
                'pattern': validationRules.search.pattern.source
            });

            this.form.find('input[name="location"]').attr({
                'minlength': validationRules.location.minLength,
                'maxlength': validationRules.location.maxLength,
                'pattern': validationRules.location.pattern.source
            });

            this.form.find('select[name="rating_min"]').attr({
                'min': validationRules.rating_min.min,
                'max': validationRules.rating_min.max
            });

            this.form.find('input[name="results_per_page"]').attr({
                'min': validationRules.results_per_page.min,
                'max': validationRules.results_per_page.max,
                'type': 'number'
            });
        }

        validateField(field) {
            const fieldName = field.name;
            const fieldValue = field.value.trim();
            const rules = validationRules[fieldName];

            if (!rules) {
                return true; // No validation rules for this field
            }

            let isValid = true;
            let errorMessage = '';

            // Check if field is required and empty
            if (field.hasAttribute('required') && !fieldValue) {
                isValid = false;
                errorMessage = 'This field is required';
            } else if (fieldValue) {
                // Check minimum length
                if (rules.minLength && fieldValue.length < rules.minLength) {
                    isValid = false;
                    errorMessage = rules.messages.minLength;
                }
                // Check maximum length
                else if (rules.maxLength && fieldValue.length > rules.maxLength) {
                    isValid = false;
                    errorMessage = rules.messages.maxLength;
                }
                // Check minimum value
                else if (rules.min && parseFloat(fieldValue) < rules.min) {
                    isValid = false;
                    errorMessage = rules.messages.min;
                }
                // Check maximum value
                else if (rules.max && parseFloat(fieldValue) > rules.max) {
                    isValid = false;
                    errorMessage = rules.messages.max;
                }
                // Check pattern
                else if (rules.pattern && !rules.pattern.test(fieldValue)) {
                    isValid = false;
                    errorMessage = rules.messages.pattern;
                }
            }

            this.showFieldValidation(field, isValid, errorMessage);
            return isValid;
        }

        validateForm() {
            let isValid = true;
            this.validationErrors = {};

            // Validate all form fields
            this.form.find('input, select').each((index, field) => {
                if (!this.validateField(field)) {
                    isValid = false;
                    this.validationErrors[field.name] = true;
                }
            });

            // Show form-level validation message
            if (!isValid) {
                this.showFormValidationError('Please correct the errors above before submitting.');
            } else {
                this.hideFormValidationError();
            }

            return isValid;
        }

        showFieldValidation(field, isValid, errorMessage) {
            const fieldContainer = $(field).closest('.hbl-filter-field');
            
            // Remove existing validation classes and messages
            fieldContainer.removeClass(config.validationClass);
            fieldContainer.find('.' + config.validationMessageClass).remove();

            if (!isValid) {
                // Add validation error class
                fieldContainer.addClass(config.validationClass);
                
                // Add error message
                const errorElement = $(`
                    <div class="${config.validationMessageClass}">
                        <svg class="hbl-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="15" y1="9" x2="9" y2="15"></line>
                            <line x1="9" y1="9" x2="15" y2="15"></line>
                        </svg>
                        <span>${errorMessage}</span>
                    </div>
                `);
                
                fieldContainer.append(errorElement);
                
                // Add error state to field
                $(field).addClass('hbl-field-error');
            } else {
                // Remove error state from field
                $(field).removeClass('hbl-field-error');
            }
        }

        showFormValidationError(message) {
            // Remove existing form validation message
            this.hideFormValidationError();
            
            const errorElement = $(`
                <div class="hbl-form-validation-error">
                    <svg class="hbl-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="15" y1="9" x2="9" y2="15"></line>
                        <line x1="9" y1="9" x2="15" y2="15"></line>
                    </svg>
                    <span>${message}</span>
                </div>
            `);
            
            this.form.prepend(errorElement);
        }

        hideFormValidationError() {
            this.form.find('.hbl-form-validation-error').remove();
        }

        setupAutoSubmit() {
            const autoSubmit = this.form.data('auto-submit');
            if (autoSubmit === 'false') {
                return;
            }

            // Clear existing timer
            if (this.autoSubmitTimer) {
                clearTimeout(this.autoSubmitTimer);
            }

            // Set new timer
            this.autoSubmitTimer = setTimeout(() => {
                if (this.validateForm()) {
                    this.handleSubmit();
                }
            }, config.autoSubmitDelay);
        }

        // Enhanced debouncing methods
        setupEnhancedDebouncing() {
            // Setup intelligent debouncing for search input
            const searchInput = this.form.find('input[name="search"]');
            
            if (searchInput.length) {
                searchInput.on('input', (e) => {
                    this.handleSearchInput(e.target.value);
                });
                
                searchInput.on('keydown', (e) => {
                    // Handle Enter key for immediate search
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        this.performImmediateSearch(e.target.value);
                    }
                    
                    // Handle Escape key to clear search
                    if (e.key === 'Escape') {
                        e.target.value = '';
                        this.clearSearchResults();
                    }
                });
                
                // Setup search suggestions if enabled
                if (config.enableSearchSuggestions) {
                    this.setupSearchSuggestions(searchInput);
                }
            }
        }

        handleSearchInput(searchTerm) {
            // Clear previous timers
            if (this.searchDebounceTimer) {
                clearTimeout(this.searchDebounceTimer);
            }
            
            if (this.searchSuggestionTimer) {
                clearTimeout(this.searchSuggestionTimer);
            }
            
            // Validate search term length
            if (searchTerm.length > config.maxSearchLength) {
                searchTerm = searchTerm.substring(0, config.maxSearchLength);
                this.form.find('input[name="search"]').val(searchTerm);
            }
            
            // Don't search if term is too short
            if (searchTerm.length < config.minSearchLength) {
                this.clearSearchResults();
                this.hideSearchSuggestions();
                return;
            }
            
            // Check if search term has actually changed
            if (searchTerm === this.lastSearchTerm) {
                return;
            }
            
            this.lastSearchTerm = searchTerm;
            
            // Show search suggestions if enabled
            if (config.enableSearchSuggestions) {
                this.searchSuggestionTimer = setTimeout(() => {
                    this.showSearchSuggestions(searchTerm);
                }, config.searchSuggestionDelay);
            }
            
            // Debounced search
            this.searchDebounceTimer = setTimeout(() => {
                this.performDebouncedSearch(searchTerm);
            }, config.searchDebounceDelay);
        }

        performDebouncedSearch(searchTerm) {
            // Prevent duplicate searches
            if (this.isSearching && this.pendingSearch === searchTerm) {
                return;
            }
            
            // If currently searching, queue this search
            if (this.isSearching) {
                this.pendingSearch = searchTerm;
                return;
            }
            
            this.isSearching = true;
            
            // Update form value
            this.form.find('input[name="search"]').val(searchTerm);
            
            // Perform search
            this.handleSubmit(1).then(() => {
                this.isSearching = false;
                
                // Add to search history
                this.addToSearchHistory(searchTerm);
                
                // Process pending search if any
                if (this.pendingSearch && this.pendingSearch !== searchTerm) {
                    const pendingTerm = this.pendingSearch;
                    this.pendingSearch = null;
                    setTimeout(() => {
                        this.performDebouncedSearch(pendingTerm);
                    }, 100);
                }
            }).catch(() => {
                this.isSearching = false;
                this.pendingSearch = null;
            });
        }

        performImmediateSearch(searchTerm) {
            // Clear any pending debounced searches
            if (this.searchDebounceTimer) {
                clearTimeout(this.searchDebounceTimer);
            }
            
            if (this.searchSuggestionTimer) {
                clearTimeout(this.searchSuggestionTimer);
            }
            
            // Perform immediate search
            this.form.find('input[name="search"]').val(searchTerm);
            this.handleSubmit(1);
            
            // Add to search history
            this.addToSearchHistory(searchTerm);
            
            // Hide suggestions
            this.hideSearchSuggestions();
        }

        clearSearchResults() {
            this.resultsContainer.empty();
            this.currentPage = 1;
            this.totalPages = 1;
        }

        // Search suggestions functionality
        setupSearchSuggestions(searchInput) {
            // Create suggestions container
            const suggestionsContainer = $(`
                <div class="hbl-search-suggestions" id="hbl-search-suggestions" style="display: none;">
                    <div class="hbl-suggestions-list"></div>
                    <div class="hbl-search-history">
                        <h4>Recent Searches</h4>
                        <div class="hbl-history-list"></div>
                    </div>
                </div>
            `);
            
            searchInput.after(suggestionsContainer);
            
            // Handle suggestion clicks
            suggestionsContainer.on('click', '.hbl-suggestion-item', (e) => {
                const suggestion = $(e.currentTarget).data('suggestion');
                searchInput.val(suggestion);
                this.performImmediateSearch(suggestion);
            });
            
            // Handle history item clicks
            suggestionsContainer.on('click', '.hbl-history-item', (e) => {
                const historyItem = $(e.currentTarget).data('history');
                searchInput.val(historyItem);
                this.performImmediateSearch(historyItem);
            });
            
            // Handle clear history
            suggestionsContainer.on('click', '.hbl-clear-history', (e) => {
                e.stopPropagation();
                this.clearSearchHistory();
                this.updateSearchHistoryDisplay();
            });
        }

        showSearchSuggestions(searchTerm) {
            const suggestionsContainer = $('#hbl-search-suggestions');
            const suggestionsList = suggestionsContainer.find('.hbl-suggestions-list');
            
            // Generate suggestions based on search term
            this.generateSearchSuggestions(searchTerm).then(suggestions => {
                if (suggestions.length > 0) {
                    const suggestionsHtml = suggestions.map(suggestion => `
                        <div class="hbl-suggestion-item" data-suggestion="${suggestion}">
                            <svg class="hbl-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="11" cy="11" r="8"></circle>
                                <path d="m21 21-4.35-4.35"></path>
                            </svg>
                            <span>${suggestion}</span>
                        </div>
                    `).join('');
                    
                    suggestionsList.html(suggestionsHtml);
                } else {
                    suggestionsList.html('<div class="hbl-no-suggestions">No suggestions found</div>');
                }
                
                // Update history display
                this.updateSearchHistoryDisplay();
                
                // Show container
                suggestionsContainer.show();
            });
        }

        hideSearchSuggestions() {
            $('#hbl-search-suggestions').hide();
        }

        generateSearchSuggestions(searchTerm) {
            // This would typically call an API for suggestions
            // For now, we'll generate simple suggestions based on search history
            return new Promise((resolve) => {
                const suggestions = [];
                
                // Add search history items that match
                this.searchHistory.forEach(item => {
                    if (item.toLowerCase().includes(searchTerm.toLowerCase()) && 
                        item !== searchTerm && 
                        suggestions.length < config.maxSearchSuggestions) {
                        suggestions.push(item);
                    }
                });
                
                // Add some generic suggestions
                const genericSuggestions = [
                    `${searchTerm} near me`,
                    `${searchTerm} services`,
                    `${searchTerm} companies`,
                    `${searchTerm} businesses`
                ];
                
                genericSuggestions.forEach(suggestion => {
                    if (suggestions.length < config.maxSearchSuggestions) {
                        suggestions.push(suggestion);
                    }
                });
                
                resolve(suggestions);
            });
        }

        // Search history functionality
        loadSearchHistory() {
            try {
                const history = localStorage.getItem('hsf_search_history');
                return history ? JSON.parse(history) : [];
            } catch (e) {
                console.warn('Could not load search history:', e);
                return [];
            }
        }

        saveSearchHistory() {
            try {
                localStorage.setItem('hsf_search_history', JSON.stringify(this.searchHistory));
            } catch (e) {
                console.warn('Could not save search history:', e);
            }
        }

        addToSearchHistory(searchTerm) {
            if (!config.enableSearchHistory || !searchTerm.trim()) {
                return;
            }
            
            // Remove if already exists
            this.searchHistory = this.searchHistory.filter(item => item !== searchTerm);
            
            // Add to beginning
            this.searchHistory.unshift(searchTerm);
            
            // Limit history size
            if (this.searchHistory.length > config.maxSearchHistory) {
                this.searchHistory = this.searchHistory.slice(0, config.maxSearchHistory);
            }
            
            this.saveSearchHistory();
        }

        clearSearchHistory() {
            this.searchHistory = [];
            this.saveSearchHistory();
        }

        updateSearchHistoryDisplay() {
            const historyList = $('#hbl-search-suggestions .hbl-history-list');
            
            if (this.searchHistory.length > 0) {
                const historyHtml = this.searchHistory.map(item => `
                    <div class="hbl-history-item" data-history="${item}">
                        <svg class="hbl-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 8v4l3 3"></path>
                            <circle cx="12" cy="12" r="10"></circle>
                        </svg>
                        <span>${item}</span>
                    </div>
                `).join('');
                
                historyList.html(historyHtml);
            } else {
                historyList.html('<div class="hbl-no-history">No recent searches</div>');
            }
        }

        setupReset() {
            this.form.find('.hbl-reset-button').on('click', (e) => {
                e.preventDefault();
                this.handleReset();
            });
        }

        setupAccessibility() {
            // Add ARIA labels and roles
            this.form.attr('role', 'search');
            this.form.attr('aria-label', 'Business search and filter form');

            // Add live region for results
            if (this.resultsContainer.length) {
                this.resultsContainer.attr('aria-live', 'polite');
                this.resultsContainer.attr('aria-atomic', 'true');
            }

            // Keyboard navigation for dropdowns
            this.form.find('select').on('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    $(e.target).trigger('change');
                }
            });
            
            // Setup saved filters functionality
            this.setupSavedFilters();
        }

        setupSavedFilters() {
            const saveFilterBtn = $('#hbl-save-filter-btn');
            const savedFiltersDropdown = $('#hbl-saved-filters-dropdown');
            const closeDropdownBtn = $('#hbl-close-dropdown');
            const savedFiltersList = $('#hbl-saved-filters-list');
            const savedFiltersEmpty = $('#hbl-saved-filters-empty');
            
            if (saveFilterBtn.length) {
                // Save filter button click
                saveFilterBtn.on('click', () => {
                    this.saveCurrentFilter();
                });
                
                // Toggle saved filters dropdown
                saveFilterBtn.on('click', (e) => {
                    e.stopPropagation();
                    savedFiltersDropdown.toggle();
                    if (savedFiltersDropdown.is(':visible')) {
                        this.loadSavedFilters();
                    }
                });
                
                // Close dropdown
                closeDropdownBtn.on('click', () => {
                    savedFiltersDropdown.hide();
                });
                
                // Close dropdown when clicking outside
                $(document).on('click', (e) => {
                    if (!$(e.target).closest('.hbl-saved-filters-controls').length) {
                        savedFiltersDropdown.hide();
                    }
                });
            }
        }

        saveCurrentFilter() {
            const filterName = prompt(hsfAdvancedFilter.i18n.saveFilterPrompt || 'Enter a name for this filter:');
            
            if (!filterName) {
                return;
            }
            
            const formData = this.getFormData();
            
            $.ajax({
                url: hsfAdvancedFilter.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'hsf_save_filter',
                    nonce: hsfAdvancedFilter.savedNonce,
                    name: filterName,
                    params: formData
                },
                success: (response) => {
                    if (response.success) {
                        this.showNotification('Filter saved successfully!', 'success');
                        this.loadSavedFilters();
                    } else {
                        this.showNotification('Failed to save filter. Please try again.', 'error');
                    }
                },
                error: () => {
                    this.showNotification('Failed to save filter. Please try again.', 'error');
                }
            });
        }

        loadSavedFilters() {
            const savedFiltersList = $('#hbl-saved-filters-list');
            const savedFiltersEmpty = $('#hbl-saved-filters-empty');
            
            $.ajax({
                url: hsfAdvancedFilter.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'hsf_get_filters',
                    nonce: hsfAdvancedFilter.savedNonce
                },
                success: (response) => {
                    if (response.success && response.data.length > 0) {
                        savedFiltersEmpty.hide();
                        savedFiltersList.show();
                        
                        const filtersHtml = response.data.map(filter => `
                            <div class="hbl-saved-filter-dropdown-item" data-filter-id="${filter.id}">
                                <div class="hbl-filter-info">
                                    <div class="hbl-filter-name">${filter.name}</div>
                                    <div class="hbl-filter-date">${this.formatDate(filter.created)}</div>
                                </div>
                                <button type="button" class="hbl-delete-filter" data-filter-id="${filter.id}">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                    </svg>
                                </button>
                            </div>
                        `).join('');
                        
                        savedFiltersList.html(filtersHtml);
                        
                        // Bind events for saved filter items
                        savedFiltersList.find('.hbl-saved-filter-dropdown-item').on('click', (e) => {
                            if (!$(e.target).closest('.hbl-delete-filter').length) {
                                this.loadSavedFilter(filter);
                            }
                        });
                        
                        savedFiltersList.find('.hbl-delete-filter').on('click', (e) => {
                            e.stopPropagation();
                            this.deleteSavedFilter($(e.target).closest('.hbl-delete-filter').data('filter-id'));
                        });
                    } else {
                        savedFiltersList.hide();
                        savedFiltersEmpty.show();
                    }
                },
                error: () => {
                    savedFiltersList.hide();
                    savedFiltersEmpty.show();
                }
            });
        }

        loadSavedFilter(filter) {
            // Apply the saved filter parameters
            Object.keys(filter.params).forEach(key => {
                const field = this.form.find(`[name="${key}"]`);
                if (field.length) {
                    if (field.attr('type') === 'checkbox') {
                        field.prop('checked', filter.params[key] === '1' || filter.params[key] === true);
                    } else if (field.attr('type') === 'radio') {
                        field.filter(`[value="${filter.params[key]}"]`).prop('checked', true);
                    } else {
                        field.val(filter.params[key]);
                    }
                }
            });
            
            // Trigger search with saved filter
            this.handleSubmit(1);
            
            // Close dropdown
            $('#hbl-saved-filters-dropdown').hide();
            
            this.showNotification(`Filter "${filter.name}" applied!`, 'success');
        }

        deleteSavedFilter(filterId) {
            if (!confirm(hsfAdvancedFilter.i18n.deleteFilterConfirm || 'Are you sure you want to delete this saved filter?')) {
                return;
            }
            
            $.ajax({
                url: hsfAdvancedFilter.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'hsf_delete_filter',
                    nonce: hsfAdvancedFilter.savedNonce,
                    id: filterId
                },
                success: (response) => {
                    if (response.success) {
                        this.showNotification('Filter deleted successfully!', 'success');
                        this.loadSavedFilters();
                    } else {
                        this.showNotification('Failed to delete filter. Please try again.', 'error');
                    }
                },
                error: () => {
                    this.showNotification('Failed to delete filter. Please try again.', 'error');
                }
            });
        }

        formatDate(timestamp) {
            const date = new Date(timestamp * 1000);
            return date.toLocaleDateString();
        }

        showNotification(message, type = 'info') {
            const notification = $(`
                <div class="hbl-notification hbl-notification-${type}" role="alert">
                    <span>${message}</span>
                    <button type="button" class="hbl-notification-close">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 6L6 18M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            `);
            
            // Remove existing notifications
            $('.hbl-notification').remove();
            
            // Add notification to the form
            this.form.prepend(notification);
            
            // Auto-hide after 3 seconds
            setTimeout(() => {
                notification.fadeOut(() => notification.remove());
            }, 3000);
            
            // Handle close button
            notification.find('.hbl-notification-close').on('click', () => {
                notification.fadeOut(() => notification.remove());
            });
        }

        setupPagination() {
            // Handle pagination clicks
            $(document).on('click', config.paginationSelector, (e) => {
                e.preventDefault();
                const page = $(e.target).data('page') || $(e.target).text();
                if (page && !isNaN(page)) {
                    this.loadPage(parseInt(page));
                }
            });

            // Handle load more button
            $(document).on('click', config.loadMoreSelector, (e) => {
                e.preventDefault();
                this.loadNextPage();
            });
        }

        setupInfiniteScroll() {
            // Check if infinite scroll is enabled
            if (this.form.data('infinite-scroll') === 'true') {
                this.isInfiniteScroll = true;
                this.bindInfiniteScroll();
            }
        }

        bindInfiniteScroll() {
            $(window).on('scroll', () => {
                if (this.scrollTimer) {
                    clearTimeout(this.scrollTimer);
                }
                
                this.scrollTimer = setTimeout(() => {
                    this.checkInfiniteScroll();
                }, 100);
            });
        }

        checkInfiniteScroll() {
            if (this.isLoading || this.currentPage >= this.totalPages) {
                return;
            }

            const scrollTop = $(window).scrollTop();
            const windowHeight = $(window).height();
            const documentHeight = $(document).height();
            const threshold = config.infiniteScrollThreshold;

            if (scrollTop + windowHeight >= documentHeight - threshold) {
                this.loadNextPage();
            }
        }

        // Filter State Management Methods
        saveFilterState() {
            if (this.isRestoringState) {
                return; // Don't save while restoring
            }

            const formData = this.getFormData();
            const state = {
                timestamp: Date.now(),
                formData: formData,
                url: window.location.pathname
            };

            // Save to localStorage
            try {
                localStorage.setItem(config.storageKey, JSON.stringify(state));
            } catch (e) {
                console.warn('Could not save filter state to localStorage:', e);
            }

            // Update URL parameters
            this.updateURL();
        }

        loadFilterState() {
            try {
                const stored = localStorage.getItem(config.storageKey);
                if (stored) {
                    this.filterState = JSON.parse(stored);
                }
            } catch (e) {
                console.warn('Could not load filter state from localStorage:', e);
                this.filterState = {};
            }
        }

        restoreFilterState() {
            if (!this.filterState.formData || this.filterState.url !== window.location.pathname) {
                return; // No state to restore or different page
            }

            // Check if state is not too old (24 hours)
            const maxAge = 24 * 60 * 60 * 1000; // 24 hours in milliseconds
            if (Date.now() - this.filterState.timestamp > maxAge) {
                this.clearFilterState();
                return;
            }

            this.isRestoringState = true;

            try {
                const formData = this.filterState.formData;
                
                // Restore form values
                Object.keys(formData).forEach(key => {
                    const value = formData[key];
                    const field = this.form.find(`[name="${key}"]`);
                    
                    if (field.length) {
                        if (field.attr('type') === 'checkbox') {
                            field.prop('checked', value === '1' || value === true);
                        } else if (field.attr('type') === 'radio') {
                            field.filter(`[value="${value}"]`).prop('checked', true);
                        } else {
                            field.val(value);
                        }
                    }
                });

                // Trigger change events to update any dependent fields
                this.form.find('input, select').trigger('change');
                
                // Show restoration notification
                this.showStateRestoredNotification();
                
                // Trigger search with restored filters
                setTimeout(() => {
                    this.handleSubmit(1);
                }, 100);
                
            } catch (e) {
                console.warn('Error restoring filter state:', e);
            } finally {
                this.isRestoringState = false;
            }
        }

        clearFilterState() {
            try {
                localStorage.removeItem(config.storageKey);
                this.filterState = {};
            } catch (e) {
                console.warn('Could not clear filter state:', e);
            }
        }

        showStateRestoredNotification() {
            const notification = $(`
                <div class="hbl-state-restored" role="alert">
                    <svg class="hbl-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 12l2 2 4-4"></path>
                        <path d="M21 12c0 4.97-4.03 9-9 9s-9-4.03-9-9 4.03-9 9-9 9 4.03 9 9z"></path>
                    </svg>
                    <span>${hsfAdvancedFilter.i18n.filterStateRestored || 'Previous search filters restored'}</span>
                    <button type="button" class="hbl-dismiss-notification" aria-label="Dismiss">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 6L6 18M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            `);

            // Insert notification after the form
            this.form.after(notification);

            // Auto-hide after 5 seconds
            setTimeout(() => {
                notification.fadeOut(() => notification.remove());
            }, 5000);

            // Handle dismiss button
            notification.find('.hbl-dismiss-notification').on('click', () => {
                notification.fadeOut(() => notification.remove());
            });
        }

        handleSubmit(page = 1) {
            if (this.isLoading) {
                return Promise.reject(new Error('Already loading'));
            }

            this.currentPage = page;
            this.setLoading(true);
            this.updateURL();

            const formData = this.getFormData();
            formData.paged = page;
            
            return new Promise((resolve, reject) => {
                $.ajax({
                    url: hsfAdvancedFilter.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'hsf_advanced_search',
                        nonce: hsfAdvancedFilter.nonce,
                        ...formData
                    },
                    success: (response) => {
                        this.handleSuccess(response, page === 1);
                        resolve(response);
                    },
                    error: (xhr, status, error) => {
                        this.handleError(xhr, status, error);
                        reject(error);
                    },
                    complete: () => {
                        this.setLoading(false);
                    }
                });
            });
        }

        loadPage(page) {
            if (page === this.currentPage) {
                return;
            }
            this.handleSubmit(page);
        }

        loadNextPage() {
            if (this.currentPage < this.totalPages) {
                this.loadPage(this.currentPage + 1);
            }
        }

        handleAutoSubmit() {
            if (this.form.data('auto-submit') !== 'false') {
                this.setupAutoSubmit();
            }
        }

        handleReset() {
            // Clear loading state immediately
            this.setLoading(false);
            
            // Reset form fields
            this.form[0].reset();
            
            // Clear multi-select chips
            this.selectedChips.forEach((chips, fieldName) => {
                chips.forEach(chip => chip.remove());
            });
            this.selectedChips.clear();
            
            // Clear validation errors
            this.clearValidationErrors();
            
            // Clear filter state
            this.clearFilterState();
            
            // Reset URL
            this.clearURL();
            
            // Reset analytics display
            if (this.analyticsData) {
                this.updateAnalyticsDisplay(this.analyticsData);
            }
            
            // Reset preset buttons
            this.presetActive = null;
            this.form.find('.hbl-preset-button').removeClass('active');
            
            // Clear search suggestions and history
            this.hideSearchSuggestions();
            
            // Reset form to initial state
            this.form.removeClass('loading');
            this.form.find('.hbl-submit-button, .hbl-reset-button').prop('disabled', false);
            
            // Load initial results after a short delay
            setTimeout(() => {
                this.handleSubmit(1);
            }, 100);
            
            // Show notification
            this.showNotification('Filters have been reset', 'info');
            
            // Trigger custom event
            $(document).trigger('hsf:resetComplete');
        }

        clearValidationErrors() {
            this.form.find('.' + config.validationClass).removeClass(config.validationClass);
            this.form.find('.' + config.validationMessageClass).remove();
            this.form.find('.hbl-field-error').removeClass('hbl-field-error');
            this.hideFormValidationError();
            this.validationErrors = {};
        }

        showCacheStatus(cached, timestamp) {
            // Remove existing cache status
            this.resultsContainer.find('.hbl-cache-status').remove();
            
            if (cached) {
                const cacheStatus = $(`
                    <div class="hbl-cache-status hbl-cache-hit">
                        <svg class="hbl-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 12l2 2 4-4"></path>
                            <path d="M21 12c0 4.97-4.03 9-9 9s-9-4.03-9-9 4.03-9 9-9 9 4.03 9 9z"></path>
                        </svg>
                        <span>${hsfAdvancedFilter.i18n.cacheHit || 'Results loaded from cache'}</span>
                    </div>
                `);
                
                this.resultsContainer.prepend(cacheStatus);
                
                // Auto-hide after 3 seconds
                setTimeout(() => {
                    cacheStatus.fadeOut(() => cacheStatus.remove());
                }, 3000);
            }
        }

        appendResults(html) {
            // Extract just the business cards from the HTML
            const tempDiv = $('<div>').html(html);
            const newCards = tempDiv.find('.hbl-business-card');
            
            // Append to existing grid
            const existingGrid = this.resultsContainer.find('.hbl-results-grid');
            if (existingGrid.length) {
                existingGrid.append(newCards);
            }
            
            // Update results count
            const resultsHeader = this.resultsContainer.find('.hbl-results-header h3');
            if (resultsHeader.length) {
                const currentCount = this.resultsContainer.find('.hbl-business-card').length;
                resultsHeader.text(`Found ${currentCount} results`);
            }
        }

        updatePaginationControls() {
            const pagination = this.resultsContainer.find('.hbl-pagination');
            
            if (this.currentPage >= this.totalPages) {
                // Hide load more button if on last page
                pagination.find(config.loadMoreSelector).hide();
            } else {
                // Show load more button
                pagination.find(config.loadMoreSelector).show();
            }
            
            // Update page numbers
            pagination.find('.page-numbers').removeClass('current');
            pagination.find(`[data-page="${this.currentPage}"]`).addClass('current');
        }

        scrollToResults() {
            const offset = this.resultsContainer.offset();
            if (offset) {
                $('html, body').animate({
                    scrollTop: offset.top - 100
                }, 500);
            }
        }

        handleError(xhr, status, error) {
            console.error('HSF Search Error:', error);
            
            // Check if it's a validation error response
            if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.validation_errors) {
                this.showServerValidationErrors(xhr.responseJSON.data.validation_errors);
            } else {
                this.showError('An error occurred while searching. Please try again.');
            }
        }

        showServerValidationErrors(validationErrors) {
            // Clear existing validation errors
            this.clearValidationErrors();
            
            // Show server validation errors
            Object.keys(validationErrors).forEach(fieldName => {
                const field = this.form.find(`[name="${fieldName}"]`);
                if (field.length) {
                    this.showFieldValidation(field[0], false, validationErrors[fieldName]);
                }
            });
            
            // Show form-level error message
            this.showFormValidationError('Please correct the validation errors above before submitting.');
        }

        showError(message) {
            const errorHtml = `
                <div class="hbl-error" role="alert">
                    <p>${message}</p>
                </div>
            `;
            this.resultsContainer.html(errorHtml);
        }

        setLoading(loading) {
            this.isLoading = loading;
            
            if (loading) {
                this.form.addClass('loading');
                this.form.find('.hbl-submit-button, .hbl-reset-button').prop('disabled', true);
                this.showLoadingIndicator();
            } else {
                this.form.removeClass('loading');
                this.form.find('.hbl-submit-button, .hbl-reset-button').prop('disabled', false);
                this.hideLoadingIndicator();
                
                // Ensure all loading spinners are removed
                this.form.find('.hbl-loading-spinner').remove();
                this.form.find('.hbl-button-loading').removeClass('hbl-button-loading');
            }
        }

        showLoadingIndicator() {
            if (!this.resultsContainer.find('.hbl-loading-indicator').length) {
                const loadingHtml = `
                    <div class="hbl-loading-indicator">
                        <div class="hbl-loading-spinner"></div>
                        <p>${config.strings.loading}</p>
                    </div>
                `;
                this.resultsContainer.html(loadingHtml);
            }
        }

        hideLoadingIndicator() {
            this.resultsContainer.find('.hbl-loading-indicator').remove();
        }

        updateURL() {
            if (typeof history !== 'undefined' && history.pushState) {
                const formData = this.getFormData();
                const params = new URLSearchParams();
                
                Object.keys(formData).forEach(key => {
                    if (formData[key]) {
                        if (Array.isArray(formData[key])) {
                            formData[key].forEach(value => params.append(key, value));
                        } else {
                            params.set(key, formData[key]);
                        }
                    }
                });
                
                // Add page parameter
                if (this.currentPage > 1) {
                    params.set('paged', this.currentPage);
                }
                
                const newURL = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
                history.pushState({}, '', newURL);
            }
        }

        clearURL() {
            if (typeof history !== 'undefined' && history.pushState) {
                history.pushState({}, '', window.location.pathname);
            }
        }
    }

    // Initialize when document is ready
    $(document).ready(function() {
        if ($(config.formSelector).length) {
            new HSFFilter();
        }
    });

    // Make it globally available
    window.HSFFilter = HSFFilter;

})(jQuery); 