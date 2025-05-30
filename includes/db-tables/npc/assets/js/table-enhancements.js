/**
 * Table Enhancements
 * Ulepszenia dla tabel NPC
 */

(function ($) {
    'use strict';

    class TableEnhancements {
        constructor() {
            this.init();
        }

        init() {
            this.bindEvents();
            this.initFilters();
        }

        bindEvents() {
            // Filtry tabeli
            $(document).on('change', '#status-filter, #location-filter', this.applyFilters.bind(this));
            $(document).on('click', '.clear-filters-btn', this.clearFilters.bind(this));

            // Sortowanie kolumn
            $(document).on('click', '.sortable-column', this.sortColumn.bind(this));

            // Bulk actions
            $(document).on('change', '.select-all-checkbox', this.toggleAllCheckboxes.bind(this));
            $(document).on('change', '.row-checkbox', this.updateSelectAllState.bind(this));
            $(document).on('click', '.bulk-action-btn', this.performBulkAction.bind(this));
        }

        initFilters() {
            // Inicjalizacja filtrów na podstawie danych w tabeli
            this.populateLocationFilter();
        }

        populateLocationFilter() {
            const $locationFilter = $('#location-filter');
            if ($locationFilter.length === 0) return;

            const locations = new Set();

            $('.npc-list-table tbody tr').each(function () {
                const location = $(this).find('.column-location').text().trim();
                if (location) {
                    locations.add(location);
                }
            });

            // Dodaj opcje do selecta
            locations.forEach(location => {
                $locationFilter.append(`<option value="${location}">${location}</option>`);
            });
        }

        applyFilters() {
            const statusFilter = $('#status-filter').val();
            const locationFilter = $('#location-filter').val().toLowerCase();

            $('.npc-list-table tbody tr').each(function () {
                const $row = $(this);
                const status = $row.find('.column-status').text().trim().toLowerCase();
                const location = $row.find('.column-location').text().toLowerCase();

                let showRow = true;

                if (statusFilter && status !== statusFilter.toLowerCase()) {
                    showRow = false;
                }

                if (locationFilter && location.indexOf(locationFilter) === -1) {
                    showRow = false;
                }

                $row.toggle(showRow);
            });

            this.updateRowCount();
        }

        clearFilters() {
            $('#status-filter').val('');
            $('#location-filter').val('');
            $('.npc-list-table tbody tr').show();
            this.updateRowCount();
        }

        updateRowCount() {
            const totalRows = $('.npc-list-table tbody tr').length;
            const visibleRows = $('.npc-list-table tbody tr:visible').length;

            let $counter = $('.table-row-counter');
            if ($counter.length === 0) {
                $counter = $('<div class="table-row-counter"></div>');
                $('.npc-list-table').after($counter);
            }

            $counter.text(`Pokazano ${visibleRows} z ${totalRows} wpisów`);
        }

        sortColumn(event) {
            event.preventDefault();

            const $header = $(event.currentTarget);
            const column = $header.data('column');
            const currentSort = $header.data('sort') || 'asc';
            const newSort = currentSort === 'asc' ? 'desc' : 'asc';

            // Reset wszystkich nagłówków
            $('.sortable-column').removeClass('sorted-asc sorted-desc').data('sort', '');

            // Ustaw nowy stan sortowania
            $header.addClass(`sorted-${newSort}`).data('sort', newSort);

            this.performSort(column, newSort);
        }

        performSort(column, direction) {
            const $tbody = $('.npc-list-table tbody');
            const $rows = $tbody.find('tr').toArray();

            $rows.sort((a, b) => {
                const aVal = $(a).find(`.column-${column}`).text().trim();
                const bVal = $(b).find(`.column-${column}`).text().trim();

                let comparison = 0;

                // Sortowanie numeryczne dla ID
                if (column === 'id') {
                    comparison = parseInt(aVal) - parseInt(bVal);
                } else {
                    comparison = aVal.localeCompare(bVal);
                }

                return direction === 'desc' ? -comparison : comparison;
            });

            $tbody.empty().append($rows);
        }

        toggleAllCheckboxes(event) {
            const isChecked = $(event.target).is(':checked');
            $('.row-checkbox').prop('checked', isChecked);
            this.updateBulkActionButtons();
        }

        updateSelectAllState() {
            const totalCheckboxes = $('.row-checkbox').length;
            const checkedCheckboxes = $('.row-checkbox:checked').length;

            $('.select-all-checkbox').prop('checked', totalCheckboxes === checkedCheckboxes);
            this.updateBulkActionButtons();
        }

        updateBulkActionButtons() {
            const checkedCount = $('.row-checkbox:checked').length;
            $('.bulk-action-btn').prop('disabled', checkedCount === 0);

            let $counter = $('.selected-count');
            if ($counter.length === 0) {
                $counter = $('<span class="selected-count"></span>');
                $('.bulk-actions').append($counter);
            }

            $counter.text(checkedCount > 0 ? ` (${checkedCount} wybranych)` : '');
        }

        performBulkAction(event) {
            const action = $(event.target).data('action');
            const selectedIds = $('.row-checkbox:checked').map(function () {
                return $(this).val();
            }).get();

            if (selectedIds.length === 0) {
                window.notificationManager?.showNotice('Nie wybrano żadnych elementów.', 'error');
                return;
            }

            if (!confirm(`Czy na pewno chcesz wykonać akcję "${action}" dla ${selectedIds.length} elementów?`)) {
                return;
            }

            // Wykonaj akcję bulk
            this.executeBulkAction(action, selectedIds);
        }

        executeBulkAction(action, ids) {
            const data = {
                action: `npc_bulk_${action}`,
                ids: ids,
                nonce: npcAdmin.nonce
            };

            $.post(npcAdmin.ajax_url, data)
                .done((response) => {
                    if (response.success) {
                        window.notificationManager?.showNotice(`Akcja "${action}" została wykonana pomyślnie.`, 'success');
                        location.reload(); // Odśwież stronę
                    } else {
                        window.notificationManager?.showNotice(`Błąd podczas wykonywania akcji: ${response.data}`, 'error');
                    }
                })
                .fail(() => {
                    window.notificationManager?.showNotice('Błąd połączenia z serwerem.', 'error');
                });
        }
    }

    // Globalna instancja
    window.TableEnhancements = TableEnhancements;
    window.tableEnhancements = new TableEnhancements();

})(jQuery);
