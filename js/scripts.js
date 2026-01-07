// Initialize SimpleMDE editor
let easyMDE;

function initEditor() {
    if (document.getElementById('content')) {
        easyMDE = new EasyMDE({
            element: document.getElementById('content'),
            spellChecker: false,
            autosave: {
                enabled: false
            },
            placeholder: "Start writing your amazing content here...",
            autoDownloadFontAwesome: false,
            toolbar: [
                "bold", "italic", "heading", "|",
                "quote", "unordered-list", "ordered-list", "|",
                "link", "image", "code", "|",
                "preview", "side-by-side", "fullscreen", "|",
                "guide"
            ],
            status: false,
            sideBySideFullscreen: false
        });
    }
}

// Initialize Chart.js
let trafficChart;

function initCharts() {
    const ctx = document.getElementById('trafficChart');
    if (ctx) {
        trafficChart = new Chart(ctx.getContext('2d'), {
            type: 'line',
            data: {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                datasets: [{
                    label: 'Views',
                    data: [1200, 1900, 3000, 5000, 2000, 3000, 4500],
                    borderColor: '#6366f1',
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4
                }, {
                    label: 'Visitors',
                    data: [800, 1200, 1800, 2500, 1500, 2000, 2800],
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0,0,0,0.05)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }
}

// File upload preview
function initFileUpload() {
    const fileInput = document.getElementById('image');
    if (fileInput) {
        fileInput.addEventListener('change', function(e) {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const existingPreview = document.querySelector('.current-image');
                    if (existingPreview) {
                        existingPreview.remove();
                    }
                    
                    const fileUpload = document.querySelector('.file-upload');
                    const previewDiv = document.createElement('div');
                    previewDiv.className = 'current-image';
                    previewDiv.innerHTML = `
                        <p><strong>Image Preview:</strong></p>
                        <img src="${e.target.result}" style="max-width: 200px; margin-top: 10px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
                    `;
                    
                    fileUpload.parentNode.appendChild(previewDiv);
                };
                reader.readAsDataURL(this.files[0]);
            }
        });
        
        // Drag and drop functionality
        const fileUpload = document.querySelector('.file-upload');
        if (fileUpload) {
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                fileUpload.addEventListener(eventName, preventDefaults, false);
            });
            
            ['dragenter', 'dragover'].forEach(eventName => {
                fileUpload.addEventListener(eventName, highlight, false);
            });
            
            ['dragleave', 'drop'].forEach(eventName => {
                fileUpload.addEventListener(eventName, unhighlight, false);
            });
            
            fileUpload.addEventListener('drop', handleDrop, false);
        }
    }
}

function preventDefaults(e) {
    e.preventDefault();
    e.stopPropagation();
}

function highlight() {
    const fileUpload = document.querySelector('.file-upload');
    fileUpload.style.borderColor = '#6366f1';
    fileUpload.style.background = 'rgba(99, 102, 241, 0.05)';
}

function unhighlight() {
    const fileUpload = document.querySelector('.file-upload');
    fileUpload.style.borderColor = '';
    fileUpload.style.background = '';
}

function handleDrop(e) {
    const dt = e.dataTransfer;
    const files = dt.files;
    const fileInput = document.getElementById('image');
    fileInput.files = files;
    
    // Trigger change event
    const event = new Event('change');
    fileInput.dispatchEvent(event);
}

// Bulk actions
function toggleSelectAll(checkbox) {
    const checkboxes = document.querySelectorAll('.post-checkbox');
    checkboxes.forEach(cb => cb.checked = checkbox.checked);
    const tableSelectAll = document.getElementById('table-select-all');
    if (tableSelectAll) {
        tableSelectAll.checked = checkbox.checked;
    }
}

