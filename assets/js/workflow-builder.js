/**
 * Ryvr Workflow Builder
 * Visual workflow builder with drag-and-drop functionality
 */

class RyvrWorkflowBuilder {
    constructor(container) {
        this.container = container;
        this.nodes = new Map();
        this.connections = new Map();
        this.selectedNode = null;
        this.draggedElement = null;
        this.connectors = {};
        this.availableModels = {};
        
        // Connection state
        this.isConnecting = false;
        this.connectionStart = null;
        this.tempConnection = null;
        
        this.init();
    }
    
    init() {
        this.setupContainer();
        this.setupEventListeners();
        this.loadConnectors();
        this.loadAvailableModels();
        
        // Test SVG rendering disabled - connections working!
        // setTimeout(() => {
        //     this.testSVGRendering();
        // }, 1000);
    }
    
    testSVGRendering() {
        console.log('Testing SVG rendering...');
        
        if (!this.connectionsSvg) {
            console.error('SVG element not found!');
            return;
        }
        
        console.log('SVG element details:', {
            element: this.connectionsSvg,
            width: this.connectionsSvg.getAttribute('width'),
            height: this.connectionsSvg.getAttribute('height'),
            clientWidth: this.connectionsSvg.clientWidth,
            clientHeight: this.connectionsSvg.clientHeight,
            style: this.connectionsSvg.style.cssText
        });
        
        // Create multiple test elements
        const testLine = document.createElementNS('http://www.w3.org/2000/svg', 'line');
        testLine.setAttribute('x1', '100');
        testLine.setAttribute('y1', '100');
        testLine.setAttribute('x2', '300');
        testLine.setAttribute('y2', '200');
        testLine.setAttribute('stroke', '#ff0000');
        testLine.setAttribute('stroke-width', '5');
        testLine.setAttribute('id', 'test-line');
        
        const testCircle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
        testCircle.setAttribute('cx', '400');
        testCircle.setAttribute('cy', '150');
        testCircle.setAttribute('r', '20');
        testCircle.setAttribute('fill', '#00ff00');
        testCircle.setAttribute('id', 'test-circle');
        
        this.connectionsSvg.appendChild(testLine);
        this.connectionsSvg.appendChild(testCircle);
        
        console.log('Test elements added to SVG:', { testLine, testCircle });
        console.log('SVG children count:', this.connectionsSvg.children.length);
        console.log('SVG innerHTML:', this.connectionsSvg.innerHTML);
        
        // Remove test elements after 5 seconds
        setTimeout(() => {
            if (testLine.parentNode) testLine.remove();
            if (testCircle.parentNode) testCircle.remove();
            console.log('Test elements removed');
        }, 5000);
    }
    
    setupContainer() {
        this.container.innerHTML = `
            <div class="ryvr-workflow-builder">
                <div class="ryvr-sidebar">
                    <h3>Tasks</h3>
                    <div class="ryvr-connectors-list"></div>
                </div>
                
                <div class="ryvr-canvas">
                    <div class="ryvr-canvas-grid"></div>
                    <div class="ryvr-nodes-container"></div>
                    <svg class="ryvr-connections-svg" width="100%" height="100%" 
                         style="position: absolute; top: 0; left: 0; pointer-events: none; z-index: 1;">
                        <defs>
                            <marker id="arrowhead" markerWidth="6" markerHeight="4" 
                                    refX="6" refY="2" orient="auto">
                                <polygon points="0 0, 6 2, 0 4" 
                                         fill="#3b82f6" />
                            </marker>
                        </defs>
                    </svg>
                </div>
                
                <div class="ryvr-inspector">
                    <div class="ryvr-inspector-content">
                        <div class="empty-state">
                            <div class="icon">⚙️</div>
                            <h4>Select a task to configure</h4>
                            <p>Click on a task node to view and edit its parameters.</p>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        this.sidebarElement = this.container.querySelector('.ryvr-sidebar');
        this.canvasElement = this.container.querySelector('.ryvr-canvas');
        this.nodesContainer = this.container.querySelector('.ryvr-nodes-container');
        this.connectionsSvg = this.container.querySelector('.ryvr-connections-svg');
        this.inspectorElement = this.container.querySelector('.ryvr-inspector');
        this.inspectorContent = this.container.querySelector('.ryvr-inspector-content');
        
        // Debug SVG setup
        console.log('SVG element found:', this.connectionsSvg);
        console.log('Canvas element:', this.canvasElement);
        if (this.connectionsSvg) {
            console.log('SVG dimensions:', {
                width: this.connectionsSvg.clientWidth,
                height: this.connectionsSvg.clientHeight,
                boundingRect: this.connectionsSvg.getBoundingClientRect()
            });
        }
        if (this.canvasElement) {
            console.log('Canvas dimensions:', {
                width: this.canvasElement.clientWidth,
                height: this.canvasElement.clientHeight,
                boundingRect: this.canvasElement.getBoundingClientRect()
            });
        }
    }
    
    setupEventListeners() {
        // Canvas drag and drop
        this.canvasElement.addEventListener('dragover', this.handleDragOver.bind(this));
        this.canvasElement.addEventListener('drop', this.handleDrop.bind(this));
        
        // Node selection
        this.canvasElement.addEventListener('click', this.handleCanvasClick.bind(this));
        
        // Prevent default drag behavior on canvas
        this.canvasElement.addEventListener('dragstart', (e) => e.preventDefault());
    }
    
    async loadConnectors() {
        try {
            console.log('Workflow Builder: Loading connectors...');
            console.log('AJAX URL:', ryvrWorkflowBuilder.ajax_url);
            console.log('Nonce:', ryvrWorkflowBuilder.nonce);
            
            if (!ryvrWorkflowBuilder.nonce) {
                console.error('No nonce available - WordPress localization may have failed');
                return;
            }
            
            const response = await fetch(ryvrWorkflowBuilder.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'ryvr_get_connectors',
                    nonce: ryvrWorkflowBuilder.nonce
                })
            });
            
            console.log('Response status:', response.status);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            console.log('Response data:', data);
            
            if (data.success) {
                this.connectors = data.data;
                console.log('Loaded connectors:', this.connectors);
                this.renderConnectorsList();
            } else {
                console.error('Failed to load connectors:', data.data || data);
                this.showConnectorError('Failed to load connectors: ' + (data.data || 'Unknown error'));
            }
        } catch (error) {
            console.error('Failed to load connectors:', error);
            this.showConnectorError('Failed to load connectors: ' + error.message);
        }
    }
    
    async loadAvailableModels() {
        try {
            if (!ryvrWorkflowBuilder.nonce) {
                console.error('No nonce available for loading models');
                return;
            }
            
            const response = await fetch(ryvrWorkflowBuilder.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'ryvr_get_openai_models',
                    nonce: ryvrWorkflowBuilder.nonce
                })
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            if (data.success) {
                this.availableModels = data.data;
                console.log('Loaded OpenAI models:', this.availableModels);
            } else {
                console.error('Failed to load OpenAI models:', data.data || 'Unknown error');
            }
        } catch (error) {
            console.error('Failed to load OpenAI models:', error);
        }
    }
    
    renderConnectorsList() {
        const connectorsContainer = this.sidebarElement.querySelector('.ryvr-connectors-list');
        
        // Add data processing tools first
        const dataProcessingGroup = this.createDataProcessingGroup();
        
        // Group connectors by category
        const categories = {};
        Object.values(this.connectors).forEach(connector => {
            const category = connector.metadata?.category || 'other';
            if (!categories[category]) {
                categories[category] = [];
            }
            categories[category].push(connector);
        });
        
        connectorsContainer.innerHTML = '';
        
        // Add data processing tools at the top
        connectorsContainer.appendChild(dataProcessingGroup);
        
        Object.entries(categories).forEach(([category, connectors]) => {
            const groupElement = document.createElement('div');
            groupElement.className = 'ryvr-connector-group';
            
            const categoryName = this.formatCategoryName(category);
            groupElement.innerHTML = `<h4>${categoryName}</h4>`;
            
            connectors.forEach(connector => {
                Object.entries(connector.actions || {}).forEach(([actionId, action]) => {
                    const taskCard = this.createTaskCard(connector, actionId, action);
                    groupElement.appendChild(taskCard);
                });
            });
            
            connectorsContainer.appendChild(groupElement);
        });
    }

    createDataProcessingGroup() {
        const groupElement = document.createElement('div');
        groupElement.className = 'ryvr-connector-group';
        groupElement.innerHTML = `<h4>Data Processing</h4>`;
        
        const dataProcessingTasks = [
            {
                id: 'data_filter',
                name: 'Filter Data',
                description: 'Filter data based on conditions',
                icon: '🔍',
                category: 'data_processing'
            },
            {
                id: 'data_transform',
                name: 'Transform Data',
                description: 'Transform and modify data fields',
                icon: '🔄',
                category: 'data_processing'
            },
            {
                id: 'data_mapper',
                name: 'Map Fields',
                description: 'Map data fields between formats',
                icon: '🗺️',
                category: 'data_processing'
            },
            {
                id: 'data_validator',
                name: 'Validate Data',
                description: 'Validate data against rules',
                icon: '✅',
                category: 'data_processing'
            },
            {
                id: 'decision_node',
                name: 'Decision',
                description: 'Make decisions based on conditions',
                icon: '🔀',
                category: 'flow_control'
            },
            {
                id: 'delay_node',
                name: 'Delay',
                description: 'Add delays between tasks',
                icon: '⏱️',
                category: 'flow_control'
            }
        ];
        
        dataProcessingTasks.forEach(task => {
            const taskCard = this.createDataProcessingTaskCard(task);
            groupElement.appendChild(taskCard);
        });
        
        return groupElement;
    }

    createDataProcessingTaskCard(task) {
        const card = document.createElement('div');
        card.className = 'ryvr-task-card ryvr-data-processing-task';
        card.setAttribute('draggable', 'true');
        card.setAttribute('data-task-type', 'data_processing');
        card.setAttribute('data-task-id', task.id);
        
        card.innerHTML = `
            <div class="ryvr-task-icon">${task.icon}</div>
            <div class="ryvr-task-info">
                <div class="ryvr-task-name">${task.name}</div>
                <div class="ryvr-task-description">${task.description}</div>
            </div>
        `;
        
        card.addEventListener('dragstart', (e) => {
            this.draggedElement = {
                type: 'data_processing',
                taskId: task.id,
                task: task
            };
        });
        
        return card;
    }
    
    createTaskCard(connector, actionId, action) {
        const card = document.createElement('div');
        card.className = 'ryvr-task-card';
        card.setAttribute('draggable', 'true');
        card.setAttribute('data-connector', connector.metadata.id);
        card.setAttribute('data-action', actionId);
        
        card.innerHTML = `
            <div class="task-icon" style="background: ${connector.metadata.brand_color || '#666'}"></div>
            <div class="task-title">${action.name}</div>
            <div class="task-description">${action.description}</div>
        `;
        
        card.addEventListener('dragstart', this.handleTaskDragStart.bind(this));
        
        return card;
    }
    
    formatCategoryName(category) {
        return category.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
    }
    
    handleTaskDragStart(e) {
        this.draggedElement = {
            connector: e.target.getAttribute('data-connector'),
            action: e.target.getAttribute('data-action'),
            element: e.target
        };
        
        e.dataTransfer.effectAllowed = 'copy';
        e.dataTransfer.setData('text/plain', ''); // Required for drag to work
    }
    
    handleDragOver(e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'copy';
    }
    
    handleDrop(e) {
        e.preventDefault();
        
        if (!this.draggedElement) return;
        
        const rect = this.canvasElement.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;
        
        if (this.draggedElement.type === 'data_processing') {
            this.createDataProcessingNode(this.draggedElement.taskId, this.draggedElement.task, x, y);
        } else {
            this.createNode(this.draggedElement.connector, this.draggedElement.action, x, y);
        }
        
        this.draggedElement = null;
    }
    
    createNode(connectorId, actionId, x, y) {
        const nodeId = `node-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
        const connector = this.connectors[connectorId];
        const action = connector.actions[actionId];
        
        const nodeData = {
            id: nodeId,
            connectorId,
            actionId,
            x,
            y,
            parameters: {},
            jsonSchema: null
        };
        
        this.nodes.set(nodeId, nodeData);
        this.renderNode(nodeData);
    }

