@extends( 'frontend/layouts/contentLayoutMaster' )

@section( 'title', $title )

@section( 'vendor-style' )
    <!-- vendor css files -->
    <link rel="stylesheet" href="{{ asset( mix( 'vendors/css/forms/wizard/bs-stepper.min.css' ) ) }}">
    <link rel="stylesheet" href="{{ asset( mix( 'vendors/css/forms/select/select2.min.css' ) ) }}">
    <link rel="stylesheet" href="{{ asset( mix( 'vendors/css/extensions/toastr.min.css' ) ) }}">
    <link rel="stylesheet" href="{{ asset( mix( 'vendors/css/animate/animate.min.css' ) ) }}">
    <link rel="stylesheet" href="{{ asset( mix( 'vendors/css/extensions/sweetalert2.min.css' ) ) }}">
    <link rel="stylesheet" href="{{ asset( mix( 'vendors/css/extensions/dragula.min.css' ) ) }}">
@endsection
@section( 'page-style' )
    {{-- Page Css files --}}
    <link rel="stylesheet" type="text/css"
        href="{{ asset( 'css/' . strtolower( env( 'SETTINGS_KEY', 'KeyInstitute' ) ) . '/base/pages/page-blog.css' ) }}" />
    <link rel="stylesheet"
        href="{{ asset( mix( 'css/' . strtolower( env( 'SETTINGS_KEY', 'KeyInstitute' ) ) . '/base/plugins/forms/form-validation.css' ) ) }}">
    <link rel="stylesheet"
        href="{{ asset( mix( 'css/' . strtolower( env( 'SETTINGS_KEY', 'KeyInstitute' ) ) . '/base/plugins/forms/form-wizard.css' ) ) }}">
    <link rel="stylesheet"
        href="{{ asset( mix( 'css/' . strtolower( env( 'SETTINGS_KEY', 'KeyInstitute' ) ) . '/base/plugins/extensions/ext-component-toastr.css' ) ) }}">
    <link rel="stylesheet"
        href="{{ asset( mix( 'css/' . strtolower( env( 'SETTINGS_KEY', 'KeyInstitute' ) ) . '/base/plugins/extensions/ext-component-sweet-alerts.css' ) ) }}">
    <link rel="stylesheet"
        href="{{ asset( mix( 'css/' . strtolower( env( 'SETTINGS_KEY', 'KeyInstitute' ) ) . '/base/plugins/extensions/ext-component-drag-drop.css' ) ) }}">
    <style>
        /* Mobile-friendly sorting question improvements */
        .sorting-list .sort-item {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            padding: 0.75rem 1rem;
            min-height: 60px;
        }

        .sorting-list .sort-item:active {
            transform: scale(0.98);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        }


        .sort-handle {
            min-width: 44px;
            min-height: 44px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .sort-handle button {
            min-width: 36px;
            min-height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            touch-action: manipulation;
        }

        .sort-handle button:active {
            transform: scale(0.95);
        }

        .sort-handle-desktop {
            cursor: grab;
            min-width: 24px;
        }

        .sort-handle-desktop:active {
            cursor: grabbing;
        }

        .sort-order-badge {
            min-width: 32px;
            text-align: center;
        }

        @media (max-width: 767.98px) {
            .sorting-list .sort-item {
                padding: 1rem;
                margin-bottom: 0.5rem;
            }

            .sorting-list .sort-item h5 {
                font-size: 1rem;
                line-height: 1.4;
            }

            .sort-handle {
                margin-right: 0.75rem;
            }
        }

        /* Improve drag feedback */
        .gu-mirror {
            opacity: 0.9;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2) !important;
        }

        .gu-transit {
            opacity: 0.3;
        }

        /* Prevent unwanted drag behavior on touchscreen devices */
        @media (hover: none) and (pointer: coarse) {
            .sorting-list .sort-item {
                touch-action: pan-y;
                -webkit-user-select: none;
                user-select: none;
            }

            .matrix-source .list-group-item {
                touch-action: pan-y;
                -webkit-user-select: none;
                user-select: none;
            }

            /* Ensure buttons are easily tappable on touchscreen */
            .sort-handle button,
            .matrix-remove-slot {
                touch-action: manipulation;
            }
        }
    </style>