function applyBulkAction() {
    const action = document.getElementById('bulk-action').value;
    const selected = Array.from(document.querySelectorAll('.post-checkbox:checked')).map(cb => cb.value);
    
    if (!action) {
        alert('Please select a bulk action');
        return;
    }
    
    if (selected.length === 0) {
        alert('Please select at least one post');
        return;
    }
    
    if (confirm(`Are you sure you want to ${action} ${selected.length} post(s)?`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        
        selected.forEach(id => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'selected_posts[]';
            input.value = id;
            form.appendChild(input);
        });
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'bulk_action';
        actionInput.value = action;
        form.appendChild(actionInput);
        
        document.body.appendChild(form);
        form.submit();
    }
}

// View post
function viewPost(id) {
    window.open(`post.php?id=${id}`, '_blank');
}

// Preview post
function previewPost() {
    alert('Post preview feature would open in a new tab. In a real implementation, this would generate a preview.');
}

// Search functionality
function initSearch() {
    const searchInput = document.querySelector('.search-input');
    if (searchInput) {
        searchInput.addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('.posts-table tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
    }
}

// Auto-hide messages
function initAutoHideMessages() {
    setTimeout(() => {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            alert.style.opacity = '0';
            setTimeout(() => alert.style.display = 'none', 300);
        });
    }, 5000);
}

// Table select all
function initTableSelectAll() {
    const tableSelectAll = document.getElementById('table-select-all');
    if (tableSelectAll) {
        tableSelectAll.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.post-checkbox');
            checkboxes.forEach(cb => cb.checked = this.checked);
            const selectAll = document.getElementById('select-all');
            if (selectAll) {
                selectAll.checked = this.checked;
            }
        });
    }
}

// Form submission feedback
function initFormFeedback() {
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        if (!form.classList.contains('delete-form')) {
            form.addEventListener('submit', function() {
                const submitBtn = this.querySelector('button[type="submit"]');
                if (submitBtn) {
                    const originalText = submitBtn.innerHTML;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                    submitBtn.disabled = true;
                    
                    setTimeout(() => {
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                    }, 3000);
                }
            });
        }
    });
}

// Initialize tooltips
function initTooltips() {
    const tooltips = document.querySelectorAll('[title]');
    tooltips.forEach(el => {
        el.addEventListener('mouseenter', showTooltip);
        el.addEventListener('mouseleave', hideTooltip);
    });
}

function showTooltip(e) {
    const tooltip = document.createElement('div');
    tooltip.className = 'tooltip';
    tooltip.textContent = this.title;
    tooltip.style.cssText = `
        position: fixed;
        background: #1f2937;
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 6px;
        font-size: 0.9rem;
        z-index: 10000;
        pointer-events: none;
        transform: translateY(-10px);
        opacity: 0;
        transition: opacity 0.2s, transform 0.2s;
    `;
    document.body.appendChild(tooltip);
    
    const rect = this.getBoundingClientRect();
    tooltip.style.left = rect.left + rect.width / 2 - tooltip.offsetWidth / 2 + 'px';
    tooltip.style.top = rect.top - tooltip.offsetHeight - 5 + 'px';
    
    setTimeout(() => {
        tooltip.style.opacity = '1';
        tooltip.style.transform = 'translateY(0)';
    }, 10);
    
    this._tooltip = tooltip;
}

function hideTooltip() {
    if (this._tooltip) {
        this._tooltip.remove();
        this._tooltip = null;
    }
}

// Initialize everything when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initEditor();
    initCharts();
    initFileUpload();
    initSearch();
    initAutoHideMessages();
    initTableSelectAll();
    initFormFeedback();
    initTooltips();
    
    // Chart time filter buttons
    const chartBtns = document.querySelectorAll('.chart-btn');
    chartBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            chartBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            // In a real app, you would update the chart data here
        });
    });
    
    // Notification button
    const notificationBtn = document.querySelector('.notification-btn');
    if (notificationBtn) {
        notificationBtn.addEventListener('click', function() {
            alert('Notifications feature would show a dropdown with notifications.');
        });
    }
    
    // Quick Post button
    const quickPostBtn = document.querySelector('.quick-actions .btn-primary');
    if (quickPostBtn && quickPostBtn.textContent.includes('Quick Post')) {
        quickPostBtn.addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('post-form').scrollIntoView({ behavior: 'smooth' });
            document.getElementById('title').focus();
        });
    }
});