    createDataProcessingNode(taskId, task, x, y) {
        const nodeId = `node-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
        
        const nodeData = {
            id: nodeId,
            type: 'data_processing',
            taskId,
            taskName: task.name,
            x,
            y,
            parameters: this.getDefaultParametersForTask(taskId),
            config: {}
        };
        
        this.nodes.set(nodeId, nodeData);
        this.renderDataProcessingNode(nodeData);
    }

    getDefaultParametersForTask(taskId) {
        const defaults = {
            'data_filter': {
                conditions: [],
                operator: 'and'
            },
            'data_transform': {
                transformations: []
            },
            'data_mapper': {
                mappings: []
            },
            'data_validator': {
                rules: []
            },
            'decision_node': {
                condition: '',
                true_path: '',
                false_path: ''
            },
            'delay_node': {
                duration: 1000,
                unit: 'milliseconds'
            }
        };
        
        return defaults[taskId] || {};
    }
    
    renderNode(nodeData) {
        const connector = this.connectors[nodeData.connectorId];
        const action = connector.actions[nodeData.actionId];
        
        const nodeElement = document.createElement('div');
        nodeElement.className = 'ryvr-node';
        nodeElement.setAttribute('data-node-id', nodeData.id);
        nodeElement.style.left = `${nodeData.x}px`;
        nodeElement.style.top = `${nodeData.y}px`;
        
        nodeElement.innerHTML = `
            <div class="node-header">
                <div class="node-icon" style="background: ${connector.metadata.brand_color}"></div>
                <div class="node-title">${action.name}</div>
                <div class="node-status"></div>
            </div>
            <div class="node-description">${action.description}</div>
            <div class="ryvr-handle source" data-handle-type="source" data-node-id="${nodeData.id}">
                <div class="handle-dot"></div>
            </div>
            <div class="ryvr-handle target" data-handle-type="target" data-node-id="${nodeData.id}">
                <div class="handle-dot"></div>
            </div>
        `;
        
        nodeElement.addEventListener('click', (e) => {
            e.stopPropagation();
            this.selectNode(nodeData.id);
        });
        
        // Handle connection events
        this.setupConnectionHandles(nodeElement);
        
        // Make node draggable
        this.makeNodeDraggable(nodeElement, nodeData);
        
        this.nodesContainer.appendChild(nodeElement);
        
        // Debug log
        console.log('Regular node rendered with handles:', nodeElement.querySelectorAll('.ryvr-handle').length);
    }

    renderDataProcessingNode(nodeData) {
        const taskColors = {
            'data_filter': '#3b82f6',
            'data_transform': '#8b5cf6',
            'data_mapper': '#10b981',
            'data_validator': '#f59e0b',
            'decision_node': '#ef4444',
            'delay_node': '#6b7280'
        };
        
        const nodeElement = document.createElement('div');
        nodeElement.className = 'ryvr-node ryvr-data-processing-node';
        nodeElement.setAttribute('data-node-id', nodeData.id);
        nodeElement.style.left = `${nodeData.x}px`;
        nodeElement.style.top = `${nodeData.y}px`;
        
        const taskIcon = this.getTaskIcon(nodeData.taskId);
        const taskColor = taskColors[nodeData.taskId] || '#6b7280';
        
        nodeElement.innerHTML = `
            <div class="node-header">
                <div class="node-icon" style="background: ${taskColor}">${taskIcon}</div>
                <div class="node-title">${nodeData.taskName}</div>
                <div class="node-status"></div>
            </div>
            <div class="node-description">${this.getTaskDescription(nodeData.taskId)}</div>
            <div class="ryvr-handle source" data-handle-type="source" data-node-id="${nodeData.id}">
                <div class="handle-dot"></div>
            </div>
            <div class="ryvr-handle target" data-handle-type="target" data-node-id="${nodeData.id}">
                <div class="handle-dot"></div>
            </div>
        `;
        
        nodeElement.addEventListener('click', (e) => {
            e.stopPropagation();
            this.selectNode(nodeData.id);
        });
        
        // Handle connection events
        this.setupConnectionHandles(nodeElement);
        
        // Make node draggable
        this.makeNodeDraggable(nodeElement, nodeData);
        
        this.nodesContainer.appendChild(nodeElement);
        
        // Debug log
        console.log('Data processing node rendered with handles:', nodeElement.querySelectorAll('.ryvr-handle').length);
    }

    getTaskIcon(taskId) {
        const icons = {
            'data_filter': '🔍',
            'data_transform': '🔄',
            'data_mapper': '🗺️',
            'data_validator': '✅',
            'decision_node': '🔀',
            'delay_node': '⏱️'
        };
        
        return icons[taskId] || '⚙️';
    }

    getTaskDescription(taskId) {
        const descriptions = {
            'data_filter': 'Filter data based on conditions',
            'data_transform': 'Transform and modify data fields',
            'data_mapper': 'Map data fields between formats',
            'data_validator': 'Validate data against rules',
            'decision_node': 'Make decisions based on conditions',
            'delay_node': 'Add delays between tasks'
        };
        
        return descriptions[taskId] || 'Data processing task';
    }
    
    makeNodeDraggable(element, nodeData) {
        let isDragging = false;
        let dragOffset = { x: 0, y: 0 };
        
        element.addEventListener('mousedown', (e) => {
            if (e.target.closest('.ryvr-handle')) return; // Don't drag on handles
            
            isDragging = true;
            const rect = element.getBoundingClientRect();
            dragOffset.x = e.clientX - rect.left;
            dragOffset.y = e.clientY - rect.top;
            
            element.style.zIndex = '1000';
            document.addEventListener('mousemove', handleMouseMove);
            document.addEventListener('mouseup', handleMouseUp);
        });
        
        const handleMouseMove = (e) => {
            if (!isDragging) return;
            
            const canvasRect = this.canvasElement.getBoundingClientRect();
            const x = e.clientX - canvasRect.left - dragOffset.x;
            const y = e.clientY - canvasRect.top - dragOffset.y;
            
            nodeData.x = Math.max(0, x);
            nodeData.y = Math.max(0, y);
            
            element.style.left = `${nodeData.x}px`;
            element.style.top = `${nodeData.y}px`;
            
            // Update connections when node moves
            this.updateNodeConnections(nodeData.id);
        };
        
        const handleMouseUp = () => {
            isDragging = false;
            element.style.zIndex = '';
            document.removeEventListener('mousemove', handleMouseMove);
            document.removeEventListener('mouseup', handleMouseUp);
        };
    }
    
    updateNodeConnections(nodeId) {
        // Update all connections involving this node
        this.connections.forEach((connectionData, connectionId) => {
            if (connectionData.source === nodeId || connectionData.target === nodeId) {
                const sourceElement = this.nodesContainer.querySelector(`[data-node-id="${connectionData.source}"]`);
                const targetElement = this.nodesContainer.querySelector(`[data-node-id="${connectionData.target}"]`);
                
                if (sourceElement && targetElement) {
                    const sourceHandle = sourceElement.querySelector('.ryvr-handle.source');
                    const targetHandle = targetElement.querySelector('.ryvr-handle.target');
                    this.updateConnectionLine(connectionId, sourceHandle, targetHandle);
                }
            }
        });
    }
    
    handleCanvasClick(e) {
        if (e.target === this.canvasElement || e.target.closest('.ryvr-canvas-grid')) {
            this.selectNode(null);
        }
    }
    
    selectNode(nodeId) {
        // Deselect all nodes
        this.nodesContainer.querySelectorAll('.ryvr-node').forEach(node => {
            node.classList.remove('selected');
        });
        
        this.selectedNode = nodeId;
        
        if (nodeId) {
            const nodeElement = this.nodesContainer.querySelector(`[data-node-id="${nodeId}"]`);
            nodeElement.classList.add('selected');
            this.showNodeInspector(nodeId);
        } else {
            this.showEmptyInspector();
        }
    }
    
    showNodeInspector(nodeId) {
        const nodeData = this.nodes.get(nodeId);
        
        if (nodeData.type === 'data_processing') {
            this.showDataProcessingInspector(nodeData);
        } else {
            const connector = this.connectors[nodeData.connectorId];
            const action = connector.actions[nodeData.actionId];
            
            this.inspectorContent.innerHTML = `
                <h3>${action.name}</h3>
                <div class="ryvr-test-section">
                    <button type="button" class="ryvr-btn ryvr-btn-primary test-node-btn" data-node-id="${nodeId}">
                        🧪 Test Task
                    </button>
                    <div class="test-results" id="test-results-${nodeId}" style="display: none;"></div>
                </div>
                <div class="ryvr-inspector-form">
                    ${this.renderParameterForm(action, nodeData.parameters)}
                    ${this.renderJsonSchemaSection(nodeData)}
                </div>
            `;
            
            this.bindParameterFormEvents(nodeId);
            this.bindTestEvents(nodeId);
        }
    }

    showDataProcessingInspector(nodeData) {
        this.inspectorContent.innerHTML = `
            <h3>${nodeData.taskName}</h3>
            <div class="ryvr-test-section">
                <button type="button" class="ryvr-btn ryvr-btn-primary test-node-btn" data-node-id="${nodeData.id}">
                    🧪 Test Task
                </button>
                <div class="test-results" id="test-results-${nodeData.id}" style="display: none;"></div>
            </div>
            <div class="ryvr-inspector-form">
                ${this.renderDataProcessingForm(nodeData)}
            </div>
        `;
        
        this.bindDataProcessingFormEvents(nodeData.id);
        this.bindTestEvents(nodeData.id);
    }

    renderDataProcessingForm(nodeData) {
        switch (nodeData.taskId) {
            case 'data_filter':
                return this.renderFilterForm(nodeData);
            case 'data_transform':
                return this.renderTransformForm(nodeData);
            case 'data_mapper':
                return this.renderMapperForm(nodeData);
            case 'data_validator':
                return this.renderValidatorForm(nodeData);
            case 'decision_node':
                return this.renderDecisionForm(nodeData);
            case 'delay_node':
                return this.renderDelayForm(nodeData);
            default:
                return '<p>Configuration form not available for this task type.</p>';
        }
    }

    renderFilterForm(nodeData) {
        const conditions = nodeData.parameters.conditions || [];
        const operator = nodeData.parameters.operator || 'and';
        
        let conditionsHtml = conditions.map((condition, index) => `
            <div class="filter-condition" data-index="${index}">
                <select name="field">
                    <option value="${condition.field || ''}">${condition.field || 'Select field'}</option>
                </select>
                <select name="operator">
                    <option value="equals" ${condition.operator === 'equals' ? 'selected' : ''}>Equals</option>
                    <option value="not_equals" ${condition.operator === 'not_equals' ? 'selected' : ''}>Not Equals</option>
                    <option value="contains" ${condition.operator === 'contains' ? 'selected' : ''}>Contains</option>
                    <option value="not_contains" ${condition.operator === 'not_contains' ? 'selected' : ''}>Not Contains</option>
                    <option value="greater_than" ${condition.operator === 'greater_than' ? 'selected' : ''}>Greater Than</option>
                    <option value="less_than" ${condition.operator === 'less_than' ? 'selected' : ''}>Less Than</option>
                </select>
                <input type="text" name="value" value="${condition.value || ''}" placeholder="Value">
                <button type="button" class="remove-condition" data-index="${index}">Remove</button>
            </div>
        `).join('');
        
        return `
            <h4>Filter Conditions</h4>
            <div class="filter-conditions">
                ${conditionsHtml}
            </div>
            <button type="button" class="add-condition">Add Condition</button>
            
            <h4>Operator</h4>
            <select name="operator">
                <option value="and" ${operator === 'and' ? 'selected' : ''}>AND (all conditions must match)</option>
                <option value="or" ${operator === 'or' ? 'selected' : ''}>OR (any condition must match)</option>
            </select>
        `;
    }

    renderTransformForm(nodeData) {
        const transformations = nodeData.parameters.transformations || [];
        
        let transformationsHtml = transformations.map((transform, index) => `
            <div class="transformation" data-index="${index}">
                <select name="field">
                    <option value="${transform.field || ''}">${transform.field || 'Select field'}</option>
                </select>
                <select name="function">
                    <option value="uppercase" ${transform.function === 'uppercase' ? 'selected' : ''}>Uppercase</option>
                    <option value="lowercase" ${transform.function === 'lowercase' ? 'selected' : ''}>Lowercase</option>
                    <option value="trim" ${transform.function === 'trim' ? 'selected' : ''}>Trim</option>
                    <option value="replace" ${transform.function === 'replace' ? 'selected' : ''}>Replace</option>
                    <option value="format_date" ${transform.function === 'format_date' ? 'selected' : ''}>Format Date</option>
                    <option value="number_format" ${transform.function === 'number_format' ? 'selected' : ''}>Number Format</option>
                </select>
                <input type="text" name="params" value="${transform.params || ''}" placeholder="Function parameters (JSON)">
                <button type="button" class="remove-transformation" data-index="${index}">Remove</button>
            </div>
        `).join('');
        
        return `
            <h4>Transformations</h4>
            <div class="transformations">
                ${transformationsHtml}
            </div>
            <button type="button" class="add-transformation">Add Transformation</button>
        `;
    }

    renderMapperForm(nodeData) {
        const mappings = nodeData.parameters.mappings || [];
        
        let mappingsHtml = mappings.map((mapping, index) => `
            <div class="mapping" data-index="${index}">
                <input type="text" name="source" value="${mapping.source || ''}" placeholder="Source field">
                <span>→</span>
                <input type="text" name="target" value="${mapping.target || ''}" placeholder="Target field">
                <button type="button" class="remove-mapping" data-index="${index}">Remove</button>
            </div>
        `).join('');
        
        return `
            <h4>Field Mappings</h4>
            <div class="mappings">
                ${mappingsHtml}
            </div>
            <button type="button" class="add-mapping">Add Mapping</button>
        `;
    }

    renderValidatorForm(nodeData) {
        const rules = nodeData.parameters.rules || [];
        
        let rulesHtml = rules.map((rule, index) => `
            <div class="validation-rule" data-index="${index}">
                <select name="field">
                    <option value="${rule.field || ''}">${rule.field || 'Select field'}</option>
                </select>
                <select name="rule">
                    <option value="required" ${rule.rule === 'required' ? 'selected' : ''}>Required</option>
                    <option value="string" ${rule.rule === 'string' ? 'selected' : ''}>String</option>
                    <option value="integer" ${rule.rule === 'integer' ? 'selected' : ''}>Integer</option>
                    <option value="email" ${rule.rule === 'email' ? 'selected' : ''}>Email</option>
                    <option value="url" ${rule.rule === 'url' ? 'selected' : ''}>URL</option>
                    <option value="min_length" ${rule.rule === 'min_length' ? 'selected' : ''}>Min Length</option>
                    <option value="max_length" ${rule.rule === 'max_length' ? 'selected' : ''}>Max Length</option>
                    <option value="regex" ${rule.rule === 'regex' ? 'selected' : ''}>Regex</option>
                </select>
                <input type="text" name="params" value="${rule.params || ''}" placeholder="Rule parameters">
                <button type="button" class="remove-rule" data-index="${index}">Remove</button>
            </div>
        `).join('');
        
        return `
            <h4>Validation Rules</h4>
            <div class="validation-rules">
                ${rulesHtml}
            </div>
            <button type="button" class="add-rule">Add Rule</button>
        `;
    }

    renderDecisionForm(nodeData) {
        return `
            <h4>Decision Logic</h4>
            <label>Condition:</label>
            <textarea name="condition" placeholder="Enter JavaScript condition (e.g., data.status === 'active')">${nodeData.parameters.condition || ''}</textarea>
            
            <label>True Path Label:</label>
            <input type="text" name="true_path" value="${nodeData.parameters.true_path || 'Yes'}" placeholder="Label for true path">
            
            <label>False Path Label:</label>
            <input type="text" name="false_path" value="${nodeData.parameters.false_path || 'No'}" placeholder="Label for false path">
        `;
    }

    renderDelayForm(nodeData) {
        return `
            <h4>Delay Configuration</h4>
            <label>Duration:</label>
            <input type="number" name="duration" value="${nodeData.parameters.duration || 1000}" min="0">
            
            <label>Unit:</label>
            <select name="unit">
                <option value="milliseconds" ${nodeData.parameters.unit === 'milliseconds' ? 'selected' : ''}>Milliseconds</option>
                <option value="seconds" ${nodeData.parameters.unit === 'seconds' ? 'selected' : ''}>Seconds</option>
                <option value="minutes" ${nodeData.parameters.unit === 'minutes' ? 'selected' : ''}>Minutes</option>
                <option value="hours" ${nodeData.parameters.unit === 'hours' ? 'selected' : ''}>Hours</option>
            </select>
        `;
    }

    bindDataProcessingFormEvents(nodeId) {
        const nodeData = this.nodes.get(nodeId);
        
        // Add event listeners based on task type
        switch (nodeData.taskId) {
            case 'data_filter':
                this.bindFilterFormEvents(nodeId);
                break;
            case 'data_transform':
                this.bindTransformFormEvents(nodeId);
                break;
            case 'data_mapper':
                this.bindMapperFormEvents(nodeId);
                break;
            case 'data_validator':
                this.bindValidatorFormEvents(nodeId);
                break;
            case 'decision_node':
                this.bindDecisionFormEvents(nodeId);
                break;
            case 'delay_node':
                this.bindDelayFormEvents(nodeId);
                break;
        }
    }

    bindFilterFormEvents(nodeId) {
        const addButton = this.inspectorContent.querySelector('.add-condition');
        if (addButton) {
            addButton.addEventListener('click', () => {
                // Add new condition logic
                console.log('Add condition for node:', nodeId);
            });
        }
    }

    bindTransformFormEvents(nodeId) {
        const addButton = this.inspectorContent.querySelector('.add-transformation');
        if (addButton) {
            addButton.addEventListener('click', () => {
                // Add new transformation logic
                console.log('Add transformation for node:', nodeId);
            });
        }
    }

    bindMapperFormEvents(nodeId) {
        const addButton = this.inspectorContent.querySelector('.add-mapping');
        if (addButton) {
            addButton.addEventListener('click', () => {
                // Add new mapping logic
                console.log('Add mapping for node:', nodeId);
            });
        }
    }

    bindValidatorFormEvents(nodeId) {
        const addButton = this.inspectorContent.querySelector('.add-rule');
        if (addButton) {
            addButton.addEventListener('click', () => {
                // Add new validation rule logic
                console.log('Add validation rule for node:', nodeId);
            });
        }
    }

    bindDecisionFormEvents(nodeId) {
        const inputs = this.inspectorContent.querySelectorAll('input, textarea');
        inputs.forEach(input => {
            input.addEventListener('change', (e) => {
                this.updateDataProcessingParameter(nodeId, e.target.name, e.target.value);
            });
        });
    }

    bindDelayFormEvents(nodeId) {
        const inputs = this.inspectorContent.querySelectorAll('input, select');
        inputs.forEach(input => {
            input.addEventListener('change', (e) => {
                this.updateDataProcessingParameter(nodeId, e.target.name, e.target.value);
            });
        });
    }

    updateDataProcessingParameter(nodeId, paramName, value) {
        const nodeData = this.nodes.get(nodeId);
        if (nodeData) {
            nodeData.parameters[paramName] = value;
            console.log('Updated parameter:', paramName, 'to:', value, 'for node:', nodeId);
        }
    }
    
    renderParameterForm(action, currentParams) {
        const requiredParams = action.parameters?.required || [];
        const optionalParams = action.parameters?.optional || [];
        
        let html = '';
        
        if (requiredParams.length > 0) {
            html += '<h4>Required Parameters</h4>';
            requiredParams.forEach(param => {
                html += this.renderParameterField(param, currentParams[param], true);
            });
        }
        
        if (optionalParams.length > 0) {
            html += '<h4>Optional Parameters</h4>';
            optionalParams.forEach(param => {
                html += this.renderParameterField(param, currentParams[param], false);
            });
        }
        
        return html;
    }
    
    renderParameterField(paramName, currentValue, required) {
        const fieldId = `param-${paramName}`;
        const value = currentValue || '';
        
        // Special handling for specific parameters
        if (paramName === 'model' && this.availableModels.length) {
            return `
                <div class="ryvr-form-group">
                    <label class="ryvr-form-label" for="${fieldId}">
                        ${this.formatParameterName(paramName)} ${required ? '*' : ''}
                    </label>
                    <select class="ryvr-form-select" id="${fieldId}" data-param="${paramName}">
                        <option value="">Select a model...</option>
                        ${this.availableModels.map(model => 
                            `<option value="${model.id}" ${value === model.id ? 'selected' : ''}>
                                ${model.name} (${model.category})
                            </option>`
                        ).join('')}
                    </select>
                </div>
            `;
        }
        
        // Handle arrays (like messages for OpenAI)
        if (paramName === 'messages') {
            return `
                <div class="ryvr-form-group">
                    <label class="ryvr-form-label" for="${fieldId}">
                        ${this.formatParameterName(paramName)} ${required ? '*' : ''}
                    </label>
                    <textarea class="ryvr-form-textarea" id="${fieldId}" data-param="${paramName}" 
                              placeholder='[{"role": "user", "content": "Your prompt here"}]'>${value}</textarea>
                    <div class="ryvr-form-help">Enter JSON array of message objects</div>
                </div>
            `;
        }
        
        // Default text field
        return `
            <div class="ryvr-form-group">
                <label class="ryvr-form-label" for="${fieldId}">
                    ${this.formatParameterName(paramName)} ${required ? '*' : ''}
                </label>
                <input class="ryvr-form-input" type="text" id="${fieldId}" 
                       data-param="${paramName}" value="${value}">
            </div>
        `;
    }
    
    renderJsonSchemaSection(nodeData) {
        if (nodeData.connectorId !== 'openai') return '';
        
        return `
            <h4>JSON Schema Response Format</h4>
            <div class="ryvr-form-group">
                <label class="ryvr-form-label">
                    Enable Structured Output
                    <input type="checkbox" id="enable-json-schema" 
                           ${nodeData.jsonSchema ? 'checked' : ''}>
                </label>
            </div>
            <div id="json-schema-builder" style="display: ${nodeData.jsonSchema ? 'block' : 'none'}">
                ${this.renderJsonSchemaBuilder(nodeData.jsonSchema)}
            </div>
        `;
    }
    
    renderJsonSchemaBuilder(schema) {
        const properties = schema?.schema?.properties || {};
        
        return `
            <div class="ryvr-schema-builder">
                <h5>Response Properties</h5>
                <div id="schema-properties">
                    ${Object.entries(properties).map(([name, prop]) => 
                        this.renderSchemaProperty(name, prop)
                    ).join('')}
                </div>
                <button type="button" class="ryvr-btn ryvr-btn-secondary ryvr-btn-sm" 
                        onclick="ryvrWorkflowBuilder.addSchemaProperty()">
                    + Add Property
                </button>
            </div>
        `;
    }
    
    renderSchemaProperty(name, property) {
        return `
            <div class="ryvr-schema-property">
                <input type="text" placeholder="Property name" value="${name}">
                <select>
                    <option value="string" ${property.type === 'string' ? 'selected' : ''}>String</option>
                    <option value="number" ${property.type === 'number' ? 'selected' : ''}>Number</option>
                    <option value="boolean" ${property.type === 'boolean' ? 'selected' : ''}>Boolean</option>
                    <option value="array" ${property.type === 'array' ? 'selected' : ''}>Array</option>
                    <option value="object" ${property.type === 'object' ? 'selected' : ''}>Object</option>
                </select>
                <button type="button" class="ryvr-btn ryvr-btn-icon" onclick="this.parentElement.remove()">×</button>
            </div>
        `;
    }
    
    bindParameterFormEvents(nodeId) {
        // Parameter input changes
        this.inspectorContent.querySelectorAll('[data-param]').forEach(input => {
            input.addEventListener('change', (e) => {
                this.updateNodeParameter(nodeId, e.target.dataset.param, e.target.value);
            });
        });
        
        // JSON Schema toggle
        const schemaToggle = this.inspectorContent.querySelector('#enable-json-schema');
        if (schemaToggle) {
            schemaToggle.addEventListener('change', (e) => {
                const builder = this.inspectorContent.querySelector('#json-schema-builder');
                builder.style.display = e.target.checked ? 'block' : 'none';
                
                if (e.target.checked && !this.nodes.get(nodeId).jsonSchema) {
                    this.initializeJsonSchema(nodeId);
                }
            });
        }
    }
    
        updateNodeParameter(nodeId, paramName, value) {
        const nodeData = this.nodes.get(nodeId);
        nodeData.parameters[paramName] = value;
    }

    bindTestEvents(nodeId) {
        const testBtn = this.inspectorContent.querySelector('.test-node-btn');
        if (testBtn) {
            testBtn.addEventListener('click', () => {
                this.testNode(nodeId);
            });
        }
    }

    async testNode(nodeId) {
        const nodeData = this.nodes.get(nodeId);
        const testBtn = this.inspectorContent.querySelector('.test-node-btn');
        const resultsContainer = document.getElementById(`test-results-${nodeId}`);
        
        if (!testBtn || !resultsContainer) return;
        
        // Show loading state
        testBtn.disabled = true;
        testBtn.innerHTML = '⏳ Testing...';
        resultsContainer.style.display = 'block';
        resultsContainer.innerHTML = '<p>Running test...</p>';
        
        try {
            let result;
            
            if (nodeData.type === 'data_processing') {
                result = await this.testDataProcessingNode(nodeData);
            } else {
                result = await this.testConnectorNode(nodeData);
            }
            
            // Store test result for field mapping
            nodeData.lastTestResult = result;
            
            // Display results
            this.displayTestResults(nodeId, result);
            
        } catch (error) {
            console.error('Test failed:', error);
            resultsContainer.innerHTML = `
                <div class="test-error">
                    <h4>❌ Test Failed</h4>
                    <p>${error.message}</p>
                </div>
            `;
        } finally {
            // Reset button
            testBtn.disabled = false;
            testBtn.innerHTML = '🧪 Test Task';
        }
    }

    async testConnectorNode(nodeData) {
        const connector = this.connectors[nodeData.connectorId];
        const action = connector.actions[nodeData.actionId];
        
        // Prepare test data
        const testPayload = {
            action: 'ryvr_test_task',
            nonce: window.ryvrWorkflowBuilder.nonce,
            connector_id: nodeData.connectorId,
            action_id: nodeData.actionId,
            parameters: nodeData.parameters || {},
            test_mode: true
        };
        
        const response = await fetch(window.ryvrWorkflowBuilder.ajax_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams(testPayload)
        });
        
        const result = await response.json();
        
        if (!result.success) {
            throw new Error(result.data || 'Test execution failed');
        }
        
        return result.data;
    }

    async testDataProcessingNode(nodeData) {
        // For data processing nodes, we need sample input data
        // Try to get it from connected source nodes
        const sourceData = this.getSourceNodeTestData(nodeData.id);
        
        // Simulate data processing based on task type
        switch (nodeData.taskId) {
            case 'data_filter':
                return this.simulateDataFilter(sourceData, nodeData.parameters);
            case 'data_transform':
                return this.simulateDataTransform(sourceData, nodeData.parameters);
            case 'data_mapper':
                return this.simulateDataMapper(sourceData, nodeData.parameters);
            case 'data_validator':
                return this.simulateDataValidator(sourceData, nodeData.parameters);
            case 'decision_node':
                return this.simulateDecisionNode(sourceData, nodeData.parameters);
            case 'delay_node':
                return this.simulateDelayNode(sourceData, nodeData.parameters);
            default:
                throw new Error('Unknown data processing task type');
        }
    }

    getSourceNodeTestData(nodeId) {
        // Find connected source nodes that have test results
        for (const [connectionId, connection] of this.connections) {
            if (connection.target === nodeId) {
                const sourceNode = this.nodes.get(connection.source);
                if (sourceNode && sourceNode.lastTestResult) {
                    return sourceNode.lastTestResult;
                }
            }
        }
        
        // Return sample data if no source found
        return {
            sample_field: 'sample_value',
            status: 'active',
            count: 42,
            created_at: '2024-01-15T10:30:00Z',
            tags: ['tag1', 'tag2'],
            metadata: {
                source: 'test',
                priority: 'high'
            }
        };
    }

    simulateDataFilter(data, parameters) {
        const conditions = parameters.conditions || [];
        const operator = parameters.operator || 'and';
        
        // Simple simulation - just return filtered indication
        return {
            filtered: true,
            operator: operator,
            conditions_applied: conditions.length,
            sample_result: data,
            output_fields: Object.keys(data)
        };
    }

    simulateDataTransform(data, parameters) {
        const transformations = parameters.transformations || [];
        const transformedData = { ...data };
        
        // Apply sample transformations
        transformations.forEach(transform => {
            if (transform.field && transformedData[transform.field]) {
                switch (transform.function) {
                    case 'uppercase':
                        transformedData[transform.field] = String(transformedData[transform.field]).toUpperCase();
                        break;
                    case 'lowercase':
                        transformedData[transform.field] = String(transformedData[transform.field]).toLowerCase();
                        break;
                    case 'trim':
                        transformedData[transform.field] = String(transformedData[transform.field]).trim();
                        break;
                }
            }
        });
        
        return {
            transformed: true,
            transformations_applied: transformations.length,
            sample_result: transformedData,
            output_fields: Object.keys(transformedData)
        };
    }

    simulateDataMapper(data, parameters) {
        const mappings = parameters.mappings || [];
        const mappedData = {};
        
        mappings.forEach(mapping => {
            if (mapping.source && mapping.target && data[mapping.source]) {
                mappedData[mapping.target] = data[mapping.source];
            }
        });
        
        return {
            mapped: true,
            mappings_applied: mappings.length,
            sample_result: mappedData,
            output_fields: Object.keys(mappedData)
        };
    }

    simulateDataValidator(data, parameters) {
        const rules = parameters.rules || [];
        const validationResults = [];
        
        rules.forEach(rule => {
            validationResults.push({
                field: rule.field,
                rule: rule.rule,
                valid: Math.random() > 0.2 // Simulate 80% pass rate
            });
        });
        
        return {
            validated: true,
            rules_applied: rules.length,
            validation_results: validationResults,
            sample_result: data,
            output_fields: Object.keys(data)
        };
    }

    simulateDecisionNode(data, parameters) {
        const condition = parameters.condition || 'true';
        const result = Math.random() > 0.5; // Random decision for demo
        
        return {
            decision: true,
            condition: condition,
            result: result,
            path_taken: result ? (parameters.true_path || 'Yes') : (parameters.false_path || 'No'),
            sample_result: data,
            output_fields: Object.keys(data)
        };
    }

    simulateDelayNode(data, parameters) {
        const duration = parameters.duration || 1000;
        const unit = parameters.unit || 'milliseconds';
        
        return {
            delayed: true,
            duration: duration,
            unit: unit,
            sample_result: data,
            output_fields: Object.keys(data)
        };
    }

    displayTestResults(nodeId, result) {
        const resultsContainer = document.getElementById(`test-results-${nodeId}`);
        if (!resultsContainer) return;
        
        const resultHtml = `
            <div class="test-success">
                <h4>✅ Test Successful</h4>
                <div class="test-data">
                    <h5>Output Data Structure:</h5>
                    <pre class="json-output">${JSON.stringify(result, null, 2)}</pre>
                </div>
                <div class="available-fields">
                    <h5>Available Fields for Mapping:</h5>
                    <div class="field-list">
                        ${this.getAvailableFields(result).map(field => 
                            `<span class="field-tag" data-field="${field}">${field}</span>`
                        ).join('')}
                    </div>
                </div>
                <button type="button" class="ryvr-btn ryvr-btn-secondary refresh-connections-btn" data-node-id="${nodeId}">
                    🔄 Update Field Mappings
                </button>
            </div>
        `;
        
        resultsContainer.innerHTML = resultHtml;
        
        // Bind refresh connections button
        const refreshBtn = resultsContainer.querySelector('.refresh-connections-btn');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', () => {
                this.refreshConnectionMappings(nodeId);
            });
        }
        
        // Make field tags draggable for easy mapping
        resultsContainer.querySelectorAll('.field-tag').forEach(tag => {
            tag.draggable = true;
            tag.addEventListener('dragstart', (e) => {
                e.dataTransfer.setData('text/plain', tag.dataset.field);
                e.dataTransfer.effectAllowed = 'copy';
            });
        });
    }

    getAvailableFields(data, prefix = '') {
        const fields = [];
        
        if (typeof data === 'object' && data !== null) {
            for (const [key, value] of Object.entries(data)) {
                const fieldName = prefix ? `${prefix}.${key}` : key;
                fields.push(fieldName);
                
                if (typeof value === 'object' && value !== null && !Array.isArray(value)) {
                    fields.push(...this.getAvailableFields(value, fieldName));
                }
            }
        }
        
        return fields.sort();
    }

    refreshConnectionMappings(nodeId) {
        // Update all outgoing connections to show available source fields
        for (const [connectionId, connection] of this.connections) {
            if (connection.source === nodeId) {
                this.updateConnectionFieldOptions(connectionId);
            }
        }
        
        alert('Connection field mappings updated! Check the connection inspectors.');
    }

    updateConnectionFieldOptions(connectionId) {
        const connection = this.connections.get(connectionId);
        const sourceNode = this.nodes.get(connection.source);
        
        if (sourceNode && sourceNode.lastTestResult) {
            const availableFields = this.getAvailableFields(sourceNode.lastTestResult);
            connection.availableSourceFields = availableFields;
        }
    }

    initializeJsonSchema(nodeId) {
        const nodeData = this.nodes.get(nodeId);
        nodeData.jsonSchema = {
            name: 'response',
            schema: {
                type: 'object',
                properties: {}
            }
        };
    }
    
    addSchemaProperty() {
        const container = this.inspectorContent.querySelector('#schema-properties');
        const propertyElement = document.createElement('div');
        propertyElement.innerHTML = this.renderSchemaProperty('', { type: 'string' });
        container.appendChild(propertyElement.firstElementChild);
    }
    
    formatParameterName(param) {
        return param.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
    }
    
    showEmptyInspector() {
        this.inspectorContent.innerHTML = `
            <div class="empty-state">
                <div class="icon">⚙️</div>
                <h4>Select a task to configure</h4>
                <p>Click on a task node to view and edit its parameters.</p>
            </div>
        `;
    }
    
    // Export workflow as JSON
    exportWorkflow() {
        const workflow = {
            nodes: Array.from(this.nodes.values()),
            connections: Array.from(this.connections.values())
        };
        
        return JSON.stringify(workflow, null, 2);
    }
    
    // Load workflow from JSON
    loadWorkflow(workflowJson) {
        try {
            const workflow = JSON.parse(workflowJson);
            
            // Clear existing nodes and connections
            this.nodes.clear();
            this.connections.clear();
            this.nodesContainer.innerHTML = '';
            this.connectionsSpg.innerHTML = `
                <defs>
                    <marker id="arrowhead" markerWidth="10" markerHeight="7" 
                            refX="10" refY="3.5" orient="auto">
                        <polygon points="0 0, 10 3.5, 0 7" 
                                 fill="var(--ryvr-accent)" />
                    </marker>
                </defs>
            `;
            
            // Load nodes
            if (workflow.nodes) {
                workflow.nodes.forEach(nodeData => {
                    this.nodes.set(nodeData.id, nodeData);
                    this.renderNode(nodeData);
                });
            }
            
            // Load connections
            if (workflow.connections) {
                workflow.connections.forEach(connectionData => {
                    this.connections.set(connectionData.id, connectionData);
                    // Delay rendering connections to ensure nodes are rendered first
                    setTimeout(() => {
                        this.renderConnection(connectionData);
                    }, 100);
                });
            }
            
            console.log('Workflow loaded successfully:', workflow);
            
        } catch (error) {
            console.error('Failed to load workflow:', error);
            alert('Failed to load workflow: ' + error.message);
        }
    }
    
    showConnectorError(message) {
        const connectorsContainer = this.sidebarElement.querySelector('.ryvr-connectors-list');
        connectorsContainer.innerHTML = `
            <div class="ryvr-error-state">
                <div class="icon">⚠️</div>
                <h4>Error Loading Connectors</h4>
                <p>${message}</p>
                <button class="ryvr-btn ryvr-btn-secondary" onclick="location.reload()">Retry</button>
            </div>
        `;
    }
    
    setupConnectionHandles(nodeElement) {
        const handles = nodeElement.querySelectorAll('.ryvr-handle');
        console.log('Setting up connection handles for node:', nodeElement.getAttribute('data-node-id'), 'handles found:', handles.length);
        
        handles.forEach(handle => {
            handle.addEventListener('mousedown', (e) => {
                e.preventDefault();
                e.stopPropagation();
                console.log('Handle mousedown:', handle.getAttribute('data-handle-type'));
                this.startConnection(e, handle);
            });
            
            handle.addEventListener('mouseenter', (e) => {
                if (this.isConnecting && this.connectionStart) {
                    handle.classList.add('connection-target');
                    console.log('Handle entered during connection');
                }
            });
            
            handle.addEventListener('mouseleave', (e) => {
                handle.classList.remove('connection-target');
            });
            
            handle.addEventListener('mouseup', (e) => {
                e.preventDefault();
                e.stopPropagation();
                if (this.isConnecting) {
                    console.log('Completing connection to:', handle.getAttribute('data-handle-type'));
                    this.completeConnection(e, handle);
                }
            });
            
            // Add visual feedback
            handle.style.pointerEvents = 'auto';
        });
    }
    
    startConnection(e, handle) {
        const handleType = handle.getAttribute('data-handle-type');
        const nodeId = handle.getAttribute('data-node-id');
        
        console.log('Starting connection from:', nodeId, 'handle type:', handleType);
        
        // Only start connections from source handles
        if (handleType !== 'source') {
            console.log('Connection only starts from source handles');
            return;
        }
        
        this.isConnecting = true;
        this.connectionStart = {
            nodeId: nodeId,
            handle: handle,
            type: handleType
        };
        
        console.log('Connection started successfully');
        
        // Add temporary connection line that follows mouse
        document.addEventListener('mousemove', this.handleConnectionDrag.bind(this));
        document.addEventListener('mouseup', this.cancelConnection.bind(this));
        
        this.container.classList.add('connecting');
    }
    
    handleConnectionDrag(e) {
        if (!this.isConnecting || !this.connectionStart) return;
        
        // Remove existing temp connection
        if (this.tempConnection) {
            this.tempConnection.remove();
        }
        
        // Create temporary line following mouse
        const startHandle = this.connectionStart.handle;
        const startRect = startHandle.getBoundingClientRect();
        const canvasRect = this.canvasElement.getBoundingClientRect();
        
        const startX = startRect.left + startRect.width / 2 - canvasRect.left;
        const startY = startRect.top + startRect.height / 2 - canvasRect.top;
        const endX = e.clientX - canvasRect.left;
        const endY = e.clientY - canvasRect.top;
        
        this.tempConnection = this.createConnectionLine(startX, startY, endX, endY, true);
        this.connectionsSvg.appendChild(this.tempConnection);
    }
    
    completeConnection(e, targetHandle) {
        if (!this.isConnecting || !this.connectionStart) return;
        
        const targetType = targetHandle.getAttribute('data-handle-type');
        const targetNodeId = targetHandle.getAttribute('data-node-id');
        
        // Only complete connections to target handles
        if (targetType !== 'target') {
            this.cancelConnection();
            return;
        }
        
        // Don't connect to same node
        if (this.connectionStart.nodeId === targetNodeId) {
            this.cancelConnection();
            return;
        }
        
        // Create the connection
        this.createConnection(this.connectionStart.nodeId, targetNodeId);
        this.cancelConnection();
    }
    
    cancelConnection() {
        this.isConnecting = false;
        this.connectionStart = null;
        
        if (this.tempConnection) {
            this.tempConnection.remove();
            this.tempConnection = null;
        }
        
        this.container.classList.remove('connecting');
        document.removeEventListener('mousemove', this.handleConnectionDrag.bind(this));
        document.removeEventListener('mouseup', this.cancelConnection.bind(this));
        
        // Remove connection target highlights
        this.container.querySelectorAll('.connection-target').forEach(el => {
            el.classList.remove('connection-target');
        });
    }
    
    createConnection(sourceNodeId, targetNodeId) {
        const connectionId = `connection-${sourceNodeId}-${targetNodeId}`;
        
        // Check if connection already exists
        if (this.connections.has(connectionId)) {
            return;
        }
        
        const connectionData = {
            id: connectionId,
            source: sourceNodeId,
            target: targetNodeId,
            mapping: {} // For data mapping between nodes
        };
        
        this.connections.set(connectionId, connectionData);
        this.renderConnection(connectionData);
    }
    
    renderConnection(connectionData) {
        const sourceElement = this.nodesContainer.querySelector(`[data-node-id="${connectionData.source}"]`);
        const targetElement = this.nodesContainer.querySelector(`[data-node-id="${connectionData.target}"]`);
        
        if (!sourceElement || !targetElement) return;
        
        const sourceHandle = sourceElement.querySelector('.ryvr-handle.source');
        const targetHandle = targetElement.querySelector('.ryvr-handle.target');
        
        this.updateConnectionLine(connectionData.id, sourceHandle, targetHandle);
    }
    
    updateConnectionLine(connectionId, sourceHandle, targetHandle) {
        // Remove existing line
        const existingLine = this.connectionsSvg.querySelector(`[data-connection-id="${connectionId}"]`);
        if (existingLine) {
            existingLine.remove();
        }
        
        const sourceRect = sourceHandle.getBoundingClientRect();
        const targetRect = targetHandle.getBoundingClientRect();
        const canvasRect = this.canvasElement.getBoundingClientRect();
        
        const startX = sourceRect.left + sourceRect.width / 2 - canvasRect.left;
        const startY = sourceRect.top + sourceRect.height / 2 - canvasRect.top;
        const endX = targetRect.left + targetRect.width / 2 - canvasRect.left;
        const endY = targetRect.top + targetRect.height / 2 - canvasRect.top;
        
        const line = this.createConnectionLine(startX, startY, endX, endY, false, connectionId);
        this.connectionsSvg.appendChild(line);
    }
    
    createConnectionLine(startX, startY, endX, endY, isTemp = false, connectionId = null) {
        const line = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        
        // Create curved path with better control points
        const dx = endX - startX;
        const controlX1 = startX + Math.max(50, Math.abs(dx) * 0.5);
        const controlY1 = startY;
        const controlX2 = endX - Math.max(50, Math.abs(dx) * 0.5);
        const controlY2 = endY;
        
        const pathData = `M ${startX} ${startY} C ${controlX1} ${controlY1}, ${controlX2} ${controlY2}, ${endX} ${endY}`;
        
        line.setAttribute('d', pathData);
        line.setAttribute('stroke', isTemp ? '#ff6b6b' : '#3b82f6');
        line.setAttribute('stroke-width', isTemp ? '2' : '3');
        line.setAttribute('fill', 'none');
        line.setAttribute('marker-end', 'url(#arrowhead)');
        line.setAttribute('opacity', '1');
        line.style.pointerEvents = connectionId ? 'stroke' : 'none';
        
        if (connectionId) {
            line.setAttribute('data-connection-id', connectionId);
            line.classList.add('connection-line');
            
            // Add click handler for connection
            line.addEventListener('click', (e) => {
                e.stopPropagation();
                this.selectConnection(connectionId);
            });
        } else if (isTemp) {
            line.classList.add('temp-connection');
        }
        return line;
    }
    
    selectConnection(connectionId) {
        // Remove existing selection
        this.connectionsSpg.querySelectorAll('.connection-line').forEach(line => {
            line.classList.remove('selected');
        });
        
        // Select the clicked connection
        const connectionLine = this.connectionsSpg.querySelector(`[data-connection-id="${connectionId}"]`);
        if (connectionLine) {
            connectionLine.classList.add('selected');
        }
        
        // Show connection inspector
        this.showConnectionInspector(connectionId);
    }
    
    showConnectionInspector(connectionId) {
        const connectionData = this.connections.get(connectionId);
        const sourceNode = this.nodes.get(connectionData.source);
        const targetNode = this.nodes.get(connectionData.target);
        
        // Get available fields from source node if tested
        const sourceFields = connectionData.availableSourceFields || 
                            (sourceNode.lastTestResult ? this.getAvailableFields(sourceNode.lastTestResult) : []);
        
        const sourceFieldsHtml = sourceFields.length > 0 ? 
            `<div class="available-source-fields">
                <h5>Available Source Fields:</h5>
                <div class="field-list">
                    ${sourceFields.map(field => 
                        `<span class="field-tag draggable" data-field="${field}" draggable="true">${field}</span>`
                    ).join('')}
                </div>
            </div>` : 
            `<div class="no-source-fields">
                <p>No source fields available. Test the source task first to see available fields.</p>
            </div>`;
        
        const currentMappings = connectionData.mapping || {};
        const mappingsHtml = Object.keys(currentMappings).length > 0 ?
            `<div class="current-mappings">
                <h5>Current Field Mappings:</h5>
                <div class="mappings-list">
                    ${Object.entries(currentMappings).map(([source, target]) => 
                        `<div class="mapping-row">
                            <span class="source-field">${source}</span>
                            <span class="arrow">→</span>
                            <span class="target-field">${target}</span>
                            <button class="remove-mapping" data-source="${source}">×</button>
                        </div>`
                    ).join('')}
                </div>
            </div>` : '';
        
        this.inspectorContent.innerHTML = `
            <h3>Connection</h3>
            <div class="connection-info">
                <p><strong>From:</strong> ${sourceNode.actionId || sourceNode.taskName}</p>
                <p><strong>To:</strong> ${targetNode.actionId || targetNode.taskName}</p>
            </div>
            <div class="data-mapping">
                <h4>Data Mapping</h4>
                ${sourceFieldsHtml}
                ${mappingsHtml}
                <div class="mapping-tools">
                    <h5>Add Field Mapping:</h5>
                    <div class="mapping-form">
                        <input type="text" id="source-field-input" placeholder="Source field" list="source-fields-list">
                        <datalist id="source-fields-list">
                            ${sourceFields.map(field => `<option value="${field}">${field}</option>`).join('')}
                        </datalist>
                        <span>→</span>
                        <input type="text" id="target-field-input" placeholder="Target field">
                        <button class="ryvr-btn ryvr-btn-primary" id="add-mapping-btn">Add</button>
                    </div>
                </div>
                <div class="mapping-controls">
                    <button class="ryvr-btn ryvr-btn-danger" onclick="ryvrWorkflowBuilderInstance.deleteConnection('${connectionId}')">
                        Delete Connection
                    </button>
                </div>
            </div>
        `;
        
        // Bind events for field mapping
        this.bindConnectionMappingEvents(connectionId);
    }
    
    deleteConnection(connectionId) {
        if (confirm('Delete this connection?')) {
            // Remove from data
            this.connections.delete(connectionId);
            
            // Remove visual line
            const connectionLine = this.connectionsSpg.querySelector(`[data-connection-id="${connectionId}"]`);
            if (connectionLine) {
                connectionLine.remove();
            }
            
            // Clear inspector
            this.showEmptyInspector();
        }
    }
    
    bindConnectionMappingEvents(connectionId) {
        const addBtn = this.inspectorContent.querySelector('#add-mapping-btn');
        const sourceInput = this.inspectorContent.querySelector('#source-field-input');
        const targetInput = this.inspectorContent.querySelector('#target-field-input');
        
        if (addBtn && sourceInput && targetInput) {
            addBtn.addEventListener('click', () => {
                const sourceField = sourceInput.value.trim();
                const targetField = targetInput.value.trim();
                
                if (sourceField && targetField) {
                    this.addFieldMapping(connectionId, sourceField, targetField);
                    sourceInput.value = '';
                    targetInput.value = '';
                    this.showConnectionInspector(connectionId); // Refresh the view
                }
            });
            
            // Allow Enter key to add mapping
            [sourceInput, targetInput].forEach(input => {
                input.addEventListener('keypress', (e) => {
                    if (e.key === 'Enter') {
                        addBtn.click();
                    }
                });
            });
        }
        
        // Bind remove mapping buttons
        this.inspectorContent.querySelectorAll('.remove-mapping').forEach(btn => {
            btn.addEventListener('click', () => {
                const sourceField = btn.getAttribute('data-source');
                this.removeFieldMapping(connectionId, sourceField);
                this.showConnectionInspector(connectionId); // Refresh the view
            });
        });
        
        // Make field tags draggable and bind drag events
        this.inspectorContent.querySelectorAll('.field-tag.draggable').forEach(tag => {
            tag.addEventListener('dragstart', (e) => {
                e.dataTransfer.setData('text/plain', tag.dataset.field);
                e.dataTransfer.effectAllowed = 'copy';
            });
        });
        
        // Make target input accept dropped fields
        if (targetInput) {
            targetInput.addEventListener('dragover', (e) => {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'copy';
            });
            
            targetInput.addEventListener('drop', (e) => {
                e.preventDefault();
                const fieldName = e.dataTransfer.getData('text/plain');
                if (fieldName) {
                    sourceInput.value = fieldName;
                    targetInput.focus();
                }
            });
        }
    }

    addFieldMapping(connectionId, sourceField, targetField) {
        const connection = this.connections.get(connectionId);
        if (!connection.mapping) {
            connection.mapping = {};
        }
        connection.mapping[sourceField] = targetField;
    }

    removeFieldMapping(connectionId, sourceField) {
        const connection = this.connections.get(connectionId);
        if (connection.mapping && connection.mapping[sourceField]) {
            delete connection.mapping[sourceField];
        }
    }

    openMappingModal(connectionId) {
        // This method is now replaced by the inline mapping interface
        this.showConnectionInspector(connectionId);
    }
}

// Global instance and utilities
let ryvrWorkflowBuilderInstance = null;

// Preserve existing ryvrWorkflowBuilder data from WordPress localization
const existingData = window.ryvrWorkflowBuilder || {};

window.ryvrWorkflowBuilder = {
    // Preserve WordPress localized data
    nonce: existingData.nonce || '',
    ajax_url: existingData.ajax_url || '',
    
    init(container) {
        ryvrWorkflowBuilderInstance = new RyvrWorkflowBuilder(container);
        return ryvrWorkflowBuilderInstance;
    },
    
    addSchemaProperty() {
        if (ryvrWorkflowBuilderInstance) {
            ryvrWorkflowBuilderInstance.addSchemaProperty();
        }
    },
    
    deleteConnection(connectionId) {
        if (ryvrWorkflowBuilderInstance) {
            ryvrWorkflowBuilderInstance.deleteConnection(connectionId);
        }
    },
    
    openMappingModal(connectionId) {
        if (ryvrWorkflowBuilderInstance) {
            ryvrWorkflowBuilderInstance.openMappingModal(connectionId);
        }
    }
};

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    const container = document.querySelector('.ryvr-workflow-builder-container');
    if (container) {
        window.ryvrWorkflowBuilder.init(container);
    }
}); 