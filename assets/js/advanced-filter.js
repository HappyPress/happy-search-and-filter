/**
 * Happy Search & Filter - Enhanced JavaScript
 * Modern functionality with improved UX
 */

(function($) {
    'use strict';

    // Configuration
    const config = {
        autoSubmitDelay: 500,
        loadingClass: 'loading',
        resultsContainer: '#hsf-search-results',
        formSelector: '#hsf-search-form',
        resetSelector: '.hbl-reset-button'
    };

    class HSFFilter {
        constructor() {
            this.form = $(config.formSelector);
            this.resultsContainer = $(config.resultsContainer);
            this.autoSubmitTimer = null;
            this.isLoading = false;
            
            this.init();
        }

        init() {
            this.bindEvents();
            this.setupAutoSubmit();
            this.setupReset();
            this.setupAccessibility();
        }

        bindEvents() {
            // Form submission
            this.form.on('submit', (e) => {
                e.preventDefault();
                this.handleSubmit();
            });

            // Input changes for auto-submit
            this.form.find('input, select').on('change keyup', (e) => {
                if (e.type === 'keyup' && e.target.type === 'text') {
                    this.handleAutoSubmit();
                } else if (e.type === 'change') {
                    this.handleAutoSubmit();
                }
            });

            // Checkbox changes
            this.form.find('input[type="checkbox"]').on('change', () => {
                this.handleAutoSubmit();
            });

            // Prevent form submission on Enter key for text inputs
            this.form.find('input[type="text"]').on('keypress', (e) => {
                if (e.which === 13) {
                    e.preventDefault();
                    this.handleSubmit();
                }
            });
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
                this.handleSubmit();
            }, config.autoSubmitDelay);
        }

        setupReset() {
            $(config.resetSelector).on('click', (e) => {
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
        }

        handleSubmit() {
            if (this.isLoading) {
                return;
            }

            this.setLoading(true);
            this.updateURL();

            const formData = this.getFormData();
            
            $.ajax({
                url: hsfAdvancedFilter.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'hsf_advanced_search',
                    nonce: hsfAdvancedFilter.nonce,
                    ...formData
                },
                success: (response) => {
                    this.handleSuccess(response);
                },
                error: (xhr, status, error) => {
                    this.handleError(error);
                },
                complete: () => {
                    this.setLoading(false);
                }
            });
        }

        handleAutoSubmit() {
            if (this.form.data('auto-submit') !== 'false') {
                this.setupAutoSubmit();
            }
        }

        handleReset() {
            // Reset form
            this.form[0].reset();
            
            // Clear results
            this.resultsContainer.empty();
            
            // Clear URL parameters
            this.clearURL();
            
            // Trigger change events to update any dependent fields
            this.form.find('input, select').trigger('change');
            
            // Focus on search input
            this.form.find('input[type="text"]').first().focus();
        }

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
            
            return formData;
        }

        handleSuccess(response) {
            if (response.success) {
                this.resultsContainer.html(response.data.html);
                this.resultsContainer.attr('aria-label', `Found ${response.data.count || 0} results`);
                
                // Trigger custom event
                $(document).trigger('hsf:searchComplete', [response.data]);
            } else {
                this.showError(response.data || 'Search failed. Please try again.');
            }
        }

        handleError(error) {
            console.error('HSF Search Error:', error);
            this.showError('An error occurred while searching. Please try again.');
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
                this.form.addClass(config.loadingClass);
                this.form.find('button[type="submit"]').prop('disabled', true);
            } else {
                this.form.removeClass(config.loadingClass);
                this.form.find('button[type="submit"]').prop('disabled', false);
            }
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