@endsection

@section( 'content-sidebar' )
    {{-- @include('frontend.content.lms.sidebar') --}}
@endsection

@section( 'content' )
    @if (
            !empty( $lastAttempt ) &&
            in_array( $lastAttempt->status, [ 'SUBMITTED', 'SATISFACTORY' ] ) &&
        in_array( $lastAttempt->system_result, [ 'COMPLETED', 'EVALUATED' ] ) )
        <div class="row">
            <div class="col-8 align-content-center">
                @if ( $post->id == config( 'ptr.quiz_id' ) )
                    <p>PTR assessment completed successfully!
                        <a href="{{ route( 'frontend.dashboard' ) }}">Go to Dashboard</a>.
                    </p>
                @else
                    <p>Quiz is already submitted,
                        <a href="{{ route( 'frontend.lms.topics.show', $post->topic_id ) }}">Go back to main topic</a>.
                    </p>
                @endif
            </div>
        </div>
    @else
        <div class="d-flex justify-content-end align-items-end mb-2 ">
            <div class="btn-group">
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#digestModal">
                    Read Lesson/Topics
                </button>
                <button id="quizDetailsTrigger" class="btn btn-sm btn-dark" type="button" data-bs-toggle="collapse"
                    data-bs-target="#collapseQuizDetails" aria-expanded="true" aria-controls="collapseQuizDetails">Hide Quiz
                    Instructions
                </button>
            </div>
        </div>
        <div class="mt-1 mb-2">
            <div class="show collapse collapse-horizontal" id="collapseQuizDetails">
                <div class="d-flex border p-1">
                    <div class="flex-grow-1">
                        {!! $post->lb_content !!}
                    </div>
                </div>
            </div>
        </div>
        @include( 'frontend.content.lms.quiz-content' )
        {{-- @include('frontend.content.lms.quiz-content-html') --}}
        @include( 'frontend.content.lms.digest' )
    @endif
@endsection

@section( 'vendor-script' )
    <!-- vendor files -->
    <script src="{{ asset( mix( 'vendors/js/extensions/moment.min.js' ) ) }}"></script>
    <script src="{{ asset( mix( 'vendors/js/extensions/moment-timezone.min.js' ) ) }}"></script>
    <script src="{{ asset( mix( 'vendors/js/extensions/moment-timezone-with-data.min.js' ) ) }}"></script>
    <script src="{{ asset( mix( 'vendors/js/forms/wizard/bs-stepper.min.js' ) ) }}"></script>
    <script src="{{ asset( mix( 'vendors/js/forms/select/select2.full.min.js' ) ) }}"></script>
    {{--
    <script src="{{ asset(mix('vendors/js/forms/validation/jquery.validate.min.js')) }}"></script> --}}
    {{-- TinyMCE Self-hosted (Free) --}}
    <script src="https://cdn.jsdelivr.net/npm/tinymce@6.7.0/tinymce.min.js"></script>
    <script src="{{ asset( mix( 'vendors/js/extensions/toastr.min.js' ) ) }}"></script>
    <script src="{{ asset( mix( 'vendors/js/extensions/sweetalert2.all.min.js' ) ) }}"></script>
    <script src="{{ asset( mix( 'vendors/js/extensions/dragula.min.js' ) ) }}"></script>
