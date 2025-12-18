/**
 * Store Page - Filters and View Toggle
 */
document.addEventListener('DOMContentLoaded', function() {
    // Toggle view mode
    document.querySelectorAll('.view-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.view-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            const grid = document.getElementById('gamesGrid');
            if (grid) {
                if (this.dataset.view === 'list') {
                    grid.classList.add('list-view');
                } else {
                    grid.classList.remove('list-view');
                }
            }
        });
    });

    // Auto-submit filters
    const filterForm = document.getElementById('filterForm');
    if (!filterForm) return;
    
    let searchTimeout;

    // Auto-submit on select change
    filterForm.querySelectorAll('select').forEach(select => {
        select.addEventListener('change', () => filterForm.submit());
    });

    // Auto-submit on checkbox change
    filterForm.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
        checkbox.addEventListener('change', () => filterForm.submit());
    });

    // Auto-submit on search input (with debounce)
    const searchInput = filterForm.querySelector('input[name="search"]');
    if (searchInput) {
        searchInput.addEventListener('input', () => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                filterForm.submit();
            }, 500);
        });
    }

    // Auto-submit on price change (with debounce)
    filterForm.querySelectorAll('input[name="min_price"], input[name="max_price"]').forEach(input => {
        input.addEventListener('change', () => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                filterForm.submit();
            }, 500);
        });
    });
});
