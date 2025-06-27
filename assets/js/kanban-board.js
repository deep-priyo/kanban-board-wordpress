// WPTaskBoard Frontend JavaScript
(function($) {
    'use strict';

    // Initialize when DOM is ready
    $(document).ready(function() {
        initKanbanBoard();
    });

    function initKanbanBoard() {
        setupDragAndDrop();
        setupAddTaskModal();
    }

    function setupDragAndDrop() {
        // Make tasks draggable
        $('.kanban-task').attr('draggable', true);

        // Drag start
        $(document).on('dragstart', '.kanban-task', function(e) {
            const taskId = $(this).data('task-id');
            e.originalEvent.dataTransfer.setData('text/plain', taskId);
            $(this).addClass('dragging');
        });

        // Drag end
        $(document).on('dragend', '.kanban-task', function(e) {
            $(this).removeClass('dragging');
        });

        // Allow drop on columns
        $(document).on('dragover', '.kanban-column-content', function(e) {
            e.preventDefault();
            $(this).addClass('drag-over');
        });

        // Remove drag over styling
        $(document).on('dragleave', '.kanban-column-content', function(e) {
            $(this).removeClass('drag-over');
        });

        // Handle drop
        $(document).on('drop', '.kanban-column-content', function(e) {
            e.preventDefault();
            $(this).removeClass('drag-over');

            const taskId = e.originalEvent.dataTransfer.getData('text/plain');
            const newStatus = $(this).data('status');
            const taskElement = $('[data-task-id="' + taskId + '"]');

            // Move the task element
            $(this).append(taskElement);

            // Update task status via AJAX
            updateTaskStatus(taskId, newStatus);

            // Update task counts
            updateTaskCounts();
        });
    }

    function updateTaskStatus(taskId, status) {
        $.ajax({
            url: wptaskboard_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'wptaskboard_update_task',
                task_id: taskId,
                status: status,
                nonce: wptaskboard_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    console.log('Task status updated successfully');
                } else {
                    console.error('Failed to update task status:', response.data);
                    // Optionally reload the page or show an error message
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', error);
                // Optionally reload the page or show an error message
            }
        });
    }

    function updateTaskCounts() {
        $('.kanban-column').each(function() {
            const taskCount = $(this).find('.kanban-task').length;
            $(this).find('.task-count').text(taskCount);
        });
    }

    function setupAddTaskModal() {
        // Add Task Form Submission
        $(document).on('submit', '#wptaskboard-add-task-form', function(e) {
            e.preventDefault();

            const formData = {
                action: 'wptaskboard_add_task',
                title: $('#task-title').val(),
                description: $('#task-description').val(),
                priority: $('#task-priority').val(),
                assignee: $('#task-assignee').val(),
                nonce: wptaskboard_ajax.nonce
            };

            $.ajax({
                url: wptaskboard_ajax.ajax_url,
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        // Add the new task to the todo column
                        addTaskToColumn(response.data, 'todo');

                        // Close modal and reset form
                        wptaskboard_close_add_task_modal();
                        $('#wptaskboard-add-task-form')[0].reset();

                        // Update task counts
                        updateTaskCounts();
                    } else {
                        alert('Error: ' + response.data);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error:', error);
                    alert('Failed to add task. Please try again.');
                }
            });
        });
    }

    function addTaskToColumn(task, status) {
        const taskHtml = `
            <div class="kanban-task" data-task-id="${task.id}" draggable="true">
                <div class="task-title">${escapeHtml(task.title)}</div>
                ${task.description ? `<div class="task-description">${escapeHtml(task.description)}</div>` : ''}
                <div class="task-meta">
                    <span class="priority-badge priority-${task.priority}">
                        ${task.priority}
                    </span>
                    ${task.assignee ? `<span class="assignee">${escapeHtml(task.assignee)}</span>` : ''}
                </div>
            </div>
        `;

        const columnContent = $(`.kanban-column-${status} .kanban-column-content`);

        // Remove "No tasks" message if it exists
        columnContent.find('.kanban-empty').remove();

        // Add the new task
        columnContent.append(taskHtml);
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Global functions for modal
    window.wptaskboard_open_add_task_modal = function() {
        $('#wptaskboard-add-task-modal').show();
    };

    window.wptaskboard_close_add_task_modal = function() {
        $('#wptaskboard-add-task-modal').hide();
        $('#wptaskboard-add-task-form')[0].reset();
    };

    // Add some CSS for drag and drop effects
    const dragStyles = `
        <style>
        .kanban-task.dragging {
            opacity: 0.5;
            transform: rotate(5deg);
        }
        .kanban-column-content.drag-over {
            background-color: #e3f2fd;
            border: 2px dashed #2196f3;
        }
        .kanban-empty {
            color: #999;
            font-style: italic;
            text-align: center;
            padding: 20px;
        }
        </style>
    `;

    $('head').append(dragStyles);

})(jQuery);