@endsection
@section( 'page-script' )
    <!-- Page js files -->
    <script src="{{ asset( mix( 'js/scripts/_my/lms-quiz.js' ) ) . '?' . time() }}"></script>

    <script>
        $(function () {

            // Initialize TinyMCE
            tinymce.init({
                selector: '.content-tinymce',
                plugins: 'lists wordcount',
                toolbar: 'bold italic underline | bullist numlist | removeformat',
                height: 500,
                menubar: false,
                branding: false,
                browser_spellcheck: true, // Enable browser's native spell checking (works with Grammarly)
            });

            // Set readonly for hidden editors after initialization
            setTimeout(function () {
                $(".content-tinymce.hidden").each(function () {
                    let idTinyMCE = $(this).prop('id');
                    let editor = tinymce.get(idTinyMCE);
                    if (editor) {
                        editor.setMode('readonly');
                    }
                });
            }, 1000);
            // dragula([document.getElementById('sorting-group3')]);

            // Detect if device is a touchscreen device
            const isTouchDevice = 'ontouchstart' in window || navigator.maxTouchPoints > 0 || navigator.msMaxTouchPoints > 0;

            if ($('.sorting-list').length) {
                $('.sorting-list').each(function (index) {
                    let id = $(this).attr('id');

                    // Only initialize dragula on non-touchscreen devices (desktop)
                    if (!isTouchDevice) {
                        let sorting_drake = dragula([$(this)[0]]);

                        // Update order badges and arrow states after drag and drop
                        sorting_drake.on('drop', function(el, target, source, sibling) {
                            const $list = $(target);
                            updateSortOrderBadges($list);
                            updateSortArrowStates($list);
                        });
                    }
                });

                // Handle up/down button clicks for mobile-friendly sorting
                $(document).on('click', '.sort-move-up', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    const $item = $(this).closest('.sort-item');
                    const $prev = $item.prev('.sort-item');
                    if ($prev.length) {
                        $item.insertBefore($prev);
                        const $list = $item.closest('.sorting-list');
                        updateSortOrderBadges($list);
                        updateSortArrowStates($list);
                    }
                });

                $(document).on('click', '.sort-move-down', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    const $item = $(this).closest('.sort-item');
                    const $next = $item.next('.sort-item');
                    if ($next.length) {
                        $item.insertAfter($next);
                        const $list = $item.closest('.sorting-list');
                        updateSortOrderBadges($list);
                        updateSortArrowStates($list);
                    }
                });

                // Handle keyboard navigation for accessibility
                $(document).on('keydown', '.sort-move-up, .sort-move-down', function(e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        $(this).click();
                    }
                });

                // Function to update sort item indices
                function updateSortOrderBadges($list) {
                    $list.find('.sort-item').each(function(index) {
                        $(this).attr('data-index', index);
                    });
                }

                // Function to update disabled state of up/down arrows
                function updateSortArrowStates($list) {
                    const $items = $list.find('.sort-item');
                    $items.each(function() {
                        const $item = $(this);
                        const $upBtn = $item.find('.sort-move-up');
                        const $downBtn = $item.find('.sort-move-down');

                        // Disable and grey out up arrow if this is the first item
                        if ($item.prev('.sort-item').length === 0) {
                            $upBtn.prop('disabled', true).addClass('disabled').css({
                                'opacity': '0.5',
                                'cursor': 'not-allowed'
                            });
                        } else {
                            $upBtn.prop('disabled', false).removeClass('disabled').css({
                                'opacity': '1',
                                'cursor': 'pointer'
                            });
                        }

                        // Disable and grey out down arrow if this is the last item
                        if ($item.next('.sort-item').length === 0) {
                            $downBtn.prop('disabled', true).addClass('disabled').css({
                                'opacity': '0.5',
                                'cursor': 'not-allowed'
                            });
                        } else {
                            $downBtn.prop('disabled', false).removeClass('disabled').css({
                                'opacity': '1',
                                'cursor': 'pointer'
                            });
                        }
                    });
                }

                // Initialize order badges and arrow states on page load
                $('.sorting-list').each(function() {
                    const $list = $(this);
                    updateSortOrderBadges($list);
                    updateSortArrowStates($list);
                });
            }
            if ($('.matrix-source').length) {
                $('.matrix-source').each(function (index) {
                    const $source = $(this);
                    const destinationPrefix = $source.data('destination');

                    // Get all destination slots for this matrix
                    const destinationSlots = [];
                    $('[id^="' + destinationPrefix + '_"]').each(function() {
                        destinationSlots.push(this);
                    });

                    // Initialize pre-loaded answers: ensure X button is present and only one answer per slot
                    destinationSlots.forEach(function(slot) {
                        const $slot = $(slot);

                        // First, check for any list items that have answers but aren't properly formatted
                        // (e.g., items without matrix-sort-item class)
                        $slot.find('li.list-group-item').not('.matrix-empty-slot').not('.matrix-sort-item').each(function() {
                            const $item = $(this);
                            const value = $item.find('small').text().trim() || $item.text().trim();
                            // Skip if it's just placeholder text
                            if (value && !value.toLowerCase().includes('drop') && !value.toLowerCase().includes('tap')) {
                                // Convert to properly formatted matrix-sort-item
                                $item.addClass('matrix-sort-item').attr('data-index', '0').html(
                                    '<div class="d-flex align-items-center justify-content-between">' +
                                    '<small class="mb-0">' + value + '</small>' +
                                    '<button type="button" class="btn btn-sm btn-outline-danger p-0 matrix-remove-slot" aria-label="Remove" tabindex="0" style="width: 18px; height: 18px; line-height: 1;">' +
                                    '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-x"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>' +
                                    '</button>' +
                                    '</div>'
                                );
                            }
                        });

                        const $sortItems = $slot.find('.matrix-sort-item');

                        // Ensure only one answer per slot (remove duplicates if any)
                        if ($sortItems.length > 1) {
                            // Keep only the first item, return others to source
                            $sortItems.slice(1).each(function() {
                                const $item = $(this);
                                const value = $item.find('small').text().trim() || $item.text().trim();
                                if (value && $source.length) {
                                    const newItem = $('<li class="list-group-item draggable border rounded p-1 mb-0">' +
                                        '<small class="mb-0">' + value + '</small>' +
                                        '</li>');
                                    $source.append(newItem);
                                }
                                $item.remove();
                            });
                        }

                        // Ensure X button is present on all matrix-sort-items
                        $slot.find('.matrix-sort-item').each(function() {
                            const $item = $(this);
                            // Check if X button already exists
                            if ($item.find('.matrix-remove-slot').length === 0) {
                                const value = $item.find('small').text().trim() || $item.text().trim();
                                if (value) {
                                    // Add X button if missing
                                    $item.html(
                                        '<div class="d-flex align-items-center justify-content-between">' +
                                        '<small class="mb-0">' + value + '</small>' +
                                        '<button type="button" class="btn btn-sm btn-outline-danger p-0 matrix-remove-slot" aria-label="Remove" tabindex="0" style="width: 18px; height: 18px; line-height: 1;">' +
                                        '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-x"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>' +
                                        '</button>' +
                                        '</div>'
                                    );
                                }
                            }
                        });

                        // Remove empty slot placeholder if slot has an answer
                        if ($slot.find('.matrix-sort-item').length > 0) {
                            $slot.find('.matrix-empty-slot').remove();
                        }
                    });

                    // Only create dragula on non-touchscreen devices (desktop)
                    if (!isTouchDevice) {
                        // Create dragula with source and all destination slots
                        const containers = [$(this)[0], ...destinationSlots];
                        let matrix_drake = dragula(containers, {
                            revertOnSpill: true,
                            copy: false,
                            accepts: function(el, target, source, sibling) {
                                // If target is a destination slot
                                if ($(target).hasClass('matrix-destination-slot')) {
                                    const $slot = $(target);
                                    const existingItems = $slot.find('.matrix-sort-item');

                                    // If dragging from another slot (swap scenario), allow drop even if target has an answer
                                    if ($(source).hasClass('matrix-destination-slot')) {
                                        // Allow swap - can drop on slot with answer
                                        return true;
                                    }
                                    // Dragging from source - only allow if target is empty
                                    return existingItems.length === 0;
                                }
                                return true; // Allow drops in source
                            }
                        });

                        // Handle drop into any destination slot or back to source
                        matrix_drake.on('drop', function(el, target, source, sibling) {
                            // Check if dropped into a destination slot
                            if ($(target).hasClass('matrix-destination-slot')) {
                                const $targetSlot = $(target);
                                const $sourceSlot = $(source);

                                // Check if this is a swap scenario (dragging from one slot to another that has an answer)
                                const isSwap = $sourceSlot.hasClass('matrix-destination-slot') &&
                                             $targetSlot.find('.matrix-sort-item').not(el).length > 0;

                                if (isSwap) {
                                    // Swap scenario: move the existing answer from target to source slot
                                    const $existingItem = $targetSlot.find('.matrix-sort-item').not(el).first();
                                    if ($existingItem.length > 0) {
                                        // Move existing item to the source slot
                                        $existingItem.detach();
                                        $sourceSlot.append($existingItem);

                                        // Ensure source slot doesn't have empty placeholder (it now has the swapped item)
                                        $sourceSlot.find('.matrix-empty-slot').remove();
                                    }
                                } else if ($sourceSlot.hasClass('matrix-destination-slot')) {
                                    // Dragging from one slot to another empty slot - add placeholder to source if it's now empty
                                    setTimeout(function() {
                                        if ($sourceSlot.find('.matrix-sort-item').length === 0) {
                                            const slotIndex = $sourceSlot.data('slot-index');
                                            $sourceSlot.find('.matrix-empty-slot').remove();
                                            $sourceSlot.append(
                                                '<li class="list-group-item matrix-empty-slot border-dashed p-2 text-center" data-slot-index="' + slotIndex + '">' +
                                                '<small class="text-muted">Drop answer here</small>' +
                                                '</li>'
                                            );
                                        }
                                    }, 0);
                                } else {
                                    // Normal drop: ensure only one answer per slot
                                    // Remove any existing answers (excluding the item we just dropped)
                                    const existingItems = $targetSlot.find('.matrix-sort-item').not(el);
                                    if (existingItems.length > 0) {
                                        // Remove all existing items except the one we just dropped
                                        existingItems.each(function() {
                                            const $existingItem = $(this);
                                            const value = $existingItem.find('small').text().trim() || $existingItem.text().trim();
                                            // Return to source if it exists
                                            const slotId = $targetSlot.attr('id');
                                            const destinationPrefix = slotId.substring(0, slotId.lastIndexOf('_'));
                                            const $source = $('.matrix-source[data-destination="' + destinationPrefix + '"]');
                                            if ($source.length && value) {
                                                const newItem = $('<li class="list-group-item draggable border rounded p-1 mb-0">' +
                                                    '<small class="mb-0">' + value + '</small>' +
                                                    '</li>');
                                                $source.append(newItem);
                                            }
                                            $existingItem.remove();
                                        });
                                    }
                                }

                                const value = $(el).find('small').text().trim() || $(el).text().trim();

                                // Remove empty slot placeholder if exists
                                $targetSlot.find('.matrix-empty-slot').remove();

                                // If item already has structure (moving from one slot to another), just ensure it's in the right place
                                if ($(el).hasClass('matrix-sort-item')) {
                                    // Item is already formatted, just ensure it's properly placed
                                    return;
                                }

                                // Transform dropped item from source
                                $(el).addClass('matrix-sort-item').html(
                                    '<div class="d-flex align-items-center justify-content-between">' +
                                    '<small class="mb-0">' + value + '</small>' +
                                    '<button type="button" class="btn btn-sm btn-outline-danger p-0 matrix-remove-slot" aria-label="Remove" tabindex="0" style="width: 18px; height: 18px; line-height: 1;">' +
                                    '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-x"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>' +
                                    '</button>' +
                                    '</div>'
                                );
                            }
                            // Check if dropped back into source (available options)
                            else if ($(target).hasClass('matrix-source')) {
                                const value = $(el).find('small').text().trim() || $(el).text().trim();

                                // Transform back to original form (remove X button and matrix-sort-item class)
                                $(el).removeClass('matrix-sort-item').html(
                                    '<small class="mb-0">' + value + '</small>'
                                );

                                // Clear mobile selection
                                selectedMatrixOption = null;
                                $('.matrix-source .list-group-item').removeClass('bg-primary text-white');
                            }
                        });

                        // Handle remove from slot (when item is dragged out)
                        matrix_drake.on('remove', function(el, container, source) {
                            // If removing from a slot, add empty placeholder back
                            if ($(container).hasClass('matrix-destination-slot')) {
                                const slotIndex = $(container).data('slot-index');
                                // Use setTimeout to check after the item has been removed
                                setTimeout(function() {
                                    const $slot = $(container);
                                    // Only add placeholder if slot is now empty
                                    if ($slot.children().length === 0 || $slot.find('.matrix-sort-item').length === 0) {
                                        $slot.find('.matrix-empty-slot').remove();
                                        $slot.append(
                                            '<li class="list-group-item matrix-empty-slot border-dashed p-2 text-center" data-slot-index="' + slotIndex + '">' +
                                            '<small class="text-muted">Drop answer here</small>' +
                                            '</li>'
                                        );
                                    }
                                }, 0);
                            }
                        });
                    }
                });

                // Touchscreen: Tap to select option, then tap slot to place
                let selectedMatrixOption = null;

                // Tap on available option to select it (touchscreen devices)
                $(document).on('click', '.matrix-source .list-group-item', function(e) {
                    if (isTouchDevice) { // Touchscreen devices only
                        e.preventDefault();
                        e.stopPropagation();

                        // Remove previous selection
                        $('.matrix-source .list-group-item').removeClass('bg-primary text-white');

                        // Select this option
                        $(this).addClass('bg-primary text-white');
                        selectedMatrixOption = $(this);
                    }
                });

                // Tap on empty slot to place selected option (touchscreen devices)
                $(document).on('click', '.matrix-empty-slot', function(e) {
                    if (isTouchDevice && selectedMatrixOption) { // Touchscreen devices only
                        e.preventDefault();
                        e.stopPropagation();

                        const $slot = $(this).closest('.matrix-destination-slot');

                        // Ensure slot doesn't already have an answer
                        const hasAnswer = $slot.find('.matrix-sort-item').length > 0;
                        if (hasAnswer) {
                            // Slot already has an answer, don't allow adding another
                            toastr['warning']('This slot already has an answer. Please remove it first.', 'Error', {
                                closeButton: true,
                                tapToDismiss: true,
                            });
                            return;
                        }

                        const value = selectedMatrixOption.find('small').text().trim() || selectedMatrixOption.text().trim();

                        // Remove empty placeholder
                        $(this).remove();

                        // Add answer to slot
                        const $newItem = $('<li class="list-group-item matrix-sort-item p-1 border-0" data-index="0">' +
                            '<div class="d-flex align-items-center justify-content-between">' +
                            '<small class="mb-0">' + value + '</small>' +
                            '<button type="button" class="btn btn-sm btn-outline-danger p-0 matrix-remove-slot" aria-label="Remove" tabindex="0" style="width: 18px; height: 18px; line-height: 1;">' +
                            '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-x"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>' +
                            '</button>' +
                            '</div>' +
                            '</li>');
                        $slot.append($newItem);

                        // Remove from source
                        selectedMatrixOption.remove();
                        selectedMatrixOption = null;
                    }
                });

                // Tap on slot with answer to remove it (touchscreen alternative to X button)
                $(document).on('click', '.matrix-sort-item', function(e) {
                    if (isTouchDevice && !$(e.target).closest('.matrix-remove-slot').length) { // Touchscreen devices only, not if clicking X
                        e.preventDefault();
                        e.stopPropagation();
                        $(this).find('.matrix-remove-slot').click();
                    }
                });

                // Handle remove button clicks for matrix slot items
                $(document).on('click', '.matrix-remove-slot', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    const $item = $(this).closest('.matrix-sort-item');
                    const $slot = $item.closest('.matrix-destination-slot');
                    const slotIndex = $slot.data('slot-index');
                    const value = $item.find('small').text().trim() || $item.text().trim();

                    // Find the source list for this matrix
                    const slotId = $slot.attr('id');
                    const destinationPrefix = slotId.substring(0, slotId.lastIndexOf('_'));
                    const $source = $('.matrix-source[data-destination="' + destinationPrefix + '"]');

                    // Create new item in source list and return it there
                    if ($source.length && value) {
                        const newItem = $('<li class="list-group-item draggable border rounded p-1 mb-0">' +
                            '<small class="mb-0">' + value + '</small>' +
                            '</li>');
                        $source.append(newItem);

                        // Clear mobile selection if this was the selected item
                        if (selectedMatrixOption && selectedMatrixOption.closest('.matrix-source')[0] === $source[0]) {
                            selectedMatrixOption = null;
                            $('.matrix-source .list-group-item').removeClass('bg-primary text-white');
                        }
                    }

                    // Remove the item from slot
                    $item.remove();

                    // Add empty placeholder back
                    if ($slot.children().length === 0) {
                        $slot.append(
                            '<li class="list-group-item matrix-empty-slot border-dashed p-2 text-center" data-slot-index="' + slotIndex + '">' +
                            '<small class="text-muted">Drop answer here</small>' +
                            '</li>'
                        );
                    }
                });

                // Handle mobile tap to add item from source to destination
                $(document).on('click', '.matrix-add-btn', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    const $item = $(this).closest('.matrix-item-source');
                    const $source = $item.closest('.matrix-source');
                    const destinationId = $source.data('destination');
                    const $destination = $('#' + destinationId);

                    if ($destination.length) {
                        // Find first empty slot or append to end
                        let $targetSlot = $destination.find('.matrix-empty-slot').first();
                        const value = $item.data('value');
                        const itemCount = $destination.find('.matrix-sort-item').length;
                        const isLast = $targetSlot.length === 0;

                        if ($targetSlot.length === 0) {
                            // No empty slots, create a new one
                            const newItem = $('<li class="list-group-item matrix-sort-item border-bottom" data-index="' + itemCount + '">' +
                                '<div class="d-flex align-items-center">' +
                                '<div class="sort-handle me-2 d-flex flex-column" role="button" tabindex="0" aria-label="Move item">' +
                                '<button type="button" class="btn btn-sm btn-outline-primary p-1 mb-1 matrix-move-up" aria-label="Move up" tabindex="0" ' + (itemCount === 0 ? 'disabled' : '') + '>' +
                                '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-arrow-up"><line x1="12" y1="19" x2="12" y2="5"></line><polyline points="5 12 12 5 19 12"></polyline></svg>' +
                                '</button>' +
                                '<button type="button" class="btn btn-sm btn-outline-primary p-1 matrix-move-down" aria-label="Move down" tabindex="0" disabled>' +
                                '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-arrow-down"><line x1="12" y1="5" x2="12" y2="19"></line><polyline points="19 12 12 19 5 12"></polyline></svg>' +
                                '</button>' +
                                '</div>' +
                                '<div class="flex-grow-1">' +
                                '<h6 class="mb-0 py-2">' + value + '</h6>' +
                                '</div>' +
                                '<div class="sort-handle-drag ms-2 d-flex align-items-center text-muted" aria-label="Drag handle">' +
                                '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-move"><polyline points="5 9 2 12 5 15"></polyline><polyline points="9 5 12 2 15 5"></polyline><polyline points="15 19 12 22 9 19"></polyline><polyline points="19 9 22 12 19 15"></polyline><line x1="2" y1="12" x2="22" y2="12"></line><line x1="12" y1="2" x2="12" y2="22"></line></svg>' +
                                '</div>' +
                                '</div>' +
                                '</li>');
                            $destination.append(newItem);
                        } else {
                            // Replace empty slot
                            $targetSlot.replaceWith(
                                '<li class="list-group-item matrix-sort-item border-bottom" data-index="' + $targetSlot.data('slot-index') + '">' +
                                '<div class="d-flex align-items-center">' +
                                '<div class="sort-handle me-2 d-flex flex-column" role="button" tabindex="0" aria-label="Move item">' +
                                '<button type="button" class="btn btn-sm btn-outline-primary p-1 mb-1 matrix-move-up" aria-label="Move up" tabindex="0">' +
                                '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-arrow-up"><line x1="12" y1="19" x2="12" y2="5"></line><polyline points="5 12 12 5 19 12"></polyline></svg>' +
                                '</button>' +
                                '<button type="button" class="btn btn-sm btn-outline-primary p-1 matrix-move-down" aria-label="Move down" tabindex="0">' +
                                '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-arrow-down"><line x1="12" y1="5" x2="12" y2="19"></line><polyline points="19 12 12 19 5 12"></polyline></svg>' +
                                '</button>' +
                                '</div>' +
                                '<div class="flex-grow-1">' +
                                '<h6 class="mb-0 py-2">' + value + '</h6>' +
                                '</div>' +
                                '<div class="sort-handle-drag ms-2 d-flex align-items-center text-muted" aria-label="Drag handle">' +
                                '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-move"><polyline points="5 9 2 12 5 15"></polyline><polyline points="9 5 12 2 15 5"></polyline><polyline points="15 19 12 22 9 19"></polyline><polyline points="19 9 22 12 19 15"></polyline><line x1="2" y1="12" x2="22" y2="12"></line><line x1="12" y1="2" x2="12" y2="22"></line></svg>' +
                                '</div>' +
                                '</div>' +
                                '</li>'
                            );
                        }
                        // Remove from source
                        $item.remove();
                        updateMatrixSlots($destination);
                        updateMatrixIndices($destination);
                        updateMatrixArrowStates($destination);
                    }
                });

                // Handle mobile tap to remove item from destination back to source
                $(document).on('click', '.matrix-remove-btn', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    const $item = $(this).closest('.matrix-item-destination, .matrix-sort-item');
                    const $destination = $item.closest('.matrix-destination');
                    const destinationId = $destination.attr('id');
                    // Find the corresponding source by matching the data-destination attribute
                    const $source = $('.matrix-source[data-destination="' + destinationId + '"]');

                    if ($source.length) {
                        const value = $item.find('h5').text();
                        const originalIndex = $item.data('original-index') || 0;

                        // Create new source item
                        const newItem = $('<li class="list-group-item draggable matrix-item-source me-2 mb-2 border rounded" data-value="' + value + '" data-original-index="' + originalIndex + '">' +
                            '<div class="d-flex align-items-center">' +
                            '<div class="flex-grow-1">' +
                            '<h5 class="mb-0 py-2">' + value + '</h5>' +
                            '</div>' +
                            '<button type="button" class="btn btn-sm btn-primary ms-2 matrix-add-btn d-md-none" aria-label="Add to answers" tabindex="0">' +
                            '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-plus"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>' +
                            '</button>' +
                            '<div class="sort-handle-desktop ms-2 d-none d-md-flex align-items-center text-muted" aria-label="Drag handle">' +
                            '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-move"><polyline points="5 9 2 12 5 15"></polyline><polyline points="9 5 12 2 15 5"></polyline><polyline points="15 19 12 22 9 19"></polyline><polyline points="19 9 22 12 19 15"></polyline><line x1="2" y1="12" x2="22" y2="12"></line><line x1="12" y1="2" x2="12" y2="22"></line></svg>' +
                            '</div>' +
                            '</div>' +
                            '</li>');
                        $source.append(newItem);

                        // Replace destination item with empty slot
                        const slotIndex = $item.data('index') || $destination.find('.matrix-item-destination, .matrix-sort-item').index($item);
                        $item.replaceWith(
                            '<li class="list-group-item matrix-empty-slot border-bottom border-dashed" data-slot-index="' + slotIndex + '">' +
                            '<div class="d-flex align-items-center justify-content-center py-4">' +
                            '<span class="text-muted">Drop answer here</span>' +
                            '</div>' +
                            '</li>'
                        );
                        updateMatrixSlots($destination);
                        updateMatrixIndices($destination);
                        updateMatrixArrowStates($destination);
                    }
                });

                // Handle keyboard navigation for accessibility
                $(document).on('keydown', '.matrix-add-btn, .matrix-remove-btn', function(e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        $(this).click();
                    }
                });

                // Function to update empty slots
                function updateMatrixSlots($destination) {
                    // This function can be used to update slot indices if needed
                    $destination.find('.matrix-item-destination, .matrix-sort-item').each(function(index) {
                        $(this).attr('data-index', index);
                    });
                }
            }
            // $("#collapseQuizDetails").
            $("#quizDetailsTrigger").click(function () {
                if ($(this).attr('aria-expanded') === "true") {
                    $(this).text("Hide Quiz Instructions").addClass('btn-dark').removeClass('btn-info');
                } else {
                    $(this).text("View Quiz Instructions").removeClass('btn-dark').addClass('btn-info');
                }
            });

            let validation = {
                rules: [],
                messages: []
            };

        });
    </script>
@endsection
