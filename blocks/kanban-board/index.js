// Kanban Block Registration
(function() {
    const { registerBlockType } = wp.blocks;
    const { useBlockProps, InspectorControls } = wp.blockEditor;
    const { PanelBody, TextControl, ToggleControl } = wp.components;
    const { __ } = wp.i18n;
    const { createElement } = wp.element;

    console.log('WPTaskBoard: Registering Kanban block...');

    registerBlockType('wptaskboard/kanban-board', {
        title: __('Kanban Task Board', 'wptaskboard'),
        description: __('A drag-and-drop task management board', 'wptaskboard'),
        category: 'widgets',
        icon: 'grid-view',
        keywords: ['kanban', 'task', 'board', 'project', 'management'],
        supports: {
            html: false,
            align: ['wide', 'full']
        },
        attributes: {
            boardTitle: {
                type: 'string',
                default: 'My Task Board'
            },
            showAddButton: {
                type: 'boolean',
                default: true
            }
        },

        edit: function(props) {
            const { attributes, setAttributes } = props;
            const { boardTitle, showAddButton } = attributes;

            const blockProps = useBlockProps({
                className: 'wptaskboard-kanban-board'
            });

            return [
                // Inspector Controls
                createElement(InspectorControls, { key: 'inspector' },
                    createElement(PanelBody, {
                            title: __('Board Settings', 'wptaskboard'),
                            initialOpen: true
                        },
                        createElement(TextControl, {
                            label: __('Board Title', 'wptaskboard'),
                            value: boardTitle,
                            onChange: function(value) {
                                setAttributes({ boardTitle: value });
                            }
                        }),
                        createElement(ToggleControl, {
                            label: __('Show Add Task Button', 'wptaskboard'),
                            checked: showAddButton,
                            onChange: function(value) {
                                setAttributes({ showAddButton: value });
                            }
                        })
                    )
                ),

                // Block Content
                createElement('div', { ...blockProps, key: 'content' },
                    createElement('div', { className: 'kanban-board-header' },
                        createElement('h2', {}, boardTitle),
                        showAddButton && createElement('button', {
                            className: 'kanban-add-task-btn',
                            onClick: function(e) {
                                e.preventDefault();
                                alert('Add Task functionality will be available on the frontend!');
                            }
                        }, '+ Add Task')
                    ),
                    createElement('div', { className: 'kanban-board' },
                        // To Do Column
                        createElement('div', { className: 'kanban-column kanban-column-todo' },
                            createElement('div', { className: 'kanban-column-header' },
                                createElement('h3', {}, __('To Do', 'wptaskboard')),
                                createElement('span', { className: 'task-count' }, '0')
                            ),
                            createElement('div', { className: 'kanban-column-content' },
                                createElement('div', { className: 'kanban-empty' }, __('No tasks', 'wptaskboard'))
                            )
                        ),
                        // In Progress Column
                        createElement('div', { className: 'kanban-column kanban-column-in-progress' },
                            createElement('div', { className: 'kanban-column-header' },
                                createElement('h3', {}, __('In Progress', 'wptaskboard')),
                                createElement('span', { className: 'task-count' }, '0')
                            ),
                            createElement('div', { className: 'kanban-column-content' },
                                createElement('div', { className: 'kanban-empty' }, __('No tasks', 'wptaskboard'))
                            )
                        ),
                        // Done Column
                        createElement('div', { className: 'kanban-column kanban-column-done' },
                            createElement('div', { className: 'kanban-column-header' },
                                createElement('h3', {}, __('Done', 'wptaskboard')),
                                createElement('span', { className: 'task-count' }, '0')
                            ),
                            createElement('div', { className: 'kanban-column-content' },
                                createElement('div', { className: 'kanban-empty' }, __('No tasks', 'wptaskboard'))
                            )
                        )
                    )
                )
            ];
        },

        save: function() {
            // Return null to use PHP render callback
            return null;
        }
    });

    console.log('WPTaskBoard: Kanban block registered successfully!');
})();